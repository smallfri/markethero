<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;

use App\Models\DeliveryServerModel;
use App\Models\GroupEmailBounceModel;
use App\Models\GroupEmailModel;
use App\Helpers\Helpers;
use App\Models\PauseGroupEmailModel;
use DB;
use Illuminate\Console\Command;

use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\Producer;
use RdKafka\TopicConf;
use Threading\Multiple;
use Threading\Task\Example;

/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class KafkaConsumerMultiThreadCommand extends Command
{

    protected $signature = 'kafka-consumer';

    protected $description = 'Gets messages from Kafka and mutlithreads them';

    public function handle()
    {

        $maxThreads = 50;
        echo 'Example of the multi-thread manager with '.$maxThreads.' threads'.PHP_EOL.PHP_EOL;
        $params = array();
        $exampleTask = new Example($maxThreads);
        $multithreadManager = new Multiple();

        $cpt = 0;
        while (++$cpt<=100)
        {
            $multithreadManager->start($exampleTask);
        }
    }

//    protected function runKafka()
//    {
//
//        $conf = new Conf();
//
//        // Set the group id. This is required when storing offsets on the broker
//        $conf->set('group.id', 'myConsumerGroup');
//        $conf->set('broker.version.fallback', '0.8.2.1');
//
//        $rk = new Consumer($conf);
//        $rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");
//
//        $topicConf = new TopicConf();
//        $topicConf->set('auto.commit.interval.ms', 100);
//
//        // Set the offset store method to 'file'
//        $topicConf->set('offset.store.method', 'file');
//        $topicConf->set('offset.store.path', sys_get_temp_dir());
//
//        // Alternatively, set the offset store method to 'broker'
//        //         $topicConf->set('offset.store.method', 'broker');
//
//        // Set where to start consuming messages when there is no initial offset in
//        // offset store or the desired offset is out of range.
//        // 'smallest': start from the beginning
//        $topicConf->set('auto.offset.reset', 'smallest');
//
//        $topic = $rk->newTopic("email_one_email_to_be_sent", $topicConf);
//        // Start consuming partition 0
//        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
//
//        while (true)
//        {
//            $message = $topic->consume(0, 120*10000);
//
//
//            if (!empty($message))
//            {
//                switch ($message->err)
//                {
//                    case RD_KAFKA_RESP_ERR_NO_ERROR:
//                        $this->sendByPHPMailer(json_decode($message->payload));
//                        break;
//                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
//                        echo "No more messages; will wait for more\n";
//                        break;
//                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
//                        echo "Timed out\n";
//                        break;
//                    default:
//                        throw new \Exception($message->errstr(), $message->err);
//                        break;
//                }
//            }
//        }
//
//    }
//
//    public function sendByPHPMailer($data)
//    {
//
//        if ($data)
//        {
//
//            if (property_exists($data, 'group_id'))
//            {
//
//
//                $group_id = $data->group_id;
//            }
//            else
//            {
//                $group_id = '';
//            }
//
//            $email_uid = uniqid('', true);
//
//            try
//            {
//                /*
//                 * Save email
//                 */
//                $Email = new GroupEmailModel();
//                $Email->email_uid = $email_uid;
//                $Email->mhEmailID = $data->id;
//                $Email->to_name = $data->to_name;
//                $Email->to_email = $data->to_email;
//                $Email->from_name = $data->from_name;
//                $Email->from_email = $data->from_email;
//                $Email->reply_to_name = $data->reply_to_name;
//                $Email->reply_to_email = $data->reply_to_email;
//                $Email->subject = $data->subject;
//                $Email->body = $data->body;
//                $Email->plain_text = $data->plain_text;
//                $Email->send_at = $data->send_at;
//                $Email->customer_id = $data->customer_id;
//                $Email->group_email_id = $group_id;
//                $Email->date_added = $Email->last_updated = new \DateTime();
//                $Email->max_retries = 5;
//                $Email->status = 'pending';
//                $Email->save();
//
//            } catch (\Exception $e)
//            {
//                return false;
//            }
//
//
//            $server = DeliveryServerModel::where('status', '=', 'active')
//                ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
//                ->get();
//
//            /*
//             * Check for paused customers or groups
//             */
//            $pause = false;
//            if (property_exists($data, 'group_id'))
//            {
//                $Pause = PauseGroupEmailModel::where('group_email_id', '=', $data->group_id)
//                    ->orWhere('customer_id', '=', $data->customer_id)
//                    ->get();
//
//                if (!empty($Pause[0]))
//                {
//                    if ($Pause->pause_customer==true||$Pause->group_id>0)
//                    {
//                        $pause = true;
//                    }
//                }
//            }
//
//
//            try
//            {
//                $mail = New \PHPMailer();
//                $mail->SMTPKeepAlive = true;
//
//                $mail->isSMTP();
//                $mail->CharSet = "utf-8";
//                $mail->SMTPAuth = true;
//                $mail->SMTPSecure = "tls";
//                $mail->Host = $server[0]['hostname'];
//                $mail->Port = 2525;
//                $mail->Username = $server[0]['username'];
//                $mail->Password = base64_decode($server[0]['password']);
//                $mail->Sender = Helpers::findBounceServerSenderEmail($server[0]['bounce_server_id']);
//
//                $mail->addCustomHeader('X-Mw-Customer-Id', $data->customer_id);
//                $mail->addCustomHeader('X-Mw-Email-Uid', $email_uid);
//                $mail->addCustomHeader('X-Mw-Group-Id', $group_id);
//
//                $mail->addReplyTo($data->from_email, $data->from_name);
//                $mail->setFrom($data->from_email, $data->from_name);
//                $mail->addAddress($data->to_email, $data->to_name);
//
//                $mail->Subject = $data->subject;
//                $mail->MsgHTML($data->body);
//
//                $status = 'unsent';
//                if ($pause==true)
//                {
//                    $status = 'paused';
//                }
//                elseif ($mail->send())
//                {
//                    $status = 'sent';
//                }
//
//                $mail->clearAddresses();
//                $mail->clearAttachments();
//                $mail->clearCustomHeaders();
//
//            } catch (\Exception $e)
//            {
//                // save status error if try/catch returns error
//                $status = 'error';
//            }
//
//            if ($Email->email_id>0)
//            {
//                $Email = GroupEmailModel::find($Email->email_id);
//                $Email->status = $status;
//                $Email->save();
//
//            }
//
//
//            if (!empty($Email)&&$status=='sent')
//            {
//                $this->replyToMarketHero($Email);
//            }
//        }
//    }
//
//    public function replyToMarketHero($Email)
//    {
//
//        $conf = new Conf();
//        $conf->set('security.protocol', 'plaintext');
//        $conf->set('broker.version.fallback', '0.8.2.1');
//
//        $rk = new Producer($conf);
//        $rk->setLogLevel(LOG_DEBUG);
//        $rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");
//
//        $topic = $rk->newTopic("email_one_email_sent");
//        $date = date_create();
//
//        $message = [
//            'mhEmailID' => $Email->mhEmailID,
//            'emailOneEmailID' => $Email->email_uid,
//            'sentDateTime' => date_format($date, 'U')
//        ];
//
//        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message));
//        //        var_dump(json_encode($message));
//    }

}