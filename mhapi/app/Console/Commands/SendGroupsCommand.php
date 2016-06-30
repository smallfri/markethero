<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;


use App\BlacklistModel;
use App\DeliveryServerModel;
use App\GroupControlsModel;
use App\GroupEmailGroupsModel;
use App\GroupEmailLogModel;
use App\GroupEmailModel;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Console\Command;
use Mail;


class SendGroupsCommand extends Command
{

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
//          Yii::app()->attachEventHandler('onError', array($this, '_restoreStates'));
//          Yii::app()->attachEventHandler('onException', array($this, '_restoreStates'));

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
        $limit = (int)$options['groups_at_once'];

        if ($this->groups_type!==null&&!in_array($this->groups_type, $types))
        {
            $this->groups_type = null;
        }

        if ((int)$this->groups_limit>0)
        {
            $limit = (int)$this->groups_limit;
        }

        $groups = GroupEmailGroupsModel::whereIn('status', $statuses)
            ->take($limit)
            ->skip($this->groups_offset)
            ->get();

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
                5,
                4
            ));
        }

        $groupIds = array();
        foreach ($groups as $group)
        {
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
            print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

            $handled = true;

            // make sure we close the database connection

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
//                  $this->sendCampaignStep1($groupId, 0);
                $this->stdout('Sending Group '.$groupId.'...');

                $this->direct_send($groupId);

            }

        }
    }

    public function direct_send($groupId)
    {

        $emails = GroupEmailModel::where('group_email_id', '=', $groupId)->take(10)->get();
        $index = 1;
        foreach ($emails AS $mail)
        {
            $this->stdout('Sending '.$index.' of '.count($emails).'...');
            $this->stdout('Sending email to '.$mail->to_email.'...');

            $data = ['body' => $mail->body, 'subject' => $mail->subject];


            Mail::queue('emails.main', $data, function ($message) use ($mail)
            {

                $message->from($mail->from_email, $mail->from_name);
                $message->to($mail->to_email, $mail->name)->subject($mail->subject);

            });

            $this->logGroupEmailDelivery($mail->primaryKey);

            $index++;
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
            return 1;
        }

        $options = $this->getOptions();

        if ($this->getCustomerStatus()=='inactive')
        {
            $this->updateGroupStatus($this->primaryKey, GroupEmailGroupsModel::STATUS_PAUSED);

            $this->stdout("This customer is inactive!");
            return 1;
        }

        $dsParams = array('customerCheckQuota' => false, 'useFor' => array(DeliveryServerModel::USE_FOR_ALL));
        $server = DeliveryServerModel::find(5);
        if (empty($server))
        {
            $this->stdout('Cannot find a valid server to send the group email, aborting until a delivery server is available!');
            return 1;
        }

        $this->stdout('Changing the group status into PROCESSING!');

        // put proper status
        $group = GroupEmailGroupsModel::find($this->_group->group_email_id);
        $group->status = GroupEmailGroupsModel::STATUS_SENDING;
        $group->save();
        // find the subscribers limit
        $limit = (int)$options['emails_at_once'];

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

        $this->stdout(sprintf("Looking for emails for group with id %s...(This is email worker #%d)",
            $group->group_email_id, $workerNumber));

        $this->stdout('limit '.$limit.' offset '.$offset);

        $emails = $this->findEmailsForSending($group, $limit, $offset);

//        $emails = (array)$emails;

        $this->stdout(sprintf("This emails worker(#%d) will process %d emails for this group...", $workerNumber,
            count($emails)));

        // run some cleanup on subscribers
        $notAllowedEmailChars = array('-', '_');
        $emailsQueue = array();

//        $this->stdout("Running email cleanup...");
//
//        foreach ($emails as $index => $email)
//        {
//            if (isset($emailsQueue[$email->email_id]))
//            {
//                unset($emails[$index]);
//                continue;
//            }
//
//            $containsNotAllowedEmailChars = false;
//            $part = explode('@', $email->to_email);
//            $part = $part[0];
//            foreach ($notAllowedEmailChars as $chr)
//            {
//                if (strpos($part, $chr)===0||strrpos($part, $chr)===0)
//                {
//                    $this->addToBlacklist($email);
//
//                    $containsNotAllowedEmailChars = true;
//                    break;
//                }
//            }
//
//            if ($containsNotAllowedEmailChars)
//            {
//                unset($email[$index]);
//                continue;
//            }
//
//            $emailsQueue[$email->email_id] = true;
//        }
//        unset($emailsQueue);
//
//        // reset the keys
//        $emails = array_values((array)$emails);
        $emailsCount = count($emails);

        $this->stdout(sprintf("Checking emails count after cleanup: %d", $emailsCount));

        if (empty($emails))
        {
            if ($canChangeGroupStatus)
            {
                $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENT);
            }
            return 0;
        }

        $this->stdout('Sorting emails...');

        // sort emails
