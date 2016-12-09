<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailBounceModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;
use App\Models\PauseGroupEmailModel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use RdKafka\Conf;
use RdKafka\Producer;

class SendEmail extends Job implements ShouldQueue
{

    use InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $data;

    /**
     * SendEmail constructor.
     * @param $data
     */
    public function __construct($data)
    {

        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {

        $this->sendByPHPMailer($this->data);
    }

    /**
     * @param $data
     * @return bool
     *
     */
    public function sendByPHPMailer($data)
    {

        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();

        if (property_exists($data, 'group_email_id'))
        {
            $group_email_id = $data->group_email_id;
        }
        else
        {
            $group_email_id = '';
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
            $mail->Host = $server[0]['hostname'];
            $mail->Port = 2525;
            $mail->Username = $server[0]['username'];
            $mail->Password = base64_decode($server[0]['password']);
            $mail->Sender = Helpers::findBounceServerSenderEmail($server[0]['bounce_server_id']);

            $mail->addCustomHeader('X-Mw-Customer-Id', $data->customer_id);
            $mail->addCustomHeader('X-Mw-Email-Uid', $data->email_uid);
            $mail->addCustomHeader('X-Mw-Group-Id', $group_email_id);

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
            // save status error if try/catch returns error
            $status = 'error';

        }
        $this->delete();

        $update = GroupEmailModel::find($data->email_id);
        $update->status = $status;
        $update->last_updated = new \DateTime();
        $update->save();

    }

}
