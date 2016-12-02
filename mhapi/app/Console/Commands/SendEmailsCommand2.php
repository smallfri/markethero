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
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailLogModel;
use App\Models\GroupEmailModel;
use DB;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use PDO;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\TopicConf;
use Threading\Multiple;
use Threading\Task\Example;

/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class SendEmailsCommand2 extends Command
{

    use DispatchesJobs;

    /**
     * @var
     */
    protected $_cipher;

    /**
     * @var
     */
    protected $_plainTextPassword;

    /**
     * @var string
     */
    protected $signature = 'send-groups-two';

    /**
     * @var string
     */
    protected $description = 'Sends Group Emails';

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

    /**
     * @return int
     */
    public function handle()
    {

        // call process function to begin processing groups
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

    /**
     * This method begins the forking process of the groups. It will array_chunk the groups, the size is determined
     * by the getGroupsInParallel call. The last step is to call sendCampaignStep1 to fork the group emails.
     *
     * @param array $groupIds
     */
    protected function sendCampaignStep0(array $groupIds = array())
    {

        $handled = false;
        if ($this->getCanUsePcntl()&&$this->getGroupsInParallel()>1)
        {
            $handled = true;

            $campaignChunks = array_chunk($groupIds, $this->getGroupsInParallel());

            foreach ($campaignChunks as $index => $cids)
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
                    sleep(1);
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

    /**
     * This method finds the groups that have been forked. It also chooses the delivery server and begins processing
     * the group.
     *
     * @param $groupId
     * @param int $workerNumber
     * @return int
     */
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
//                $group = GroupEmailGroupsModel::find($this->_group->group_email_id);


        // find the subscribers limit
        $limit = (int)$options->emails_at_once;

        $this->sendCampaignStep2(array(
            'group' => $group,
            'server' => $server,
            'limit' => $limit,
            'offset' => 0,
            'options' => $options,
        ));
    }

    /**
     * This method forks the groups into batches determined by the call to getEmailBatchesInParallel. And calls
     * sendCampaignStep3.
     *
     * @param array $params
     * @return int
     */
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
                sleep(1);
            }
        }

        if (!$handled)
        {
            $this->sendCampaignStep3($params);
        }

        return 0;
    }

    /**
     * This method sends the batches from the previous step. It also handles some email clean up and email sorting and
     * directly calls the sendEmail method.
     *
     * @param array $params
     */
    protected function sendCampaignStep3(array $params = array())
    {

        extract($params, EXTR_SKIP);


        $this->stdout('Here');

////        dd($params);

        if (!isset($workerNumber))
        {
            $workerNumber = 1;
        }

        $this->stdout(sprintf("Looking for emails for group with id %s...(This is email worker #%d)",
            $group->group_email_id, $workerNumber));

        $this->stdout('offset '.$offset.' limit '.$limit);

        $emails = $this->findEmailsForSending($group, $limit, $offset);
        $emailsCount = count($emails);

        $this->stdout(sprintf('Found %s emails for this batch', count($emails)));

        if ($emailsCount<=$offset||$emailsCount<$offset)
        {
            return;
        }

//        $this->sendByPHPMailer3($emails, $emailsCount, $group, $server);

        $this->loadQueue($emails);

        $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_PROCESSING);

        $this->stdout(sprintf("This emails worker(#%d) will process %d emails for this group...", $workerNumber,
            count($emails)));


        $this->stdout(sprintf("Checking emails count after cleanup: %d", $emailsCount));

        if (empty($emails))
        {
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENT);
        }

        $this->stdout('Sorting emails...');

        // sort emails
//        $emails = $this->sortEmails($emails);

//        dd($emails);

        $start = date('Y-m-d H:i:s');

        $this->stdout('HERE I AM');

