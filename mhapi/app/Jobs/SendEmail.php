<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Logger;
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailBounceLogModel;
use App\Models\GroupEmailBounceModel;
use App\Models\GroupEmailModel;
use App\Models\PauseGroupEmailModel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;

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
print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
            $this->sendByPHPMailer($this->data);
print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
    }

    /**
     * @param $data
     * @return bool
     *
     */
    public function sendByPHPMailer($data)
    {
print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();

//        if(GroupEmailBounceModel::where('email', '=', $data->to_email)->count() > 0)
//                     {
//                         print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
//
//                                   return false;
//                     }
        /*
         * Check for paused customers or groups
         */
        $pause = false;
        $Pause = PauseGroupEmailModel::where('group_email_id','=',$data->group_email_id)->orWhere('customer_id', '=', $data->customer_id)->get();

        if (!empty($Pause[0]))
        {
            if ($Pause->pause_customer==true||$Pause->group_email_id>0)
            {
                $pause = true;
            }
        }
print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
        /*
         * Save email
         */
        $Email = new GroupEmailModel();
        $Email->email_uid = $data->email_uid;
        $Email->to_name = $data->to_name;
        $Email->to_email = $data->to_email;
        $Email->from_name = $data->from_name;
        $Email->from_email = $data->from_email;
        $Email->reply_to_name = $data->reply_to_name;
        $Email->reply_to_email = $data->reply_to_email;
        $Email->subject = $data->subject;
        $Email->body = $data->body;
        $Email->plain_text = $data->plain_text;
        $Email->send_at = $data->send_at;
        $Email->customer_id = $data->customer_id;
        $Email->group_email_id = $data->group_email_id;
        $Email->date_added = $Email->last_updated  = new \DateTime();
        $Email->max_retries = 5;
print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
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
            $mail->addCustomHeader('X-Mw-Group-Id', $data->group_email_id);

            $mail->addReplyTo($data->from_email, $data->from_name);
            $mail->setFrom($data->from_email, $data->from_name);
            $mail->addAddress($data->to_email, $data->to_name);

            $mail->Subject = $data->subject;
            $mail->MsgHTML($data->body);

print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

            if ($pause == true)
            {
                $Email->status = 'paused';
            }
//            elseif($bounce == true)
//            {
//                $Email->status = 'bounced';
//            }
            elseif (!$mail->send())
            {
                // save status failed if mail did not send
print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
                $Email->status = 'failed';
            }
            else
            {
                // save status sent if mail DID send
                $Email->status = 'sent';
            }
            $Email->save();
       	    $this->delete();

            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
        } catch (\Exception $e)
        {
print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');   
//print_r($e);
         // save status error if try/catch returns error
            $Email->status = 'error';
            $Email->save();
       		$this->delete();


        }

    }

}
