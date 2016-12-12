<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */
namespace App\Console\Commands;

use App\Models\ClicksModel;
use DB;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\TopicConf;

/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class KafkaClicksConsumerCommand extends Command
{

    protected $signature = 'kafka-clicks-consumer';

    protected $description = 'Gets clicks from Kafka';

    public $verbose = 1;

    public $message;

    public $Email;

    public function handle()
    {
        $this->process();
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
        $conf->set('group.id', 'emailOneClickConsumerGroup');
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

        $topic = $rk->newTopic("email_one_click_email_events", $topicConf);
        // Start consuming partition 0
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        while (true)
        {
            $message = $this->message = $topic->consume(0, 10);

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
        if (property_exists($data, 'groupId'))
        {
            $group_id = $data->groupId;
        }
        else
        {
            $group_id = 1;
        }

        try
        {
            $Email = new ClicksModel();
            $Email->clickedIp = $data->clickedIP;
            $Email->clickedDate = $data->clickedDate;
            $Email->externalId = $data->externalId;
            $Email->emailOneId = $data->emailOneId;
            $Email->emailOneCustomerId = $data->emailOneCustomerId;
            $Email->groupId = $group_id;
            $Email->date_added = $Email->last_updated = new \DateTime();
            $Email->save();

            $this->stdout('['.date('Y-m-d H:i:s').'] Click Saved '.$data->emailOneId);


        } catch (\Exception $e)
        {
            $this->stdout('['.date('Y-m-d H:i:s').'] Click Not Saved '.$data->emailOneId);
            return false;
        }

        return true;

    }
}