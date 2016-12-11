<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */
namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\StatsModel;
use DB;
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

        $sql
            = '
    SELECT
    count(email_id) as send_volume,
    ge.last_updated AS last_broadcast,
    ge.group_email_id AS last_broadcast_id,
    CONCAT(c.first_name, \' \', c.last_name) AS name,
    c.customer_id as user_id,
    (SELECT count(clickId) as clicks FROM mw_group_email_clicks WHERE emailOneCustomerId = c.customer_id) AS clicks,
    (SELECT count(report_id) as complaintes FROM mw_group_email_abuse_report WHERE customer_id = c.customer_id) AS complaints,
    (SELECT count(id) as unsubscribes FROM mw_group_email_unsubscribe WHERE customer_id  = c.customer_id) AS unsubscribes
    FROM
    mw_group_email AS ge
    JOIN
    mw_group_email_groups as g
    ON g.group_email_id = ge.group_email_id
    JOIN
    mw_customer AS c
    ON c.customer_id = ge.customer_id
    GROUP BY c.customer_id LIMIT 1
';


        $customers = Customer::all();

        foreach($customers AS $customer)
        {
            $sql = '
                    SELECT
                        count(ge.email_id) as send_volume,
                        MAX(ge.last_updated) AS last_broadcast,
                        MAX(ge.group_email_id) AS last_broadcast_id,
                        CONCAT(c.first_name, " ", c.last_name) AS name,
                        c.customer_id as user_id
                    FROM
                        mw_group_email AS ge
                    JOIN
                        mw_group_email_groups AS g
                    ON g.group_email_id = ge.group_email_id
                    JOIN
                        mw_customer AS c
                    ON c.customer_id = ge.customer_id
                    WHERE c.customer_id ='.$customer->customer_id;

            $stats = DB::select(
                DB::raw(
                    $sql
                )
            );

            dd($stats);

//            StatsModel::where('customer_id', $stats)
//                        ->update(['status' => $status]);


        }

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

}