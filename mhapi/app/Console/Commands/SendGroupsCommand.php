<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;


use App\Logger;
use App\Models\BlacklistModel;
//use App\Models\BounceServer;
use App\Models\DeliveryServerModel;
//use App\Models\GroupControlsModel;
use App\Models\GroupControlsModel;
use App\Models\GroupEmailComplianceLevelsModel;
use App\Models\GroupEmailComplianceModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailLogModel;
use App\Models\GroupEmailModel;
use App\Helpers\Helpers;
use DB;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
//use phpseclib\Crypt\AES;
use Swift_Plugins_AntiFloodPlugin;
use Symfony\Component\Yaml\Yaml;


/**
 * This class is called by a cron job in Kernel.php. It takes a number of groups at once and creates
 * batches of emails for those groups, and then sends them.
 *
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class SendGroupsCommand extends Command
{

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
    protected $signature = 'send-groups';

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

        $this->helpers= new Helpers();
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

    /**
     * This method pulls groups that are in the status sending or pending-sending. It then calls sendCampaignStep0.
     * It also runs the compliance check.
     *
     * @return int
     */
    protected function process()
    {

        $options = $this->getOptions();

        $statuses = array(GroupEmailGroupsModel::STATUS_PROCESSING, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
        $types = array(GroupEmailGroupsModel::TYPE_REGULAR, GroupEmailGroupsModel::TYPE_AUTORESPONDER);
        $limit = (int)$options->groups_in_parallel;

        if ($this->groups_type!==null&&!in_array($this->groups_type, $types))
        {
            $this->groups_type = null;
        }

        if ((int)$this->groups_limit>0)
        {
            $limit = (int)$this->groups_limit;
        }

//        $groups = GroupEmailGroupsModel::whereIn('status', $statuses)->get();

        $sql = 'SELECT * FROM mw_group_email_groups WHERE status = "pending-sending" OR status = "processing"';
               $groups = DB::select(DB::raw($sql));

        //handle compliance

//        $this->helpers->complianceHandler($groups);

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

            $emails = $this->countEmails($group->group_email_id);
//
//            if ($emails==0&&strtotime($group['date_added'])<strtotime('+10 minutes'))
//            {
//                $this->stdout('No emails found to be ready for sending, setting group status '.$group['group_email_id'].' to pending-sending.');
//                $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
//                continue;
//            }
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

//        $group = GroupEmailGroupsModel::find($groupId);

        $sql = 'SELECT * FROM mw_group_email_groups WHERE group_email_id = '.$groupId;
        $group = DB::select(DB::raw($sql));

//        dd($group);

        $this->_group = $group[0];

        if (empty($group)||!in_array($group[0]->status, $statuses))
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
//        $group = GroupEmailGroupsModel::find($this->_group->group_email_id);

        $sql = 'SELECT * FROM mw_group_email_groups WHERE group_email_id = '.$this->_group->group_email_id;

         $group = DB::select(DB::raw($sql));

//        dd($group);

//        $group->status = GroupEmailGroupsModel::STATUS_PROCESSING;
//
//        if ($group->started_at==null)
//        {
            $started_at = date('Y-M-d H:i:s');
//        }
//        $group->save();

//        $sql = 'UPDATE mw_group_email_groups SET status = "'.GroupEmailGroupsModel::STATUS_PROCESSING.'" WHERE group_email_id = '.$this->_group->group_email_id;
//
//        DB::select(DB::raw($sql));

        // find the subscribers limit
        $limit = (int)$options->emails_at_once;

        $this->sendCampaignStep2(array(
            'group' => $group[0],
            'server' => $server,
            'limit' => $limit,
            'offset' => 0,
            'options' => $options,
            'canChangeCampaignStatus' => true,
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
                    $params['canChangeCampaignStatus']
                        = ($i==($this->getEmailBatchesInParallel()-1)); // last call only
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

        if (!isset($workerNumber))
        {
            $workerNumber = 1;
        }


        $this->stdout(sprintf("Looking for emails for group with id %s...(This is email worker #%d)",
            $group->group_email_id, $workerNumber));

        $this->stdout('limit '.$limit.' offset '.$offset);

        $emails = $this->findEmailsForSending($group, $limit, $offset);

//        dd($emails);

        $this->stdout(sprintf('Found %s emails for this batch', count($emails)));

        $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_PROCESSING);

        $this->stdout(sprintf("This emails worker(#%d) will process %d emails for this group...", $workerNumber,
            count($emails)));

        $emailsCount = count($emails);

        $this->stdout(sprintf("Checking emails count after cleanup: %d", $emailsCount));

        if (empty($emails))
        {
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENT);
        }

        $this->stdout('Sorting emails...');

        // sort emails
        $emails = $this->sortEmails($emails);

        $start = date('Y-m-d H:i:s');

        $this->sendByPHPMailer3($emails, $emailsCount, $group, $server);

        $emailsRemaining = GroupEmailModel::where('group_email_id', '=', $group->group_email_id)
            ->where('status', '=', 'pending-sending')
            ->count();

        if ($emailsRemaining==0 && !$this->getEmailsInReview($group) > 0)
        {
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENT);
            $this->stdout('Group has been marked as sent!');
        }
        elseif ($this->getEmailsInReview($group) > 0)
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


    /**
     * This method handles compliance, it will send a number of emails determined by the options, and place the
     * remainder in-review status until the group has been approved.
     *
     * @param $groups
     */

    //moved to helpers

//    protected function complianceHandler($groups)
//    {
//
//        foreach ($groups AS $group)
//        {
//
//            $this->stdout('Starting Compliance Handler');
//
//            $group->compliance = GroupEmailComplianceModel::find($group->group_email_id);
//
//            if (empty($group->compliance))
//            {
//                $this->stdout('Missing compliance entry in table...');
//                continue;
//            }
//
//            $group->compliance->compliance_levels
//                = GroupEmailComplianceLevelsModel::find($group->compliance->compliance_level_type_id);
//
//            $count = GroupEmailModel::where('group_email_id', '=', $group->group_email_id)->count();
//
//            $options = $this->getOptions();
//
//            if ($group->compliance->compliance_status=='in-review' AND $count>=$options->compliance_limit)
//            {
//
//                $this->stdout('This Group is in Compliance Review...');
//
//                $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_COMPLIANCE_REVIEW);
//
//                // Set emails to be sent = threshold X count
//                $emailsToBeSent = ceil($count*$group->compliance->compliance_levels->threshold);
//
//                $this->stdout('There are '.$emailsToBeSent.' emails to be sent...');
//
//
//                // Determine how many emails should be set to in-review status
//                $in_review_count = $count-$emailsToBeSent;
//
//                $this->stdout('Setting '.$in_review_count.' emails to in-review...');
//
//                // Update emails to in-review status
//                GroupEmailModel::where('group_email_id', '=', $group->group_email_id)
//                    ->where('status', '=', 'pending-sending')
//                    ->orderBy('email_id', 'asc')
//                    ->limit($in_review_count)
//                    ->update(['status' => GroupEmailGroupsModel::STATUS_IN_REVIEW]);
//
//            }
//            elseif ($group->compliance->compliance_status=='approved')
//            {
//                // Update emails to pending-sending status if this Group is no longer under review
//                GroupEmailModel::where('group_email_id', '=', $group->group_email_id)->where('status', '=', 'in-review')
//                    ->update(['status' => GroupEmailGroupsModel::STATUS_PENDING_SENDING]);
//            }
//        }
//    }

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
        $options->emails_at_once = 100;
        $options->change_server_at = 1000;
        $options->compliance_limit = 2000;
        $options->compliance_abuse_range = .01;
        $options->compliance_unsub_range = .01;
        $options->compliance_bounce_range = .01;
        $options->groups_in_parallel = 5;
        $options->group_emails_in_parallel = 50;

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

        $sql = 'select `c`.`status` as `status` from `mw_group_email_groups` inner join `mw_customer` as `c` on `c`.`customer_id` = `mw_group_email_groups`.`customer_id` where `group_email_id` = '.$this->_group->group_email_id;

      $customer =  DB::select(DB::raw($sql));

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

//        GroupEmailGroupsModel::where('group_email_id', $id)
//            ->update(['status' => $status]);

        $sql = 'UPDATE mw_group_email_groups SET status = "'.$status.'" WHERE group_email_id = '.$id;

        DB::select(DB::raw($sql));

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

        $sql = 'UPDATE mw_group_email_groups SET finished_at = "'.$endTime.'" WHERE group_email_id = '.$id;

               DB::select(DB::raw($sql));

//        GroupEmailGroupsModel::where('group_email_id', $id)
//            ->update(['finished_at' => $endTime]);

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
    protected function findEmailsForSending($group, $limit, $offset)
    {

        //Server is set to UTC + 10 minutes???
        $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone('Etc/UTC'));

        //Set user timezone to EST for the time being
        $date->setTimezone(new \DateTimeZone('EST'));

        //fix the 10 minute difference
        $date->sub(new \DateInterval('PT10M'));

        $now = $date->format('Y-m-d H:i:s');

        $this->stdout('Now: '.$now);

$sql = 'SELECT * FROM mw_group_email WHERE status = "pending-sending" AND group_email_id = '.$group->group_email_id.' LIMIT '.$offset.', '.$limit;
        $emails = DB::select(DB::raw($sql));

        $this->stdout('SQL: '.$sql);
//        $emails = GroupEmailModel::where('status', '=', 'pending-sending')
//            ->where('group_email_id', '=', $group->group_email_id)
//            ->take($limit)
//            ->skip($offset)
//            ->get()
//            ->toArray();

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

              $results =  DB::select(DB::raw($sql));

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

        GroupEmailModel::where('email_uid', $mail['email_uid'])
            ->update(['status' => $status, 'last_updated' => new \DateTime()]);
    }

    /**
     * This method calls Impressionwise.com and checks for a valid email
     *
     * @param $email
     * @return bool
     */
    protected function checkImpressionWise($email)
    {

        $this->stdout('Checking email via ImpressionWise');

        $client = new Client();

        $res = $client->request('POST',
            'http://post.impressionwise.com/fastfeed.aspx?code=D39002&pwd=M4k3Th&email='.$email['to_email']);

        $this->stdout('ImpressionWise status code '.$res->getStatusCode());

        parse_str($res->getBody(), $get_array);

        $this->stdout('ImpressionWise body '.$get_array['result']);

        $canSend = ['CERTDOM', 'CERTINT'];

        if (in_array($get_array['result'], $canSend))
        {
            $this->stdout('Impressionwise passed');
            return true;
        }
        else
        {
            $this->stdout('Impressionwise blocked');
            $this->updateGroupEmailStatus($email, GroupEmailGroupsModel::STATUS_BLOCKED);
        }

        return false;

    }

    /**
     * This method is a test for sending using swiftmailer. It was slower than phpmailer
     * @param $emailsCount
     * @param $emails
     * @param $group
     */
    protected function sendBySwiftMailer($emailsCount, $emails, $group)
    {

        try
        {

            Mail::getSwiftMailer()->registerPlugin(new Swift_Plugins_AntiFloodPlugin(1300, 10));

            $this->stdout('Entering the foreach processing loop for all '.$emailsCount.' emails...');

            foreach ($emails as $index => $mail)
            {


                $this->stdout("", false);
                $this->stdout(sprintf("%s - %d/%d - group %d", $mail['to_email'], ($index+1), $emailsCount,
                    $group->group_email_id));

                $data = ['body' => $mail['body']];

                $mail['group_email_id'] = $group->group_email_id;
                $mail['customer_id'] = $group->customer_id;

                Mail::send('emails.main', $data, function ($message) use ($mail)
                {

                    $message->from($mail['from_email'], $mail['from_name']);
                    $message->to($mail['to_email'], $mail['from_name'])->subject($mail['subject']);

                    $headers = $message->getHeaders();
                    $headers->addTextHeader('X-Mw-Group-id', $mail['group_email_id']);
                    $headers->addTextHeader('X-Mw-Customer-Id', $mail['customer_id']);
                });

                $this->logGroupEmailDelivery($mail['email_uid']);

                $this->updateGroupEmailStatus($mail, GroupEmailGroupsModel::STATUS_SENT);

            }

        } catch (\Exception $e)
        {

            $this->stdout(sprintf('Exception thrown: %s', $e->getMessage()));

            // exception code to be returned later
            $code = (int)$e->getCode();

            // make sure sending is resumed next time.
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENDING);

            // return the exception code
            print_r($code);
        }
    }