//        $emails = $this->sortEmails($emails);

        try
        {

            $this->stdout('Entering the foreach processing loop for all '.$emailsCount.' emails...');

            foreach ($emails as $index => $mail)
            {

                $this->stdout("", false);
                $this->stdout(sprintf("%s - %d/%d - group %d", $mail->to_email, ($index+1), $emailsCount,
                    $group->group_email_id));

                $data = ['body' => $mail->body];


                $mail->group_email_uid = $group->group_email_uid;
                $mail->customer_id = $group->customer_id;


                Mail::send('emails.main', $data, function ($message) use ($mail)
                {

                    $message->from($mail->from_email, $mail->from_name);
                    $message->to($mail->to_email, $mail->name)->subject($mail->subject);

                    $headers = $message->getHeaders();
                    $headers->addTextHeader('X-Mw-Group-Uid', $mail->group_email_uid);
                    $headers->addTextHeader('X-Mw-Customer-Id', $mail->customer_id);

                });

                $this->logGroupEmailDelivery($mail->email_uid);

                $this->updateGroupEmailStatus($mail);

            }

        } catch (Exception $e)
        {

            $this->stdout(sprintf('Exception thrown: %s', $e->getMessage()));

            // exception code to be returned later
            $code = (int)$e->getCode();

            // make sure sending is resumed next time.
            $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_SENDING);

            // return the exception code
            return $code;
        }

        $this->stdout("", false);
        $this->stdout(sprintf('Done processing %d emails!', count($emails)));

        $this->stdout('Done processing the group.');


        return 0;
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
            $emailParts = explode('@', $email[0]->to_email);
            $domainName = $emailParts[1];
            if (!isset($_emails[$domainName]))
            {
                $_emails[$domainName] = array();
            }
            $_emails[$domainName][] = $email;
            unset($emails[$index]);
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

        $options = GroupControlsModel::find(1);

        return $options;
    }

    protected function getCustomerStatus()
    {

        $customer = GroupEmailGroupsModel::select('c.status AS status')
            ->where('group_email_id', '=', $this->_group->group_email_id)
            ->join('mw_customer AS c', 'c.customer_id', '=', 'mw_group_email_groups.customer_id')
            ->get();
        return $customer[0]['status'];

    }

    public function logGroupEmailDelivery($emailId)
    {

        GroupEmailLogModel::insert([
            'email_uid' => $emailId,
            'message' => 'OK',
            'date_added' => new \DateTime()
        ]);
    }

    protected function updateGroupStatus($id, $status)
     {
 
         GroupEmailGroupsModel::where('group_email_id', $id)
             ->update(['status' => $status]);
     }
 
     /**
      * @param $group
      * @param $limit
      * @param $offset
      * @return mixed
      */
     protected function findEmailsForSending($group, $limit, $offset)
     {
 
         $emails = GroupEmailModel::select('mw_group_email.to_email', 'mw_group_email.from_email', 'mw_group_email.body',
             'mw_group_email.subject', 'mw_group_email.from_name', 'mw_group_email.to_name', 'mw_group_email.email_uid')
             ->where('status', '=', 'pending-sending')
             ->where('group_email_id', '=', $group->group_email_id)
             ->whereNull('logs.email_uid')
             ->leftJoin('mw_group_email_log AS logs', 'logs.email_uid', '=', 'mw_group_email.email_uid')
             ->take($limit)
             ->skip($offset)
             ->get();
         return $emails;
     }
 
     /**
      * @param $email
      */
     protected function addToBlacklist($email)
     {
 
         $blackList = new BlacklistModel();
 
         $blackList->email_id = $email->primaryKey;
         $blackList->reason = 'Invalid email address format!';
         $blackList->date_added = new \DateTime();
         $blackList->Save();
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
     */
    protected function updateGroupEmailStatus($mail)
    {

        GroupEmailModel::where('email_uid', $mail->email_uid)
            ->update(['status' => GroupEmailGroupsModel::STATUS_SENT]);
    }

}