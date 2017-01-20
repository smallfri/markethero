<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;


use App\Jobs\SendForgottenEmail;
use App\Models\GroupEmailModel;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class SendForgottenEmailsCommand extends Command
{

    protected $signature = 'send-forgotten-email';

    protected $description = 'Sends Emails that were forgotten or delayed';

    public $verbose = 1;

    public function handle()
    {

        $result = $this->process();

        return $result;
    }

    protected function process()
    {


        $Emails = $this->findEmailsForSending();

        if (!empty($Emails))
        {
            foreach ($Emails AS $email)
            {
                $job = (new SendForgottenEmail($email))->onConnection('redis')->onQueue('redis-forgotten-email-queue');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
                $this->stdout('Adding email to the queue: '.$email->to_email);

            }

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

    protected function findEmailsForSending()
    {

        $time = Carbon::parse('5 hours ago')->format('H:i:s');

        $emails = GroupEmailModel::where('status', '=', 'queued')
            ->where('last_updated', '>', $time)
            ->get();

        return $emails;

    }

}