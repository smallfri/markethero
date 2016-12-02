<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 7/22/16
 * Time: 7:27 AM
 */

namespace App\Console\Commands;


use App\Helpers\Helpers;
use App\Logger;
use App\Models\BlacklistModel;
use App\Models\BounceServer;
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailBounceLogModel;
use App\Models\GroupEmailBounceModel;
use App\Models\GroupEmailModel;
use DateTime;
use Illuminate\Console\Command;
use DB;

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
                "to_email"=>'bounces@marketherobounce1.com',
                'from_email' => 'russell@smallfri.com',
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

$data = json_decode($data);

        if ($data)
        {
            $email_uid = uniqid('', true);

            $server = DeliveryServerModel::where('status', '=', 'active')
                ->where('use_for', '=', DeliveryServerModel::USE_FOR_ALL)
                ->get();

            $this->stdout('['.date('Y-m-d H:i:s').'] Get Server');


            try
            {
                $this->stdout('['.date('Y-m-d H:i:s').'] Get ready to send mail');

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

                $mail->addCustomHeader('X-Mw-Test-Id', $email_uid);

                $mail->addReplyTo($data->from_email, $data->from_name);
                $mail->setFrom($data->from_email, $data->from_name);
                $mail->addAddress($data->to_email, $data->to_name);

                $mail->Subject = $data->subject;
                $mail->MsgHTML($data->body);

                if ($mail->send())
                {
                    $status = 'sent';
                }
                else{
                    $status = 'failed';
                }

                $this->stdout('['.date('Y-m-d H:i:s').'] Handled Mail with status '.$status);


                $mail->clearAddresses();
                $mail->clearAttachments();
                $mail->clearCustomHeaders();

            } catch (\Exception $e)
            {
                // save status error if try/catch returns error
                $status = 'error';
                $this->stdout($e);

            }

            DB::select(DB::raw('INSERT INTO mw_test_emails SET sent = 1, status = "'.$status.'", date_added = now(), email_uid = "'.$email_uid.'"'));

        }

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