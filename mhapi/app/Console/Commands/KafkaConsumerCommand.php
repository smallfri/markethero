<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;

use App\Jobs\SendEmail;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;
use DB;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\TopicConf;
use AsyncPHP\Doorman\Manager\SynchronousManager;
use AsyncPHP\Doorman\Task\CallbackTask;
/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class KafkaConsumerCommand extends Command
{

    protected $signature = 'kafka-consumer-one';

    protected $description = 'Gets messages from Kafka';

    public $verbose = 1;

    public function handle()
    {
        print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

//        $result = $this->process();

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
        print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

//        $this->runKafka();


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
            $message = $topic->consume(0, 10);
            print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

            if (!empty($message))
            {
                switch ($message->err)
                {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:

print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
                        $manager = new SynchronousManager();

                               $task1 = new CallbackTask(function ($message) {
                                   $this->save(json_decode($message->payload));
                                   $this->save(json_decode($message->payload));
                               });

                               $task2 = new CallbackTask(function ($message) {
                                   $this->loadQueue(json_decode($message->payload));
                               });

                               $manager->addTask($task1);
                               $manager->addTask($task2);

                               while ($manager->tick()) {
                                   usleep(250);
                               }

                        print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');



                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        echo "No more messages; will wait for more\n";
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        echo "Timed out\n";
                        break;
                    default:
                        print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');

                        throw new \Exception($message->errstr(), $message->err);
                        break;
                }
            }
        }
    }

    public function save($data)
    {
        print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');


        if (property_exists($data, 'group_id'))
        {
            $group_id = $data->group_id;
        }
        else
        {
            $group_id = '';
        }

        $email_uid = uniqid('', true);

        try
        {
            $Email = new GroupEmailModel();
            $Email->mhEmailID = $data->id;
            $Email->email_uid = $email_uid;
            $Email->customer_id = $data->customer_id;
            $Email->group_email_id = $group_id;;
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
            $Email->status = 'pending-sending';
            $Email->date_added = $Email->last_updated = new \DateTime();
            $Email->save();
        } catch (\Exception $e)
        {
            $this->stdout('['.date('Y-m-d H:i:s').'] Email Not Saved '.$data->id);

            return false;
        }


    }

    public function loadQueue($data)
    {

        print_r($data);

        $EmailGroup = new \stdClass();
        $EmailGroup->email_id = $data->email_id;
        $EmailGroup->email_uid = $data->email_uid;
        $EmailGroup->to_name = $data->to_name;
        $EmailGroup->to_email = $data->to_email;
        $EmailGroup->from_name = $data->from_name;
        $EmailGroup->from_email = $data->from_email;
        $EmailGroup->reply_to_name = $data->reply_to_name;
        $EmailGroup->reply_to_email = $data->reply_to_email;
        $EmailGroup->subject = $data->subject;
        $EmailGroup->body = $data->body;
        $EmailGroup->plain_text = $data->plain_text;
        $EmailGroup->send_at = $data->send_at;
        $EmailGroup->customer_id = $data->customer_id;
        $EmailGroup->group_email_id = $data->group_email_id;
        $EmailGroup->status = GroupEmailGroupsModel::STATUS_QUEUED;
        $EmailGroup->date_added = new \DateTime();
        $EmailGroup->max_retries = 5;


        $job = (new SendEmail($EmailGroup))->onConnection('qa-mail-queue');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
    }
}