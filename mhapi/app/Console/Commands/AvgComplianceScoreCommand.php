<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 7/8/16
 * Time: 7:54 AM
 */

namespace App\Console\Commands;


use App\ComplianceAverageModel;
use App\ComplianceScoreModel;
use DB;
use Illuminate\Console\Command;


class AvgComplianceScoreCommand extends Command
{

    public $verbose = 0;

    public $automate = 0;

    protected $signature = 'compliance:average';

    protected $description = 'Calculate average compliance score based on the compliance scores.';

    public function handle()
    {

        /*
         * this cron runs every day to calculate the average compliance scores
         *
         */

        $normal = ComplianceScoreModel::select(DB::raw('AVG (bounce_report) as bounce_report,
                               AVG (abuse_report) as abuse_report,
                               AVG (unsubscribe_report) as unsubscribe_report,
                               AVG (score) as score'))->get();

        $normal = $normal[0];

        ComplianceAverageModel::where('id', '=', 1)
            ->update(
                [
                    'bounce_report' => $normal['bounce_report'],
                    'abuse_report' => $normal['abuse_report'],
                    'unsubscribe_report' => $normal['unsubscribe_report'],
                    'score' => $normal['score']
                ]
            );

    }
}