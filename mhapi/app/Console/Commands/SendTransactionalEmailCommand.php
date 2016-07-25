<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;


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
use App\Models\TransactionalEmailLogModel;
use App\Models\TransactionalEmailModel;
use App\Models\TransactionalEmailOptionsModel;
use DB;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use phpseclib\Crypt\AES;
use Swift_Plugins_AntiFloodPlugin;


class SendTransactionalEmailCommand extends Command
{

    protected $_cipher;

    protected $_plainTextPassword;

    protected $signature = 'send-transactional';

    protected $description = 'Sends Transactional Emails';

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

        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();
        if (empty($server))
        {
            $this->stdout('Cannot find a valid server to send the group email, aborting until a delivery server is available!');
            return 1;
        }

        $limit = 100;

        $options = TransactionalEmailOptionsModel::find(1);

        $emails = TransactionalEmailModel::where('status', '=', 'unsent')
            ->where('retries', '<', 3)
            ->limit($limit)
            ->offset($options->offset)
            ->get();

        if (count($emails) < 1)
        {
            TransactionalEmailOptionsModel::where('id','=',1)->update(['offset' => 0]);
            return;
        }

        $this->stdout('Getting ready to send transactional Emails!');

        TransactionalEmailOptionsModel::where('id', '=', 1)->update(['offset' => $limit+$options->offset]);

        $this->sendByPHPMailer2($emails, $server);

        $this->stdout('Cleaning up old transactional Emails!');

        TransactionalEmailModel::raw('DELETE FROM mw_transactional_email WHERE `status` = "unsent" AND send_at < NOW() AND date_added < DATE_SUB(NOW(), INTERVAL 1 MONTH)');

    }

    protected function sendByPHPMailer2($emails, $server)
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
                $this->updateTransactionalEmail($email['email_uid'], 'sent');
                continue;
            }

            $mail->addCustomHeader('X-Mw-Customer-Id', $email['customer_id']);
            $mail->addCustomHeader('X-Mw-Email-Uid', $email['email_uid']);
            $mail->addCustomHeader('X-Mw-Group-Id',0);

            $mail->addReplyTo($email['from_email'], $email['from_name']);
            $mail->setFrom($email['from_email'], $email['from_name']);
            $mail->addAddress($email['to_email'], $email['to_name']);

            $mail->Subject = $email['subject'];
            $mail->MsgHTML($email['body']);

            $this->stdout('Sending transactional Emails to '.$email['to_email'].'!');

            if (!$mail->send())
            {
                $this->updateTransactionalEmail($email['email_uid'], 'unsent');
                $this->logTransactionalEmailDelivery($email['email_id'], $mail->ErrorInfo);
                $this->stdout('ERROR Sending transactional Email to '.$email['to_email'].'!');
                $this->stdout('ERROR '.$mail->ErrorInfo.'!');
            }
            else
            {
                $this->updateTransactionalEmail($email['email_uid'], 'sent');
                $this->stdout('Sent transactional Email to '.$email['to_email'].'!');
                $this->logTransactionalEmailDelivery($email['email_id'], 'OK');
            }

            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

        }

        $mail->SmtpClose();

    }

    public function logTransactionalEmailDelivery($emailId, $message = 'OK')
    {

        TransactionalEmailLogModel::insert([
            'email_id' => $emailId,
            'message' => $message,
            'date_added' => new \DateTime()
        ]);
        return;

    }

    public function updateTransactionalEmail($emailId, $status)
    {

        TransactionalEmailModel::where('email_uid', $emailId)
            ->update(['status' => $status]);
        TransactionalEmailModel::where('email_uid', '=', $emailId)->increment('retries');

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

}