<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands
        = [
            'App\Console\Commands\SendEmailsCommand',
            'App\Console\Commands\SendTransactionalEmailCommand',
            'App\Console\Commands\AvgComplianceScoreCommand',
            'App\Console\Commands\GroupBounceHandlerCommand',
            'App\Console\Commands\GroupsComplianceHandlerCommand',
            'App\Console\Commands\UpdateGroupStatusCommand',
            'App\Console\Commands\SendTestEmailCommandHandler',
            'App\Console\Commands\TestEmailCommandHandler',
            'App\Console\Commands\TestEmailNotifyHandler',
            'App\Console\Commands\KafkaConsumerCommand',
            'App\Console\Commands\KafkaProducerCommand',
            'App\Console\Commands\KafkaClicksConsumerCommand',
            'App\Console\Commands\KafkaOpensConsumerCommand',
            'App\Console\Commands\StatsCommand',
            'App\Console\Commands\SendForgottenEmailsCommand',

        ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->command('update-group-status')->everyMinute();
        // $schedule->command('kafka-consumer')->everyMinute();
        /**
         * This runs a check for forgotten emails and adds them to the 'send-forgotten-queues' queue
         *
         */
        $schedule->command('send-forgotten-groups')->hourly();
        // $schedule->command('compliance:average')->cron('0 0 * * *');
        // $schedule->command('compliance:check')->everyMinute();
        //$schedule->command('bounce-handler')->everyMinute();
        //$schedule->command('get-stats')->daily();
 		//$schedule->command('send-test-email')->everyFiveMinutes();
        //$schedule->command('test-email-handler')->everyFiveMinutes();
        //$schedule->command('test-email-notifier')->everyTenMinutes();
    }

 
}
