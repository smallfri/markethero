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
use RdKafka\Producer;
use RdKafka\TopicConf;
use Illuminate\Foundation\Bus\DispatchesJobs;


/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class KafkaProducerCommand extends Command
{

    protected $signature = 'kafka-producer {id*}';

    protected $description = 'Gets messages from Kafka';

    public $verbose = 1;

    public $message;

    public $Email;

    public $id;

    public function handle()
    {

        $id = $this->argument('id');
        $this->id = $id;
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
        $conf->set('security.protocol', 'plaintext');
        $conf->set('broker.version.fallback', '0.8.2.1');

        $rk = new Producer($conf);
        $rk->setLogLevel(LOG_DEBUG);
//        $rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");
        $rk->addBrokers("zk-1.prod.markethero.io, zk-2.prod.markethero.io, zk-3.prod.markethero.io");

        $topic = $rk->newTopic("email_one_email_to_be_sent");

        $message = [
            'reply_to_email' => 'russell@smallfri.com',
            "to_email" => "smallfriinc@gmail.com",
            'from_email' => 'russell@smallfri.com',
            'to_name' => 'russell@smallfri.com',
            'reply_to_name' => 'russell@smallfri.com',
            'subject' => uniqid(),
            'send_at' => '2016-11-15 01:38:28',
            'id' => $this->id,
            'body' => uniqid(),
            'plain_text' => 'message',
            'customer_id' => 11,
            'group_id' => 100,
            'from_name' => 'Russell',


        ];


        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message));
        var_dump(json_encode($message));
    }

}