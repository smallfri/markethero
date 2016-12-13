<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */
namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\GroupControlsModel;
use App\Models\StatsModel;
use DB;
use GroupOptions;
use Illuminate\Console\Command;
use PDO;


/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class StatsCommand extends Command
{

    protected $signature = 'get-stats';

    protected $description = 'Gets stats';

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

        DB::table('mw_customer')->orderBy('customer_id')->chunk(100, function ($customers)
        {

            foreach ($customers as $customer)
            {
                $sql
                    = '
                       SELECT
                           count(ge.email_id) as send_volume,
                           MAX(ge.last_updated) AS last_broadcast,
                           MAX(ge.group_email_id) AS last_broadcast_id
                       FROM
                           mw_group_email AS ge
                       WHERE ge.customer_id = '.$customer->customer_id;

                $stats = DB::select(
                    DB::raw(
                        $sql
                    )
                );
                $stats = $stats[0];

                $sql
                    = '
                       SELECT
                           pool_group_id
                       FROM
                           mw_customer
                       WHERE customer_id = '.$customer->customer_id;

                $pool = DB::select(
                    DB::raw(
                        $sql
                    )
                );
                $pool = $pool[0];

                StatsModel::where('customer_id', $customer->customer_id)
                    ->update([
                        'send_volume' => $stats->send_volume,
                        'last_broadcast' => $stats->last_broadcast,
                        'last_broadcast_id' => $stats->last_broadcast_id,
                        'pool_group_id' => $pool->pool_group_id,
                        'last_updated' => new \DateTime()

                    ]);

            }
        });


        DB::table('mw_customer')->orderBy('customer_id')->chunk(100, function ($customers)
        {

            foreach ($customers as $customer)
            {


                $clicks_sql
                    = 'SELECT count(clickId) as clicks FROM mw_group_email_clicks WHERE emailOneCustomerId = '.$customer->customer_id;
                $complaints_sql
                    = 'SELECT count(report_id) as complaints FROM mw_group_email_abuse_report WHERE customer_id = '.$customer->customer_id;
                $unsub_sql
                    = 'SELECT count(id) as unsubscribes FROM mw_group_email_unsubscribe WHERE customer_id  = '.$customer->customer_id;

                $clicks = DB::select(
                    DB::raw(
                        $clicks_sql
                    )
                );

                $clicks = $clicks[0];

                $complaints = DB::select(
                    DB::raw(
                        $complaints_sql
                    )
                );

                $complaints = $complaints[0];

                $unsubs = DB::select(
                    DB::raw(
                        $unsub_sql
                    )
                );

                $unsubs = $unsubs[0];

                StatsModel::where('customer_id', $customer->customer_id)
                    ->update([
                        'last_updated' => new \DateTime(),
                        'clicks' => $clicks->clicks,
                        'complaints' => $complaints->complaints,
                        'unsubscribes' => $unsubs->unsubscribes
                    ]);

            }
        });

    }

    /**
     * @param $message
     * @param bool|true $timer
     * @param string $separator
     */
    protected
    function stdout(
        $message,
        $timer = true,
        $separator = ""
    ){

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