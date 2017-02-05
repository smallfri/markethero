<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */
namespace App\Console\Commands;

use App\Jobs\SendEmail;
use App\Jobs\SendTransactionalEmail;
use App\Models\BroadcastEmailModel;
use App\Models\GroupEmailModel;
use App\Models\TransactionalEmailModel;
use DB;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\TopicConf;
use Illuminate\Foundation\Bus\DispatchesJobs;


/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class KafkaConsumerCommand extends Command
{

    protected $signature = 'kafka-consumer';

    protected $description = 'Gets messages from Kafka';

    public $verbose = 1;

    public $message;

    public $Email;

    public function handle()
    {

        $result = $this->process();

//        return $result;
    }

    /**
     * Main method containing the logic to be executed by the task
     *
     * @param $params array Assoc array of params
     *
     * @return boolean True upon success, false otherwise
     */
    public function process(array $params = array())
    {

//        print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

        $this->runKafka();

    }

    /**
     * @param $message
     * @param bool|true $timer
     * @param string $separator
     */
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

    protected function runKafka()
    {

        $conf = new Conf();

        // Set the group id. This is required when storing offsets on the broker
        $conf->set('group.id', uniqid());
        $conf->set('broker.version.fallback', '0.8.2.1');

        $rk = new Consumer($conf);
        $rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");

        $topicConf = new TopicConf();
        $topicConf->set('auto.commit.interval.ms', 100);

        // Set the offset store method to 'file'
        $topicConf->set('offset.store.method', 'file');
        $topicConf->set('offset.store.path', sys_get_temp_dir());
        $topicConf->set('auto.commit.enable', 'false');

        // Alternatively, set the offset store method to 'broker'
        //         $topicConf->set('offset.store.method', 'broker');

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $topicConf->set('auto.offset.reset', 'largest');

        $topic = $rk->newTopic("email_one_email_to_be_sent", $topicConf);
        // Start consuming partition 0
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        while (true)
        {

            $message = $this->message = $topic->consume(0, 10);
            //print_r($message);

            if (!empty($message))
            {
                switch ($message->err)
                {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $this->save(json_decode($this->message->payload));
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
    }

    public function save($data)
    {
        $emailUID = uniqid('', true);

        if (property_exists($data, 'group_id'))
        {

            $hash = md5(strtolower(trim($data->group_id). trim($data->to_email) . trim($data->body) . trim($data->subject)));

            $emailExist = BroadcastEmailModel::where('hash', '=', $hash)
                ->get();

            if (!$emailExist->isEmpty())
            {
                return false;
            }

            $Email = new BroadcastEmailModel();
            $Email->emailUID = $emailUID;
            $Email->mhEmailID = $data->id;
            $Email->toName = $data->to_name;
            $Email->toEmail = $data->to_email;
            $Email->fromName = $data->from_name;
            $Email->fromEmail = $data->from_email;
            $Email->replyToName = $data->reply_to_name;
            $Email->replyToEmail = $data->reply_to_email;
            $Email->subject = $data->subject;
            $Email->body = $data->body;
            $Email->plainText = $data->plain_text;
            $Email->customerID = $data->customer_id;
            $Email->groupID = $data->group_id;
            $Email->dateAdded = $Email->lastUpdated = new \DateTime();
            $Email->status = 'queued';
            $Email->hash = $hash;
            $Email->save();

            $this->Email = $Email;

            $job = (new SendEmail($Email))->onConnection('redis')->onQueue('redis-group-queue');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

        }
        else
        {
            $hash = md5(256, trim($data->to_email).trim($data->body).trim($data->subject));

            $emailExist = TransactionalEmailModel::where('hash', '=', $hash)
                ->get();

            if (!$emailExist->isEmpty())
            {
                return false;
            }

            $Email = new TransactionalEmailModel();
            $Email->emailUID = $emailUID;
            $Email->mhEmailID = $data->id;
            $Email->customerID = $data->customer_id;
            $Email->toName = $data->to_name;
            $Email->toEmail = $data->to_email;
            $Email->fromName = $data->from_name;
            $Email->fromEmail = $data->from_email;
            $Email->replyToName = $data->reply_to_name;
            $Email->replyToEmail = $data->reply_to_email;
            $Email->subject = $data->subject;
            $Email->body = $data->body;
            $Email->plainText = $data->plain_text;
            $Email->dateAdded = $Email->lastUpdated = new \DateTime();
            $Email->status = 'queued';
            $Email->save();

            $this->Email = $Email;

            $job = (new SendTransactionalEmail($Email))->onConnection('redis')
                ->onQueue('redis-transactional-queue');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

        }


        return true;

    }

}