<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Logger;
use App\Models\BroadcastEmailModel;
use App\Models\Customer;
use App\Models\DeliveryServerModel;
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
        $customer = Customer::find($data->customerID);

        $server = DeliveryServerModel::find($customer->pool_group_id);

        if (empty($server))
        {
            $server = DeliveryServerModel::find(1);
        }

        if (isset($data->groupID)&&$data->groupID>1)
        {
            $groupID = $data->groupID;
        }
        else
        {
            $groupID = 1;
        }

        $pause = PauseGroupEmailModel::where('group_email_id', '=', $data->groupID)
            ->orWhere('customer_id', '=', $data->customerID)
            ->get();

        if (count($pause))
        {
            $pause = $pause[0];

            if (!empty($pause))
            {
                if ($pause->groupID==$data->groupID||$pause->pause_customer==1)
                {
                    $this->delete();

                    BroadcastEmailModel::where('emailUID', '=', $data->emailUID)
                        ->update('status', '=', BroadcastEmailModel::STATUS_PAUSED);
                    return false;
                }
            }
        }

        $hash = md5(strtolower(trim($data['group_id']). trim($data->to_email) . trim($data->body) . trim($data->subject)));

        $emailExist = BroadcastEmailModel::where('hash', '=', $hash)
            ->get();

        if (!$emailExist->isEmpty())
        {
            return false;
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

            $mail->addCustomHeader('X-Mw-Customer-Id', $data->customerID);
            $mail->addCustomHeader('X-Mw-Email-Uid', $data->emailUID);
            $mail->addCustomHeader('X-Mw-Group-Id', $groupID);
            if ($groupID==1)
            {
                $mail->addCustomHeader('X-Mw-Transactional-Id', $groupID);
            }

            $mail->addReplyTo($data->fromEmail, $data->fromName);
            $mail->setFrom($data->fromEmail, $data->fromName);
            $mail->addAddress($data->toEmail, $data->toName);

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
        $this->delete();

        $this->replyToMarketHero($data);

        $update = BroadcastEmailModel::find($data->emailID);
        $update->status = $status;
        $update->lastUpdated = new \DateTime();
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
            'emailOneEmailID' => $Email->emailUID,
            'sentDateTime' => date_format($date, 'U')
        ];

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message));
        //        var_dump(json_encode($message));
    }

}
