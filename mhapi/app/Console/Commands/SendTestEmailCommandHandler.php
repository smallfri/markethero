<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 7/22/16
 * Time: 7:27 AM
 */

namespace App\Console\Commands;


use App\Helpers\Helpers;
use App\Jobs\SendEmail;
use App\Logger;
use App\Models\BlacklistModel;
use App\Models\BounceServer;
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailBounceLogModel;
use App\Models\GroupEmailBounceModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;
use DateTime;
use Illuminate\Console\Command;
use DB;
use PDO;

class SendTestEmailCommandHandler extends Command
{

    protected $signature = 'send-test-email';

    protected $description = 'Sents Test Emails';

    public $verbose = 1;

    public $_server;

    public function handle()
    {

        $this->stdout('Starting...');

        $this->process();

    }

    protected function process()
    {



        $data = [
                'reply_to_email' => 'russell@smallfri.com',
                "to_email"=>'mhtestemails@smallfriinc.com',
                'from_email' => 'smallfriinc@gmail.com',
                'to_name' => 'russell@smallfri.com',
                'reply_to_name' => 'russell@smallfri.com',
                'subject' => 'Hourly Test Message',
                'send_at' => '2016-11-15 01:38:28',
                'body' => uniqid().' / '.print_r(new DateTime(),true),
                'plain_text' => 'message',
                'customer_id' => 11,
                'group_id' => 1,
                'from_name' => 'Russell',


            ];



        $this->sendByPHPMailer(json_encode($data));


        return;
    }

    public function sendByPHPMailer($data)
    {
        $email_uid = uniqid('',true);

            $EmailGroup = new \stdClass();
                    $EmailGroup->email_uid = $email_uid;
                    $EmailGroup->to_name = 'email tester';
                    $EmailGroup->to_email = 'mhtestemails@smallfriinc.com';
                    $EmailGroup->from_name = 'email tester';
                    $EmailGroup->from_email = 'russell@smallfri.com';
                    $EmailGroup->reply_to_name = 'russell';
                    $EmailGroup->reply_to_email = 'russell@smallfri.com';
                    $EmailGroup->subject = 'Hourly Test Email';
                    $EmailGroup->body = 'UniqueID:'.uniqid();
                    $EmailGroup->plain_text = 'Hourly Test Email';
                    $EmailGroup->send_at = new \DateTime();
                    $EmailGroup->customer_id = 11;
                    $EmailGroup->group_email_id = 1;
                    $EmailGroup->status = GroupEmailGroupsModel::STATUS_QUEUED;
                    $EmailGroup->date_added = new \DateTime();
                    $EmailGroup->max_retries = 5;

       $this->stdout('Adding email mhtestemails@smallfriinc.com');

                   $job = (new SendEmail($EmailGroup))->onConnection('mail-queue');
                   app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

            DB::reconnect('mysql');
                   $pdo = DB::connection()->getPdo();
                   $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            DB::select(DB::raw('INSERT INTO mw_test_emails SET sent = 1, status = "sent", date_added = now(), email_uid = "'.$email_uid .'"'));
            DB::disconnect('mysql');




        return true;
    }


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

    function microtime_float()
    {

        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec+(float)$sec);
    }

    function fixArrayKey(&$arr)
    {

        $arr = array_combine(
            array_map(
                function ($str)
                {

                    return str_replace(" ", "_", $str);
                },
                array_keys($arr)
            ),
            array_values($arr)
        );

        foreach ($arr as $key => $val)
        {
            if (is_array($val))
            {
                fixArrayKey($arr[$key]);
            }
        }
    }

}