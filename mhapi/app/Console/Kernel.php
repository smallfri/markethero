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
    protected $commands = [
        'App\Console\Commands\SendGroupsCommand',
        'App\Console\Commands\SendTransactionalEmailCommand',
        'App\Console\Commands\AvgComplianceScoreCommand',
        'App\Console\Commands\GroupBounceHandlerCommand',
        'App\Console\Commands\GroupsComplianceHandlerCommand',
        'App\Console\Commands\SendForgottenGroupsCommand',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('send-groups')->everyMinute();
         $schedule->command('send-forgotten-groups')->everyFiveMinutes();
         $schedule->command('compliance:average')->cron('0 0 * * *');
         $schedule->command('compliance:check')->everyMinute();
         $schedule->command('bounce-handler')->cron('*/10 * * * *');
    }
}
