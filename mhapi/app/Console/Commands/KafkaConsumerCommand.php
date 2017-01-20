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
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailLogModel;
use App\Models\GroupEmailModel;
use App\Models\TransactionalEmailModel;
use DB;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\Producer;
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
        $topicConf->set('auto.offset.reset', 'smallest');

        $topic = $rk->newTopic("email_one_email_to_be_sent", $topicConf);
        // Start consuming partition 0
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        while (true)
        {
//                        print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

            $message = $this->message = $topic->consume(0, 10);
//            print_r($message);

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

        $email_uid = uniqid('', true);

        if (property_exists($data, 'group_id'))
        {
            $emailExist = GroupEmailModel::where('mhEmailID', '=', $data->id)->get();

//            dd($emailExist);

            if (!$emailExist->isEmpty())
            {
                return false;
            }

            $Email = new GroupEmailModel();
            $Email->mhEmailID = $data->id;
            $Email->email_uid = $email_uid;
            $Email->customer_id = $data->customer_id;
            $Email->group_email_id = $data->group_id;
            $Email->to_email = $data->to_email;
            $Email->to_name = $data->to_name;
            $Email->from_email = $data->from_email;
            $Email->from_name = $data->from_name;
            $Email->reply_to_email = $data->reply_to_email;
            $Email->reply_to_name = $data->reply_to_name;
            $Email->subject = $data->subject;
            $Email->body = $data->body;
            $Email->plain_text = $data->plain_text;
            $Email->max_retries = 5;
            $Email->send_at = $data->send_at;
            $Email->status = 'queued';
            $Email->date_added = $Email->last_updated = new \DateTime();
            $Email->save();

            $this->Email = $Email;

            $job = (new SendEmail($Email))->onConnection('redis')->onQueue('redis-group-queue');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
        else
        {
            $emailExist = GroupEmailModel::where('mhEmailID', '=', $data->id)->get();

            if (!$emailExist->isEmpty())
            {
                return false;
            }

            $Email = new TransactionalEmailModel();
            $Email->mhEmailID = $data->id;
            $Email->email_uid = $email_uid;
            $Email->customer_id = $data->customer_id;
            $Email->to_email = $data->to_email;
            $Email->to_name = $data->to_name;
            $Email->from_email = $data->from_email;
            $Email->from_name = $data->from_name;
            $Email->reply_to_email = $data->reply_to_email;
            $Email->reply_to_name = $data->reply_to_name;
            $Email->subject = $data->subject;
            $Email->body = $data->body;
            $Email->plain_text = $data->plain_text;
            $Email->max_retries = 5;
            $Email->send_at = $data->send_at;
            $Email->status = 'queued';
            $Email->date_added = $Email->last_updated = new \DateTime();
            $Email->save();

            $this->Email = $Email;

            $job = (new SendTransactionalEmail($Email))->onConnection('redis')
                ->onQueue('redis-transactional-queue');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

        }


        return true;

    }

}