//        $this->sendByPHPMailer3($emails, $emailsCount, $group, $server);

        $emailsRemaining = GroupEmailModel::where('group_email_id', '=', $group->group_email_id)
            ->where('status', '=', 'pending-sending')
            ->count();

        if ($emailsRemaining==0&&!$this->getEmailsInReview($group)>0)
        {
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENT);
            $this->stdout('Group has been marked as sent!');
        }
        elseif ($this->getEmailsInReview($group)>0)
        {
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_IN_COMPLIANCE_REVIEW);
            $this->stdout('Group has been marked as in in-compliance!');
        }
        else
        {
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
            $this->stdout('Group has been marked as pending-sending!');
        }

        $this->stdout("", false);
        $this->stdout(sprintf('Done processing %d emails!', count($emails)));

        $this->stdout('Done processing the group '.$group->group_email_id);

        $this->updateGroupEndTime($group->group_email_id, new \DateTime());

        $this->stdout('Start '.$start.' / End '.date('Y-m-d H:i:s'));

    }

    private function loadQueue($emails)
    {

        print_r($emails);

        foreach ($emails AS $data)
        {
            $emailUid = uniqid('', true);

            $EmailGroup = new \stdClass();
            $EmailGroup->email_id = $data['email_id'];
            $EmailGroup->email_uid = $emailUid;
            $EmailGroup->to_name = $data['to_name'];
            $EmailGroup->to_email = $data['to_email'];
            $EmailGroup->from_name = $data['from_name'];
            $EmailGroup->from_email = $data['from_email'];
            $EmailGroup->reply_to_name = $data['reply_to_name'];
            $EmailGroup->reply_to_email = $data['reply_to_email'];
            $EmailGroup->subject = $data['subject'];
            $EmailGroup->body = $data['body'];
            $EmailGroup->plain_text = $data['plain_text'];
            $EmailGroup->send_at = $data['send_at'];
            $EmailGroup->customer_id = $data['customer_id'];
            $EmailGroup->group_email_id = $data['group_email_id'];
            $EmailGroup->status = GroupEmailGroupsModel::STATUS_QUEUED;
            $EmailGroup->date_added = new \DateTime();
            $EmailGroup->max_retries = 5;

            print_r($EmailGroup);

            $Email = GroupEmailModel::find($data['email_id']);
            if(!empty($Email) && $Email->status =='sent')
            {
                print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
                return false;
            }



            $job = (new SendEmail($EmailGroup))->onConnection('mail-queue');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
        return true;
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

    /**
     * Tries to:
     * 1. Group the subscribers by domain
     * 2. Sort them so that we don't send to same domain two times in a row.
     */
    protected function sortEmails(array &$emails)
    {


        $emailsCount = count($emails);
        $_emails = array();

        foreach ($emails as $index => $email)
        {
            $emailParts = explode('@', $email['to_email']);
            if (array_key_exists(1, $emailParts))
            {
                $domainName = $emailParts[1];
                if (!isset($_emails[$domainName]))
                {
                    $_emails[$domainName] = array();
                }
                $_emails[$domainName][] = $email;
                unset($emails[$index]);
            }

        }

        $emails = array();
        while ($emailsCount>0)
        {
            foreach ($_emails as $domainName => $subs)
            {
                foreach ($subs as $index => $sub)
                {
                    $emails[] = $sub;
                    unset($_emails[$domainName][$index]);
                }
            }
            $emailsCount--;
        }
        return $emails;
    }

    /**
     * This method returns the options and settings from the db.
     *
     * @return \stdClass
     */
    protected function getOptions()
    {

        $options = new \stdClass();
        //
        //        /**
        //         * temporarily not using db so I can quickly make changes.
        //         */
        $options->scrub = 1;
        $options->id = 1;
        $options->emails_at_once = 2000;
        $options->change_server_at = 1000;
        $options->compliance_limit = 2000;
        $options->compliance_abuse_range = .01;
        $options->compliance_unsub_range = .01;
        $options->compliance_bounce_range = .01;
        $options->groups_in_parallel = 10;
        $options->group_emails_in_parallel = 10;

        //        $options = GroupControlsModel::find(1);

        return $options;

    }

    /**
     * Returns the status of a customer by id.
     *
     * @return bool
     */
    protected function getCustomerStatus()
    {

        //        $customer = GroupEmailGroupsModel::select('c.status AS status')
        //            ->where('group_email_id', '=', $this->_group->group_email_id)
        //            ->join('mw_customer AS c', 'c.customer_id', '=', 'mw_group_email_groups.customer_id')
        //            ->get();

        $sql
            = 'select `c`.`status` as `status` from `mw_group_email_groups` inner join `mw_customer` as `c` on `c`.`customer_id` = `mw_group_email_groups`.`customer_id` where `group_email_id` = '.$this->_group->group_email_id;

        $customer = DB::select(DB::raw($sql));

        if (!empty($customer))
        {
            return $customer[0]->status;
        }
        return false;

    }

    /**
     * Logs the delivery of an email by email id.
     * @param $emailId
     * @param string $message
     */
    public function logGroupEmailDelivery($emailId, $message = 'OK')
    {

        GroupEmailLogModel::insert([
            'email_uid' => $emailId,
            'message' => $message,
            'date_added' => new \DateTime()
        ]);
        return;

    }

    /**
     * This determines if we can use Proccess Control.
     * @return bool
     */
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

    /**
     * Updates the started_at column with the time the group started processing.
     * @param $id
     * @param $startTime
     */
    protected function updateGroupStartTime($id, $startTime)
    {

        //        GroupEmailGroupsModel::where('group_email_id', $id)
        //            ->update(['started_at' => $startTime]);

        $sql = 'UPDATE mw_group_email_groups SET started_at = "'.$startTime.'" WHERE group_email_id = '.$id;

        DB::select(DB::raw($sql));

        return;
    }

    /**
     * Updates the group finished_at column with the time the group has finished
     * processing.
     *
     * @param $id
     * @param $endTime
     */
    protected function updateGroupEndTime($id, $endTime)
    {

        GroupEmailGroupsModel::where('group_email_id', $id)
            ->update(['finished_at' => $endTime]);

        return;
    }

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

        //Server is set to UTC + 10 minutes???
        $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone('Etc/UTC'));

        //Set user timezone to EST for the time being
        $date->setTimezone(new \DateTimeZone('EST'));

        //fix the 10 minute difference
        $date->sub(new \DateInterval('PT10M'));

        $now = $date->format('Y-m-d H:i:s');

        $this->stdout('Now: '.$now);

//        $sql
//            = 'SELECT * FROM mw_group_email WHERE status = "pending-sending" AND group_email_id = '.$group->group_email_id.' LIMIT '.$offset.', '.$limit;
//        $emails = DB::select(DB::raw($sql));


        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $emails = GroupEmailModel::where('status', '=', 'pending-sending')
            ->where('group_email_id', '=', $group->group_email_id)
            ->take($limit)
            ->skip($offset)
            ->get();


        DB::disconnect('mysql');
        return $emails;

    }

    /**
     * Retrieves emails that are in "in-review" status
     *
     * @param $group
     * @return int
     */
    protected function getEmailsInReview($group)
    {

        $emailsInReview = GroupEmailModel::where('status', '=', 'in-review')
            ->where('group_email_id', '=', $group->group_email_id)
            ->count();

        if ($emailsInReview>1)
        {
            return $emailsInReview;
        }
        return 0;
    }

    /**
     * Checks to see if the group status is sent.
     *
     * @param $group
     * @return mixed
     */
    protected function groupIsFinished($group)
    {

        $count = GroupEmailModel::where('status', '=', 'sent')
            ->where('group_email_id', '=', $group->group_email_id)
            ->count();

        return $count;
    }

    /**
     * Counts emails in the status "pending-sending"
     *
     * @param $group_email_id
     * @return mixed
     */
    protected function countEmails($group_email_id)
    {

        //        $count = GroupEmailModel::where('status', '=', 'pending-sending')
        //            ->where('group_email_id', '=', $group_email_id)
        //            ->count();

        $sql = 'SELECT count(*) AS count FROM mw_group_email where `group_email_id` = '.$group_email_id;

        $results = DB::select(DB::raw($sql));

        return $results[0]->count;
    }

    /**
     * Adds an email to the blacklist by customer id and email id
     *
     * @param $email
     * @param $customerId
     */
    protected function addToBlacklist($email, $customerId)
    {

        $blackList = new BlacklistModel();

        $blackList->email_id = $email['primaryKey'];
        $blackList->reason = 'Invalid email address format!';
        $blackList->customer_id = $customerId;
        $blackList->date_added = new \DateTime();
        $blackList->Save();

        Logger::addProgress('This email has been blacklisted '.$email['primaryKey'], 'Email Blacklisted');

    }

    /**
     * Checks for an email on the blacklist
     *
     * @param $email
     * @param $customerId
     * @return bool
     */
    protected function isBlacklisted($email, $customerId)
    {

        $blacklist = BlacklistModel::where('email', '=', $email)->where('customer_id', '=', $customerId)->first();

        if (!empty($blacklist))
        {
            return true;
        }
        return false;
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

    /**
     * This method updates the status of group emails
     *
     * @param $mail
     * @param $status
     */
    protected function updateGroupEmailStatus($mail, $status)
    {

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);


        GroupEmailModel::where('email_uid', $mail['email_uid'])
            ->update(['status' => $status, 'last_updated' => new \DateTime()]);

        DB::disconnect('mysql');

    }

    /**
     * This method sends mail via PHPMailer after a number of groups and batches are created
     *
     * @param $emails
     * @param $emailsCount
     * @param $group
     * @param $server
     */
    public function sendByPHPMailer3($emails, $emailsCount, $group, $server)
    {

        $this->stdout('Getting ready to send mail...', false);

//        try{

        $mail = new \PHPMailer;

        $mail->isSMTP();

        $mail->SMTPAuth = true;

        $mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead

        $mail->SMTPSecure = "tls";
        $mail->Host = gethostbyname($server[0]['hostname']);
        $mail->Port = 2525;
        $mail->Username = $server[0]['username'];
        $mail->Password = base64_decode($server[0]['password']);
        $mail->Sender = Helpers::findBounceServerSenderEmail($server[0]['bounce_server_id']);

        foreach ($emails as $index => $email)
        {

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $this->stdout(sprintf("%s - %d/%d - group %d", $email['to_email'], ($index+1), $emailsCount,
                $group->group_email_id));

            $mail->addCustomHeader('X-Mw-Group-Id', $group->group_email_id);
            $mail->addCustomHeader('X-Mw-Customer-Id', $group->customer_id);
            $mail->addCustomHeader('X-Mw-Email-Uid', $email['email_uid']);

            $mail->addReplyTo($email['from_email'], $email['from_name']);
            $mail->setFrom($email['from_email'], $email['from_name']);
            $mail->addAddress($email['to_email'], $email['to_name']);

            $mail->Subject = $email['subject'];
            $mail->MsgHTML($email['body']);
//            $mail->AltBody = $email['plain_text'];

            if (!$mail->send())
            {
                $this->logGroupEmailDelivery($email['email_uid'], $mail->ErrorInfo);
                $this->stdout('ERROR Sending group email to '.$email['to_email'].'!');
                $this->stdout('ERROR '.$mail->ErrorInfo.'!');
            }
            else
            {
                $this->logGroupEmailDelivery($email['email_uid'], 'OK');
                $this->stdout('Sent group email  to '.$email['to_email'].'!');
            }

            $this->updateGroupEmailStatus($email, GroupEmailGroupsModel::STATUS_SENT);
            // Clear all addresses and attachments for next loop
            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

        }

//        } catch (\Exception $e)
//                              {
//                                  // save status error if try/catch returns error
//                                  $this->stdout($e);
//                              }
    }

}