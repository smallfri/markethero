<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Jobs\SendEmail;
use App\Logger;
use App\Models\BlacklistModel;
use App\Models\BroadcastEmailModel;
use App\Models\Customer;
use App\Models\DeliveryServerModel;
use App\Models\GroupControlsModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailLogModel;
use App\Models\GroupEmailModel;
use App\Models\PauseGroupEmailModel;
use DB;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use PDO;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\Producer;
use RdKafka\TopicConf;
use Threading\Multiple;
use Threading\Task\Example;
use Illuminate\Support\Facades\Redis;


/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class SendEmailsCommand extends Command
{

    /**
     * @var string
     */
    protected $signature = 'send-broadcast';

    /**
     * @var string
     */
    protected $description = 'Sends Broadcast Emails';

    /**
     * @return int
     */

    /**
     * @var
     */
    protected $_group;

    // flag
    /**
     * @var bool
     */
    protected $_restoreStates = true;

    // flag
    /**
     * @var bool
     */
    protected $_improperShutDown = false;

    // global command arguments

    // what type of campaigns this command is sending
    /**
     * @var
     */
    public $groups_type;

    // how many campaigns to process at once
    /**
     * @var int
     */
    public $groups_limit = 0;

    // from where to start
    /**
     * @var int
     */
    public $groups_offset = 0;

    /**
     * @var
     */
    public $options;

    /**
     * @var int
     */
    public $verbose = 1;

    public $helpers;

    /**
     *
     */
    public function init()
    {

        // this will catch exit signals and restore states
        if ($this->functionExists('pcntl_signal'))
        {
            declare(ticks = 1);
            pcntl_signal(SIGINT, array($this, '_handleExternalSignal'));
            pcntl_signal(SIGTERM, array($this, '_handleExternalSignal'));
            pcntl_signal(SIGHUP, array($this, '_handleExternalSignal'));
        }

        register_shutdown_function(array($this, '_restoreStates'));

        // if more than 1 hour then something is def. wrong?
        ini_set('max_execution_time', 3600);
        set_time_limit(3600);

        $this->helpers = new Helpers();
    }

    /**
     * @param $signalNumber
     */
    public function _handleExternalSignal($signalNumber)
    {

        // this will trigger all the handlers attached via register_shutdown_function
        $this->_improperShutDown = true;
        exit;
    }

    /**
     * @param null $event
     */
    public function _restoreStates($event = null)
    {

        if (!$this->_restoreStates)
        {
            return;
        }
        $this->_restoreStates = false;

        // called as a callback from register_shutdown_function
        // must pass only if improper shutdown in this case
        if ($event===null&&!$this->_improperShutDown)
        {
            return;
        }

        if (!empty($this->_group)&&$this->_group instanceof GroupEmailModel)
        {
            if ($this->_group->isProcessing)
            {
                $this->updateGroupStatus($this->_group->primaryKey, GroupEmailGroupsModel::STATUS_SENDING);
            }
        }
    }

    public function handle()
    {

        // call process function to begin processing groups
        $rand = rand(2, 6);
        sleep($rand);
        $result = $this->process();

        return $result;
    }

    protected function process()
    {

        $options = $this->getOptions();

        $statuses = array(GroupEmailGroupsModel::STATUS_PROCESSING, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
        $limit = (int)$options->groups_in_parallel;

        $this->groups_type = null;

        if ((int)$this->groups_limit>0)
        {
            $limit = (int)$this->groups_limit;
        }

        $groups = GroupEmailGroupsModel::whereIn('status', $statuses)->get();
        $this->stdout(sprintf("Loading %d groups, starting with offset %d...", $limit, (int)$this->groups_offset));

        if (empty($groups))
        {
            $this->stdout("No Groups found, stopping.");
            return 0;
        }

        $this->stdout(sprintf("Found %d groups and now starting processing them...", count($groups)));
        if ($this->getCanUsePcntl())
        {
            $this->stdout(sprintf(
                'Since PCNTL is active, we will send %d groups in parallel and for each group, %d batches of group emails in parallel.',
                $this->getGroupsInParallel(),
                $this->getEmailBatchesInParallel()
            ));
        }

        $groupIds = array();
        foreach ($groups as $group)
        {
            $groupIds[] = $group->group_email_id;
        }

        $this->sendCampaignStep0($groupIds);
        return 0;
    }

    protected function sendCampaignStep0(array $groupIds = array())
    {

        $handled = false;
        if ($this->getCanUsePcntl()&&$this->getGroupsInParallel()>1)
        {
            $handled = true;

            $groupChunks = array_chunk($groupIds, $this->getGroupsInParallel());

            foreach ($groupChunks as $index => $cids)
            {
                $childs = array();
                foreach ($cids as $cid)
                {
                    $pid = pcntl_fork();
                    if ($pid==-1)
                    {
                        continue;
                    }

                    // Parent
                    if ($pid)
                    {
                        $childs[] = $pid;
                    }

                    // Child
                    if (!$pid)
                    {
                        $this->stdout('send groups step 1 cid: '.$cid.' index: '.$index+1);
                        $this->sendCampaignStep1($cid, $index+1);
                        exit;
                    }
                }

                while (count($childs)>0)
                {
                    foreach ($childs as $key => $pid)
                    {
                        $res = pcntl_waitpid($pid, $status, WNOHANG);
                        if ($res==-1||$res>0)
                        {
                            unset($childs[$key]);
                        }
                    }
                    sleep(4);
                }
            }
        }

        if (!$handled)
        {
            $this->stdout('Not handled');

            foreach ($groupIds as $groupId)
            {

                $this->sendCampaignStep1($groupId, 0);

                $this->stdout('Sending Group '.$groupId.'...');
            }

        }
    }

    protected function sendCampaignStep1($groupId, $workerNumber = 0)
    {

        $this->stdout(sprintf("Group Worker #%d looking into the Group with ID: %d", $workerNumber, $groupId));

        $statuses = array(
            GroupEmailGroupsModel::STATUS_SENDING,
            GroupEmailGroupsModel::STATUS_PENDING_SENDING,
            GroupEmailGroupsModel::STATUS_IN_COMPLIANCE_REVIEW
        );

        $group = GroupEmailGroupsModel::find($groupId);

        $this->_group = $group;

        if (empty($group)||!in_array($group->status, $statuses))
        {
            $this->stdout(sprintf("The Group with ID: %d is not ready for processing 2.", $groupId));
            $this->updateGroupStatus($groupId, GroupEmailGroupsModel::STATUS_SENT);

            Logger::addProgress(sprintf("The Group with ID: %d is not ready for processing.", $groupId),
                'Group Not Ready');

            return 1;
        }
        $options = $this->getOptions();

        if ($this->getCustomerStatus()=='inactive')
        {
            $this->updateGroupStatus($this->primaryKey, GroupEmailGroupsModel::STATUS_PAUSED);

            $this->stdout("This customer is inactive!");

            Logger::addProgress('This customer is inactive '.$this->_group->customer_id, 'Group Not Ready');

            return 1;
        }

        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();
        if (empty($server))
        {
            $this->stdout('Cannot find a valid server to send the group email, aborting until a delivery server is available!');

            Logger::addError('Cannot find a valid server ', 'No Server Found');

            return 1;
        }

        $this->stdout('Changing the group status into PROCESSING!');

        // put proper status

        // find the subscribers limit
        $limit = (int)$options->emails_at_once;


        $this->sendCampaignStep2(array(
            'group' => $group,
            'server' => $server,
            'limit' => $limit,
            'offset' => 0,
            'options' => $options,
            'canChangeCampaignStatus' => true,
        ));
    }

    protected function sendCampaignStep2(array $params = array())
    {

        $handled = false;
        if ($this->getCanUsePcntl()&&$this->getEmailBatchesInParallel()>1)
        {

            $handled = true;

            $childs = array();
            for ($i = 0;$i<$this->getEmailBatchesInParallel();++$i)
            {

                $pid = pcntl_fork();
                if ($pid==-1)
                {
                    continue;
                }

                // Parent
                if ($pid)
                {
                    $childs[] = $pid;
                }

                // Child
                if (!$pid)
                {
                    $params['workerNumber'] = $i+1;
                    $params['offset'] = ($i*$params['limit']);
                    $this->sendCampaignStep3($params);
                    exit;
                }
            }

            if (count($childs)==0)
            {
                $handled = false;
            }

            while (count($childs)>0)
            {
                foreach ($childs as $key => $pid)
                {
                    $res = pcntl_waitpid($pid, $status, WNOHANG);
                    if ($res==-1||$res>0)
                    {
                        unset($childs[$key]);
                    }
                }
                sleep(4);
            }
        }

        if (!$handled)
        {
            $this->stdout('Not handled');

            $this->sendCampaignStep3($params);
        }

        return 0;
    }

    protected function sendCampaignStep3(array $params = array())
    {

        extract($params, EXTR_SKIP);

        if (!isset($workerNumber))
        {
            $workerNumber = 1;
        }
        $this->stdout('Loading Queue Worker '.$workerNumber);

        $emails = $this->findEmailsForSending($group, $limit, $offset);

        $this->stdout('Limit '.$limit.' / '.'Offset '.$offset);

        $this->stdout(sprintf('Found %s emails for this batch', count($emails)));

        $this->stdout(sprintf("This emails worker(#%d) will process %d emails for this group...", $workerNumber,
            count($emails)));

        foreach ($emails AS $data)
        {

            $this->stdout('do stuff here');

            $this->stdout('Adding email '.$data['toEmail']);

            $job = (new SendEmail($data))->onConnection('redis')->onQueue('redis-group-queue');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

            $this->updateGroupEmailsToSent($data->emailID, GroupEmailGroupsModel::STATUS_QUEUED);

        }

        $this->stdout('Finished loading queue for worker '.$workerNumber);

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $emails = GroupEmailModel::where('status', '=', 'pending-sending')
            ->where('group_email_id', '=', $group->group_email_id)
            ->count();

        DB::disconnect('mysql');

        if (empty($emails))
        {
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENT);
        }

        return 0;
    }

    public function sendByPHPMailer($data)
    {

        $customer = Customer::find($data->customer_id);

        $server = DeliveryServerModel::find($customer->group_pool_id);

        if (empty($server))
        {
            $server = DeliveryServerModel::find(1);
        }

        if (isset($data->group_email_id)&&$data->group_email_id>1)
        {
            $group_email_id = $data->group_email_id;
        }
        else
        {
            $group_email_id = 1;
        }

        $pause = PauseGroupEmailModel::where('group_email_id', '=', $data->group_email_id)
            ->orWhere('customer_id', '=', $data->customer_id)
            ->get();

        if (count($pause))
        {
            $pause = $pause[0];

            if (!empty($pause))
            {
                if ($pause->group_email_id==$data->group_email_id||$pause->pause_customer==1)
                {
                    $this->delete();

                    GroupEmailModel::where('email_uid', '=', $data->email_uid)
                        ->update('status', '=', GroupEmailGroupsModel::STATUS_PAUSED);
                    return false;
                }
            }
        }

        try
        {

            $mail = New \PHPMailer();
            $mail->SMTPKeepAlive = true;

            $mail->isSMTP();
            $mail->CharSet = "utf-8";
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = "tls";
            $mail->Host = $server->hostname;
            $mail->Port = 2525;
            $mail->Username = $server->username;
            $mail->Password = base64_decode($server->password);
            $mail->Sender = Helpers::findBounceServerSenderEmail($server->bounce_server_id);

            $mail->addCustomHeader('X-Mw-Customer-Id', $data->customer_id);
            $mail->addCustomHeader('X-Mw-Email-Uid', $data->email_uid);
            $mail->addCustomHeader('X-Mw-Group-Id', $group_email_id);
            if ($group_email_id==1)
            {
                $mail->addCustomHeader('X-Mw-Transactional-Id', $group_email_id);
            }

            $mail->addReplyTo($data->from_email, $data->from_name);
            $mail->setFrom($data->from_email, $data->from_name);
            $mail->addAddress($data->to_email, $data->to_name);

            $mail->Subject = $data->subject;
            $mail->MsgHTML($data->body);

            if (!$mail->send())
            {
                // save status failed if mail did not send
                $status = 'failed';
            }
            else
            {
                // save status sent if mail DID send
                $status = 'sent';
            }

            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

        } catch (\Exception $e)
        {
            print_r($e);
            // save status error if try/catch returns error
            $status = 'error';

        }

       // $this->replyToMarketHero($data);

        $update = BroadcastEmailModel::find($data->email_id);
        $update->status = $status;
        $update->last_updated = new \DateTime();
        $update->save();

    }

    public function replyToMarketHero($Email)
    {

        $conf = new Conf();
        $conf->set('security.protocol', 'plaintext');
        $conf->set('broker.version.fallback', '0.8.2.1');

        $rk = new Producer($conf);
        $rk->setLogLevel(LOG_DEBUG);
//        $rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");
        $rk->addBrokers("zk-1.prod.markethero.io, zk-2.prod.markethero.io, zk-3.prod.markethero.io");

        $topic = $rk->newTopic("email_one_email_sent");
        $date = date_create();

        $message = [
            'mhEmailID' => $Email->mhEmailID,
            'emailOneEmailID' => $Email->email_uid,
            'sentDateTime' => date_format($date, 'U')
        ];

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message));
        //        var_dump(json_encode($message));
    }

    public function addToKafkaQueue($Email)
    {

        $conf = new Conf();
        $conf->set('security.protocol', 'plaintext');
        $conf->set('broker.version.fallback', '0.8.2.1');

        $rk = new Producer($conf);
        $rk->setLogLevel(LOG_DEBUG);
//        $rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");
        $rk->addBrokers("zk-1.prod.markethero.io, zk-2.prod.markethero.io, zk-3.prod.markethero.io");


        $topic = $rk->newTopic("email_one_email_queued");

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $Email);

    }




    /*
     * Helper Methods
     */

