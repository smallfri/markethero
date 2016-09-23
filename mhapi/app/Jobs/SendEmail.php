<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Jobs\Job;
use App\Models\DeliveryServerModel;
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

    public function __construct(GroupEmailModel $EmailGroup)
    {

//        print_r($EmailGroup);
//        echo "here";exit;
        $this->EmailGroup = $EmailGroup;
    }

    /**
     * Execute the job.
     *
     * @param GroupEmailModel $EmailGroup
     */
    public function handle(GroupEmailModel $EmailGroup)
    {

//        $this->sendByPHPMailer();

        $data = $this->EmailGroup;
        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();

        if (empty($server))
        {
            return 1;
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


            //        $this->stdout('Sending transactional Emails to '.$data['to_email'].'!');

            if (!$mail->send())
            {
                echo "not sent";
                //                $this->updateTransactionalEmail($data['email_uid'], 'unsent');
                //                $this->logTransactionalEmailDelivery($data['email_id'], $mail->ErrorInfo);
                //            $this->stdout('ERROR Sending transactional Email to '.$data['to_email'].'!');
                //            $this->stdout('ERROR '.$mail->ErrorInfo.'!');
            }
            else
            {
                //            echo $data['to_email'];

                //                $this->updateTransactionalEmail($data['email_uid'], 'sent');
                //                $this->stdout('Sent transactional Email to '.$data['to_email'].'!');
                //                $this->logTransactionalEmailDelivery($data['email_id'], 'OK');
            }

            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

            $this->delete();

            return \Response::json(['type' => 'success'], 200);

        } catch (\Exception $e)
        {
            echo $e;
        }

//


    }

    protected function sendByPHPMailer()
    {

        $data = $this->EmailGroup;
        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();

        if (empty($server))
        {
            return 1;
        }

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


        //        $this->stdout('Sending transactional Emails to '.$data['to_email'].'!');

        if (!$mail->send())
        {
            echo "not sent";
//                $this->updateTransactionalEmail($data['email_uid'], 'unsent');
//                $this->logTransactionalEmailDelivery($data['email_id'], $mail->ErrorInfo);
            //            $this->stdout('ERROR Sending transactional Email to '.$data['to_email'].'!');
            //            $this->stdout('ERROR '.$mail->ErrorInfo.'!');
        }
        else
        {
            echo $data['to_email'];

//                $this->updateTransactionalEmail($data['email_uid'], 'sent');
//                $this->stdout('Sent transactional Email to '.$data['to_email'].'!');
//                $this->logTransactionalEmailDelivery($data['email_id'], 'OK');
        }

        $mail->clearAddresses();
        $mail->clearAttachments();
        $mail->clearCustomHeaders();


        $mail->SmtpClose();

    }
}