//    protected function sendByPHPMailer2($emails, $emailsCount, $group, $server)
//    {
//
//        $mail = New \PHPMailer();
//        $mail->SMTPKeepAlive = true;
//
//        $mail->isSMTP();
//        $mail->CharSet = "utf-8";
//        $mail->SMTPAuth = true;
//        $mail->SMTPSecure = "tls";
//        $mail->Host = gethostbyname($server[0]['hostname']);
//        $mail->Port = 2525;
//        $mail->Username = $server[0]['username'];
//        $mail->Password = base64_decode($server[0]['password']);
//        $mail->Sender = Helpers::findBounceServerSenderEmail($server[0]['bounce_server_id']);
//
//        foreach ($emails as $index => $email)
//        {
//
////            if ($this->isBlacklisted($email['to_email'], $email['customer_id']))
////            {
////                $this->stdout('Email '.$email['to_email'].' is blacklisted for this customer id!');
////                $this->updateGroupEmailStatus($email['email_uid'], 'sent');
////                continue;
////
////            }
//
//            $this->stdout("", false);
//            $this->stdout(sprintf("%s - %d/%d - group %d", $email['to_email'], ($index+1), $emailsCount,
//                $group->group_email_id));
//
//            $mail->addCustomHeader('X-Mw-Group-Id', $group->group_email_id);
//            $mail->addCustomHeader('X-Mw-Customer-Id', $group->customer_id);
//            $mail->addCustomHeader('X-Mw-Email-Uid', $email['email_uid']);
//
//            $mail->addReplyTo($email['from_email'], $email['from_name']);
//            $mail->setFrom($email['from_email'], $email['from_name']);
//            $mail->addAddress($email['to_email'], $email['to_name']);
//
//            $mail->Subject = $email['subject'];
//            $mail->MsgHTML($email['body']);
//            $mail->AltBody = $email['plain_text'];
//
//            if (!$mail->send())
//            {
////                $this->logGroupEmailDelivery($email['email_uid'], $mail->ErrorInfo);
//                $this->stdout('ERROR Sending group email to '.$email['to_email'].'!');
//                $this->stdout('ERROR '.$mail->ErrorInfo.'!');
//            }
//            else
//            {
////                $this->logGroupEmailDelivery($email['email_uid'], 'OK');
//                $this->stdout('Sent group email  to '.$email['to_email'].'!');
//            }
//
//            $this->updateGroupEmailStatus($email);
//
//            $mail->clearAddresses();
//            $mail->clearAttachments();
//            $mail->clearCustomHeaders();
//
//        }
//
//        $mail->SmtpClose();
//
//    }

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
            $options = $this->getOptions();

            if ($options->scrub)
            {
                $this->checkImpressionWise($email);
            }

            $this->stdout("", false);
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
            $mail->AltBody = $email['plain_text'];

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
    }

}