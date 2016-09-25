<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Jobs\Job;
use App\Logger;
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmail extends Job implements ShouldQueue
{

    use InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $EmailGroup;

    /**
     * SendEmail constructor.
     * @param GroupEmailModel $EmailGroup
     */
    public function __construct(GroupEmailModel $EmailGroup)
    {

        $this->EmailGroup = $EmailGroup;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $data = $this->EmailGroup;
        $this->sendByPHPMailer($data);
    }

    /**
     * @param $data
     * @return void
     *
     */
    public function sendByPHPMailer($data)
    {

        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();

        if (empty($server))
        {
            $this->updateGroupEmailStatus($data, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
            return;
        }

        try
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


            $mail->addCustomHeader('X-Mw-Customer-Id', $data['customer_id']);
            $mail->addCustomHeader('X-Mw-Email-Uid', $data['email_uid']);
            $mail->addCustomHeader('X-Mw-Group-Id', $data['group_email_id']);

            $mail->addReplyTo($data['from_email'], $data['from_name']);
            $mail->setFrom($data['from_email'], $data['from_name']);
            $mail->addAddress($data['to_email'], $data['to_name']);

            $mail->Subject = $data['subject'];
            $mail->MsgHTML($data['body']);

            if (!$mail->send())
            {
                $this->updateGroupEmailStatus($data, GroupEmailGroupsModel::STATUS_FAILED_SEND);
                Logger::addProgress('(SendEmailFromQueue) Failed for Email ID '.print_r($data['email_uid'], true),
                    '(SendEmailFromQueue) Failed for Email ID');
            }
            else
            {
                $this->updateGroupEmailStatus($data, GroupEmailGroupsModel::STATUS_SENT);
            }

            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

            $this->delete();

            return \Response::json(['type' => 'success'], 200);

        } catch (\Exception $e)
        {
            $this->updateGroupEmailStatus($data, GroupEmailGroupsModel::STATUS_FAILED_ERROR);
            Logger::addProgress('(SendEmailFromQueue) Failed for Email ID '.print_r($data['email_uid'].' Error:'.print_r($e),
                    true),
                '(SendEmailFromQueue) Failed for Email ID');
        }
    }

    /**
     * @param $mail
     * @param $status
     */
    protected function updateGroupEmailStatus($mail, $status)
    {

        GroupEmailModel::where('email_uid', $mail['email_uid'])
            ->update(['status' => $status, 'last_updated' => new \DateTime()]);
    }
}
