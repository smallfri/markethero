<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;

use App\Models\DeliveryServerModel;
use App\Models\GroupEmailBounceLogModel;
use App\Models\GroupEmailModel;
use App\Helpers\Helpers;
use App\Models\PauseGroupEmailModel;
use DB;
use Illuminate\Console\Command;

use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\TopicConf;

/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class KafkaConsumerHighCommand extends Command
{

    protected $signature = 'kafka-consumer';

    protected $description = 'Gets messages from Kafka';

    public function handle()
    {

        $result = $this->process();

        return $result;
    }

    protected function process()
    {

        $conf = new Conf();

        // Set the group id. This is required when storing offsets on the broker
        $conf->set('group.id', 'myConsumerGroup');
        $conf->set('broker.version.fallback', '0.8.2.1');

        $rk = new Consumer($conf);
        $rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");

        $topicConf = new TopicConf();
        $topicConf->set('auto.commit.interval.ms', 100);


        // Set the offset store method to 'file'
        $topicConf->set('offset.store.method', 'file');
        $topicConf->set('offset.store.path', sys_get_temp_dir());

        // Alternatively, set the offset store method to 'broker'
        // $topicConf->set('offset.store.method', 'broker');

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $topicConf->set('auto.offset.reset', 'smallest');

        $topic = $rk->newTopic("email_one_email_to_be_sent", $topicConf);
        // Start consuming partition 0
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        while (true)
        {
            $message = $topic->consume(0, 120*10000);
            switch ($message->err)
            {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->SendByPHPMailer(json_decode($message->payload));
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    break;
                default:

                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }

    }

    public function sendByPHPMailer($data)
    {

        $server = DeliveryServerModel::where('status', '=', 'active')
            ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
            ->get();
        /*
         * Check for paused customers or groups
         */
        $pause = false;
        $Pause = PauseGroupEmailModel::where('group_email_id','=',$data->group_id)->orWhere('customer_id', '=', $data->customer_id)->get();

        if (!empty($Pause[0]))
        {
            if ($Pause->pause_customer==true||$Pause->group_email_id>0)
            {
                $pause = true;
            }
        }

        /*
         * Check bounces
         */
        $Bounce = GroupEmailBounceLogModel::where('email', '=', $data->to_email)->get();

        if (!empty($Bounce[0]))
        {
            return;
        }
        /*
         * Save email
         */

        $Email = new GroupEmailModel();
        $Email->email_uid = uniqid('', true);
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
        $Email->group_email_id = $data->group_id;
        $Email->date_added = $Email->last_updated  = new \DateTime();
        $Email->max_retries = 5;

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

            if ($pause == true)
            {
                $Email->status = 'paused';
            }
            elseif (!$mail->send())
            {
                // save status failed if mail did not send
                $Email->status = 'failed';
            }
            else
            {
                // save status sent if mail DID send
                $Email->status = 'sent';
            }
            $Email->save();

            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();

        } catch (\Exception $e)
        {
            // save status error if try/catch returns error
            $Email->status = 'error';
            $Email->save();


        }

    }

}