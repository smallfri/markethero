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
use App\Models\BounceServer;
use App\Models\DeliveryServerModel;
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
use phpseclib\Crypt\AES;
use Swift_Plugins_AntiFloodPlugin;


class SendGroupsCommand extends Command
{

    protected $_cipher;

    protected $_plainTextPassword;

    protected $signature = 'send-groups';

    protected $description = 'Sends Group Emails';

    protected $_group;

    // flag
    protected $_restoreStates = true;

    // flag
    protected $_improperShutDown = false;

    // global command arguments

    // what type of campaigns this command is sending
    public $groups_type;

    // how many campaigns to process at once
    public $groups_limit = 0;

    // from where to start
    public $groups_offset = 0;

    // whether this should be verbose and output to console
    public $verbose = 1;

    // since 1.3.5.9 - whether we should send in parallel using pcntl, if available
    // this is a temporary flag that should be removed in future versions
    public $use_pcntl = false;

    // since 1.3.5.9 - if parallel sending, how many campaigns at same time
    // this is a temporary flag that should be removed in future versions
    public $campaigns_in_parallel = 1;

    // since 1.3.5.9 -  if parallel sending, how many subscriber batches at same time
    // this is a temporary flag that should be removed in future versions
    public $subscriber_batches_in_parallel = 3;

