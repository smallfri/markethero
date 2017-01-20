<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Logger;
use App\Models\Customer;
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;
use App\Models\PauseGroupEmailModel;
use App\Models\TransactionalEmailModel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use RdKafka\Conf;
use RdKafka\Producer;

class SendTransactionalEmail extends Job implements ShouldQueue
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

        $customer = Customer::find($data->customer_id);

        $server = DeliveryServerModel::find($customer->group_pool_id);

        if (empty($server))
        {
            $server = DeliveryServerModel::find(1);
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

        print_r($status);
        $this->delete();

        $this->replyToMarketHero($data);

        $update = TransactionalEmailModel::find($data->email_id);
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
        $rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");

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

}