//    private function loadQueue($emails)
//    {
//
//        foreach ($emails AS $data)
//        {
//
//            $Email = GroupEmailModel::find($data['email_id']);
//
//            if (!empty($Email)&&$Email->status!='pending-sending')
//            {
//                print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
//                continue;
//            }
//
//            $this->stdout('Adding email '.$data['to_email']);
//
//            $job = (new SendEmail($Email))->onConnection('qa-mail-queue');
//            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
//
//            $this->updateGroupEmailsToSent($Email->email_id, GroupEmailGroupsModel::STATUS_SENT);
//
//        }
//    }

    /**
     * This is the method responsible for finding emails that are ready for sending
     * when group batch is processed.
     *
     * @param $group
     * @param $limit
     * @param $offset
     * @return mixed
     */
    protected function findEmailsForSending($group, $limit = 0, $offset = 100)
    {

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $emails = BroadcastEmailModel::where('status', '=', 'pending-sending')
            ->where('groupID', '=', $group->group_email_id)
            ->take($limit)
            ->skip($offset)
            ->get();

        DB::disconnect('mysql');
        return $emails;

    }

    /**
     * @param $message
     * @param bool|true $timer
     * @param string $separator
     */
    protected function stdout($message, $timer = true, $separator = "\n")
    {

        if (!$this->verbose)
        {
            return;
        }

        $out = '';
        if ($timer)
        {
            $out .= '['.date('Y-m-d H:i:s').'] - ';
        }
        $out .= $message;
        if ($separator)
        {
            $out .= $separator;
        }

        echo $out;
    }

    protected function getCanUsePcntl()
    {

        if (!$this->functionExists('pcntl_fork')||!$this->functionExists('pcntl_waitpid'))
        {
            return false;
        }

        return true;
    }

    /**
     * This returns the number of groups to run in parallel.
     * @return mixed
     */
    protected function getGroupsInParallel()
    {

        $options = $this->getOptions();

        $this->stdout('Groups in Parallel '.$options->groups_in_parallel);

        return $options->groups_in_parallel;
    }

    /**
     * This returns the number of email batches to create.
     *
     * @return mixed
     */
    protected function getEmailBatchesInParallel()
    {

        $options = $this->getOptions();

        $this->stdout('Batches in Parallel '.$options->group_emails_in_parallel);


        return $options->group_emails_in_parallel;
    }

    /**
     * CommonHelper::functionExists()
     *
     * @param string $name
     * @return bool
     */
    public static function functionExists($name)
    {

        static $_exists = array();
        static $_disabled = null;
        static $_shDisabled = null;

        if (isset($_exists[$name])||array_key_exists($name, $_exists))
        {
            return $_exists[$name];
        }

        if (!function_exists($name))
        {
            return $_exists[$name] = false;
        }

        if ($_disabled===null)
        {
            $_disabled = ini_get('disable_functions');
            $_disabled = explode(',', $_disabled);
            $_disabled = array_map('trim', $_disabled);
        }

        if (is_array($_disabled)&&in_array($name, $_disabled))
        {
            return $_exists[$name] = false;
        }

        if ($_shDisabled===null)
        {
            $_shDisabled = ini_get('suhosin.executor.func.blacklist');
            $_shDisabled = explode(',', $_shDisabled);
            $_shDisabled = array_map('trim', $_shDisabled);
        }

        if (is_array($_shDisabled)&&in_array($name, $_shDisabled))
        {
            return $_exists[$name] = false;
        }

        return $_exists[$name] = true;
    }

    protected function getOptions()
    {

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $Options = GroupControlsModel::find(1);

        DB::disconnect('mysql');

        $options = json_decode(json_encode($Options));

        return $options;
    }

    /*
     * DB Methods
     */

    /**
     * Updates the group status by id and status.
     *
     * @param $id
     * @param $status
     */
    protected function updateGroupStatus($id, $status)
    {

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        GroupEmailGroupsModel::where('group_email_id', $id)
            ->update(['status' => $status]);


        DB::disconnect('mysql');
        return;
    }

    protected function updateGroupEmailsToSent($id, $status)
    {

        $now = new \DateTime();

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        BroadcastEmailModel::where('emailID', '=', $id)
            ->update(['status' => $status, 'lastUpdated' => $now]);

        DB::disconnect('mysql');
        return;
    }

    /**
     * Returns the status of a customer by id.
     *
     * @return bool
     */
    protected function getCustomerStatus()
    {

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $customer = BroadcastEmailModel::select('c.status AS status')
            ->where('groupID', '=', $this->_group->group_email_id)
            ->join('mw_customer AS c', 'c.customer_id', '=', 'mw_broadcast_email_log.customerID')
            ->get();


        DB::disconnect('mysql');

        if (!empty($customer))
        {
            return $customer[0]->status;
        }
        return false;

    }

}