    public $options;

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
    }

    public function _handleExternalSignal($signalNumber)
    {

        // this will trigger all the handlers attached via register_shutdown_function
        $this->_improperShutDown = true;
        exit;
    }

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

        $result = $this->process();

        return $result;
    }

    protected function process()
    {

        $options = $this->getOptions();

        $statuses = array(GroupEmailGroupsModel::STATUS_SENDING, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
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

        $groups = GroupEmailGroupsModel::whereIn('status', $statuses)->get();

        //handle compliance

//        $this->complianceHandler($groups);

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

            $emails = $this->countEmails($group['group_email_id']);

            if ($emails==0&&strtotime($group['date_added'])<strtotime('+10 minutes'))
            {
                $this->stdout('No emails found to be ready for sending, setting group status '.$group['group_email_id'].' to pending-sending.');
                $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
                continue;
            }
            $groupIds[] = $group['group_email_id'];

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

        $statuses = array(GroupEmailGroupsModel::STATUS_SENDING, GroupEmailGroupsModel::STATUS_PENDING_SENDING);

        $group = GroupEmailGroupsModel::find($groupId);

        $this->_group = $group;

        if (empty($group)||!in_array($group->status, $statuses))
        {
            $this->stdout(sprintf("The Group with ID: %d is not ready for processing.", $groupId));
            $this->updateGroupStatus($groupId, GroupEmailGroupsModel::STATUS_SENT);

            Logger::addProgress(sprintf("The Group with ID: %d is not ready for processing.", $groupId),'Group Not Ready');

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
        $group = GroupEmailGroupsModel::find($this->_group->group_email_id);
        $group->status = GroupEmailGroupsModel::STATUS_PROCESSING;

        if ($group->started_at==null)
        {
            $group->started_at = new \DateTime;
        }
        $group->save();

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
                sleep(1);
            }
        }

        if (!$handled)
        {
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


        $this->stdout(sprintf("Looking for emails for group with id %s...(This is email worker #%d)",
            $group->group_email_id, $workerNumber));

        $this->stdout('limit '.$limit.' offset '.$offset);

        $emails = $this->findEmailsForSending($group, $limit, $offset);

        $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_PROCESSING);

        $this->stdout(sprintf("This emails worker(#%d) will process %d emails for this group...", $workerNumber,
            count($emails)));

        // run some cleanup on subscribers
        $notAllowedEmailChars = array('-', '_');
        $emailsQueue = array();

        $this->stdout("Running email cleanup...");

        foreach ($emails as $index => $email)
        {
            if (isset($emailsQueue[$email['email_id']]))
            {
                unset($emails[$index]);
                continue;
            }

            $containsNotAllowedEmailChars = false;
            $part = explode('@', $email['to_email']);
            $part = $part[0];
            foreach ($notAllowedEmailChars as $chr)
            {
                if (strpos($part, $chr)===0||strrpos($part, $chr)===0)
                {
                    $this->addToBlacklist($email, $group->customer_id);

                    $containsNotAllowedEmailChars = true;
                    break;
                }
            }

            if ($containsNotAllowedEmailChars)
            {
                unset($email[$index]);
                continue;
            }

            $emailsQueue[$email['email_id']] = true;
        }
        unset($emailsQueue);

        // reset the keys
        $emails = array_values((array)$emails);

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

        $this->sendByPHPMailer2($emails, $emailsCount, $group, $server);

        $emailsRemaining = GroupEmailModel::where('group_email_id', '=', $group->group_email_id)
            ->where('status', '=', 'pending-sending')
            ->count();

        if ($emailsRemaining==0)
        {
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENT);
            $this->stdout('Group has been marked as sent!');
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

    protected function complianceHandler($groups)
    {

        foreach ($groups AS $group)
        {

            $this->stdout('Starting Compliance Handler');

            $group->compliance = GroupEmailComplianceModel::find($group->group_email_id);

            if (empty($group->compliance))
            {
                $this->stdout('Missing compliance entry in table...');
                continue;
            }

            $group->compliance->compliance_levels
                = GroupEmailComplianceLevelsModel::find($group->compliance->compliance_level_type_id);

            $count = GroupEmailModel::where('group_email_id', '=', $group->group_email_id)->count();

            $options = $this->getOptions();

            if ($group->compliance->compliance_status=='in-review' AND $count>=$options->compliance_limit)
            {

                $this->stdout('This Group is in Compliance Review...');

                $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_COMPLIANCE_REVIEW);

                // Set emails to be sent = threshold X count
                $emailsToBeSent = ceil($count*$group->compliance->compliance_levels->threshold);

                $this->stdout('There are '.$emailsToBeSent.' emails to be sent...');


                // Determine how many emails should be set to in-review status
                $in_review_count = $count-$emailsToBeSent;

                $this->stdout('Setting '.$in_review_count.' emails to in-review...');

                // Update emails to in-review status
                GroupEmailModel::where('group_email_id', '=', $group->group_email_id)
                    ->where('status', '=', 'pending-sending')
                    ->orderBy('email_id', 'asc')
                    ->limit($in_review_count)
                    ->update(['status' => GroupEmailGroupsModel::STATUS_IN_REVIEW]);

            }
            elseif ($group->compliance->compliance_status=='approved')
            {
                // Update emails to pending-sending status if this Group is no longer under review
                GroupEmailModel::where('group_email_id', '=', $group->group_email_id)->where('status', '=', 'in-review')
                    ->update(['status' => GroupEmailGroupsModel::STATUS_PENDING_SENDING]);
            }
        }
    }

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
    protected function sortEmails($emails)
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
                    break;
                }
            }
            $emailsCount--;
        }
        return $emails;
    }

    protected function getOptions()
    {

        $options = new \stdClass();

        $options->id = 1;
        $options->emails_at_once = 100;
//        $options->emails_per_minute = 200;
        $options->change_server_at = 1000;
        $options->compliance_limit = 50000;
        $options->memory_limit = 3000;
        $options->compliance_abuse_range = .01;
        $options->compliance_unsub_range = .01;
        $options->compliance_bounce_range = .01;
        $options->groups_in_parallel = 5;
        $options->group_emails_in_parallel = 15;

//        $options = GroupControlsModel::find(1);

        return $options;

    }

    protected function getCustomerStatus()
    {

        $customer = GroupEmailGroupsModel::select('c.status AS status')
            ->where('group_email_id', '=', $this->_group->group_email_id)
            ->join('mw_customer AS c', 'c.customer_id', '=', 'mw_group_email_groups.customer_id')
            ->get();

        if (!empty($customer))
        {
            return $customer[0]['status'];
        }
        return false;

    }

    public function logGroupEmailDelivery($emailId, $message = 'OK')
    {

        GroupEmailLogModel::insert([
            'email_uid' => $emailId,
            'message' => $message,
            'date_added' => new \DateTime()
        ]);
        return;

    }

    protected function getCanUsePcntl()
    {

        if (!$this->functionExists('pcntl_fork')||!$this->functionExists('pcntl_waitpid'))
        {
            return false;
        }

        return true;
    }

    protected function getGroupsInParallel()
    {

        $options = $this->getOptions();

        $this->stdout('Groups in Parallel '.$options->groups_in_parallel);

        return $options->groups_in_parallel;
    }

    protected function getEmailBatchesInParallel()
    {

        $options = $this->getOptions();

        $this->stdout('Batches in Parallel '.$options->group_emails_in_parallel);


        return $options->group_emails_in_parallel;
    }

    protected function updateGroupStatus($id, $status)
    {

        GroupEmailGroupsModel::where('group_email_id', $id)
            ->update(['status' => $status]);

        return;
    }

    protected function updateGroupStartTime($id, $startTime)
    {

        GroupEmailGroupsModel::where('group_email_id', $id)
            ->update(['started_at' => $startTime]);

        return;
    }

    protected function updateGroupEndTime($id, $endTime)
    {

        GroupEmailGroupsModel::where('group_email_id', $id)
            ->update(['finished_at' => $endTime]);

        return;
    }

    /**
     * @param $group
     * @param $limit
     * @param $offset
     * @return mixed
     */
    protected function findEmailsForSending($group, $limit, $offset)
    {

        $emails = GroupEmailModel::where('status', '=', 'pending-sending')
            ->where('group_email_id', '=', $group->group_email_id)
            ->take($limit)
            ->skip($offset)
            ->get()
            ->toArray();

//            $emails
//                = DB::select(DB::raw('SELECT * FROM mw_group_email WHERE status = "pending-sending" AND group_email_id = '.$group->group_email_id.' LIMIT '.$limit.' OFFSET '.$offset));
        return $emails;

    }

    protected function groupIsFinished($group)
    {

        $count = GroupEmailModel::where('status', '=', 'sent')
            ->where('group_email_id', '=', $group->group_email_id)
            ->count();

        return $count;
    }

    protected function countEmails($group_email_id)
    {

        $count = GroupEmailModel::where('status', '=', 'pending-sending')
            ->where('group_email_id', '=', $group_email_id)
            ->count();

        return $count;
    }

    /**
     * @param $email
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
     * @param $mail
     * @param $status
     */
    protected function updateGroupEmailStatus($mail, $status = GroupEmailGroupsModel::STATUS_SENT)
    {

        GroupEmailModel::where('email_uid', $mail['email_uid'])
            ->update(['status' => $status, 'last_updated' => new \DateTime()]);
    }

    protected function checkImpressionWise($email)
    {

        $this->stdout('Checking email via ImpressionWise');

        $client = new Client();

        $res = $client->request('POST',
            'http://post.impressionwise.com/fastfeed.aspx?code=D39002&pwd=M4k3Th&email='.$email['from_email']);

        $this->stdout('ImpressionWise status code '.$res->getStatusCode());

        parse_str($res->getBody(), $get_array);

        $this->stdout('ImpressionWise body '.$get_array['result']);

        $canSend = ['CERTDOM', 'CERTINT', 'KEY', 'QUARANTINE', 'NETPROTECT'];

        if (in_array($get_array['result'], $canSend))
        {
            return true;
        }

        if ($get_array['result']=='RETRY')
        {
            $this->updateGroupEmailStatus($email, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
        }

        return false;

    }

    /**
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

                $this->updateGroupEmailStatus($mail);

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

    /**
     * @param $emails
     * @param $emailsCount
     * @param $group
     */
    protected function sendByPHPMailer($emails, $emailsCount, $group)
    {

        $mail = New \PHPMailer();
        $mail->isSMTP();
        $mail->SMTPKeepAlive = true;

        foreach ($emails as $index => $email)
        {

            $this->stdout("", false);
            $this->stdout(sprintf("%s - %d/%d - group %d", $email['to_email'], ($index+1), $emailsCount,
                $group->group_email_id));

            $email['group_email_uid'] = $group->group_email_uid;
            $email['customer_id'] = $group->customer_id;

            try
            {
                $mail->isSMTP();
                $mail->CharSet = "utf-8";
                $mail->SMTPAuth = true;
                $mail->SMTPSecure = "tls";
                $mail->Host = "markethero.smtp.com";
                $mail->Port = 2525;
                $mail->Username = "chuck@markethero.io";
                $mail->Password = "market-hero";
                $mail->setFrom("russell@smallfri.com", "Firstnameeoooo Lastname");
                $mail->Subject = "Test";
                $mail->MsgHTML($email['body']);
                $mail->addAddress($email['to_email'], $email['to_name']);
                $mail->send();

                $this->logGroupEmailDelivery($email['email_uid']);

                $this->updateGroupEmailStatus($email);

            } catch (\phpmailerException $e)
            {
                dd($e);
            } catch (\Exception $e)
            {
                dd($e);
            }

        }

        $mail->SmtpClose();
    }

    protected function sendByPHPMailer2($emails, $emailsCount, $group, $server)
    {

        $mail = New \PHPMailer();
        $mail->SMTPKeepAlive = true;

        $mail->isSMTP();
        $mail->CharSet = "utf-8";
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";
        $mail->Host = $server[0]['hostname'];
        $mail->Port = 2525;
        $mail->Username = $server[0]['username'];
        $mail->Password = base64_decode($server[0]['password']);
        $mail->Sender = Helpers::findBounceServerSenderEmail($server[0]['bounce_server_id']);

        foreach ($emails as $index => $email)
        {

            if ($this->isBlacklisted($email['to_email'], $email['customer_id']))
            {
                $this->stdout('Email '.$email['to_email'].' is blacklisted for this customer id!');
                $this->updateGroupEmailStatus($email['email_uid'], 'sent');
                continue;

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

            $this->updateGroupEmailStatus($email);

            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

        }

        $mail->SmtpClose();

    }

    public function sendByPHPMailer3($emails, $emailsCount, $group, $server)
    {

        $mail = new \PHPMailer;

        $mail->isSMTP();

        $mail->SMTPAuth = true;

        $mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead

        $mail->SMTPSecure = "tls";
        $mail->Host = $server[0]['hostname'];
        $mail->Port = 2525;
        $mail->Username = $server[0]['username'];
        $mail->Password = base64_decode($server[0]['password']);
        $mail->Sender = Helpers::findBounceServerSenderEmail($server[0]['bounce_server_id']);

        foreach ($emails as $index => $email)
        {
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
            $mail->AltBody = $email['email'];

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

            $this->updateGroupEmailStatus($email);
            // Clear all addresses and attachments for next loop
            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

        }
    }

}