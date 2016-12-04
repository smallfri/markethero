<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 7/22/16
 * Time: 7:27 AM
 */

namespace App\Console\Commands;

use App\Models\BounceServer;
use App\Models\GroupEmailBounceModel;
use App\Models\User;
use App\Notifications\InvoicePaid;
use App\Notifications\missingEmail;
use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use PDO;

class TestEmailNotifyHandler extends Command
{

    protected $signature = 'test-email-notifier';

    protected $description = 'Notifies when email is down';

    public $verbose = 1;

    use Notifiable;

        /**
         * Route notifications for the Nexmo channel.
         *
         * @return string
         */
        public function routeNotificationForNexmo()
        {
            return $this->phone;
        }

    public function handle()
    {

        $this->stdout('Starting...');

        DB::reconnect('mysql');
                           $pdo = DB::connection()->getPdo();
                           $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                           $mail = DB::select(DB::raw('SELECT received, date_added FROM mw_test_emails ORDER BY email_test_id DESC LIMIT 1'));
                           DB::disconnect('mysql');

        if(empty($mail) || $mail[0]->received == 0)
        {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://hooks.slack.com/services/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', [
                'json' => [
                    'text' => 'Email NOT received WARNING! Date/Time: '.$mail[0]->date_added,
                ]
            ]);

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://hooks.slack.com/services/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', [
                'json' => [
                    'text' => 'Email NOT received WARNING! Date/Time: '.$mail[0]->date_added,
                ]
            ]);

            print_r($response);

        }

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

}