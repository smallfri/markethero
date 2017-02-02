<?php
namespace App\Http\Controllers;

use App\EmailOne\Transformers\CustomerTransformer;

use App\Logger;
use App\Models\Bounce;
use App\Models\BounceServer;
use App\Models\Customer;
use App\Http\Requests;
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailBounceLogModel;
use App\Models\GroupEmailBounceModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\StatsModel;
use App\Models\TraceLog;
use App\Models\TransactionalEmailModel;
use Carbon\Carbon;
use Zend\Http\Response;
use Illuminate\Http\Request;
use DB;
use App\Models\GroupEmailModel;

/**
 * Class CustomerController
 * @package App\Http\Controllers
 */
class KlipfolioController extends ApiController
{

    /**
     * CustomerController constructor.
     * @param CustomerTransformer $customerTransformer
     */
    function __construct(CustomerTransformer $customerTransformer)
    {

        $this->customerTransformer = $customerTransformer;

        $this->middleware('auth.basic');

    }

    public function customerCount()
    {
        return;


        $customers = Customer::select(DB::raw('COUNT(customer_id) as count, DAY(date_added) as date'))
            ->groupBy(DB::raw('DAY(date_added)'))
            ->get();

//            dd($customers);

        if (empty($customers))
        {
            return $this->respondWithError('No Customers found.');
        }

        return $this->respond(['customers' => $customers]);

    }

    public function groupCount()
    {
        return;


        $groups = GroupEmailGroupsModel::select(DB::raw('COUNT(group_email_id) as count, date_added'))
            ->groupBy('date_added')
            ->get();

        if (empty($groups))
        {
            return $this->respondWithError('No Groups found.');
        }

        return $this->respond(['groups' => $groups]);

    }

    public function getGroups()
    {

        return;

        $pendingGroups = GroupEmailGroupsModel::join('mw_customer as c', 'c.customer_id', '=',
            'mw_group_email_groups.customer_id')->where('mw_group_email_groups.status', '=', 'pending-sending')
            ->orWhere('mw_group_email_groups.status', '=', 'processing')
            ->take(10)
            ->get();

        $pending = [];

        if (!empty($pendingGroups))
        {
            foreach ($pendingGroups as $group)
            {
                $pending[$group->group_email_id] = [];
                $pending[$group->group_email_id]['group_email_id'] = $group->group_email_id;
                $pending[$group->group_email_id]['customer'] = $group->first_name.' '.$group->last_name;
                $pending[$group->group_email_id]['countPending'] = GroupEmailModel::where('group_email_id', '=',
                    $group->group_email_id)
                    ->where('status', '=', 'pending-sending')
                    ->count();
                $pending[$group->group_email_id]['countSent'] = GroupEmailModel::where('group_email_id', '=',
                    $group->group_email_id)
                    ->where('status', '=', 'sent')
                    ->count();
            }

            return $this->respond(['stats' => $pending]);

        }
    }

    public function getLast100Groups()
    {

        $Groups = DB::select(DB::raw('SELECT
                	count(ge.email_id) AS count, MIN(ge.last_updated) AS min, MAX(ge.last_updated) AS max, g.group_email_id, g.status, c.customer_id, c.email, c.first_name, c.last_name
                FROM
                	mw_group_email_groups AS g
                LEFT JOIN
                	mw_group_email AS ge
                ON  ge.group_email_id = g.group_email_id
                JOIN
                	mw_customer AS c
                ON  c.customer_id = g.customer_id
                GROUP BY
                	g.group_email_id ORDER BY g.group_email_id DESC LIMIT 10'
        ));

        return $this->respond(['stats' => $Groups]);

    }

    public function getAllGroupEmails()
    {
        return;


        $Emails = GroupEmailModel::select('mw_group_email.email_id', 'mw_group_email.customer_id',
            'mw_group_email.group_email_id', 'mw_group_email.from_email', 'mw_group_email.subject')
            ->orderBy('group_email_id', 'desc')
            ->groupBy('group_email_id')
            ->get();

        return $this->respond(['stats' => $Emails]);

    }

    public function getSpamReports()
    {
        return;


        $Emails = GroupEmailBounceModel::select(
            'mw_group_email_bounce_log.customer_id',
            'mw_group_email_bounce_log.group_id',
            'mw_group_email_bounce_log.message',
            'mw_group_email_bounce_log.bounce_type',
            'mw_group_email_bounce_log.date_added',
            'mw_group_email_bounce_log.email_uid'
        )
            ->orderBy('mw_group_email_bounce_log.group_id', 'desc')
            ->take(1000)
            ->get();

        return $this->respond(['stats' => $Emails]);

    }

    public function getDeliveryStats14Days()
    {

        return;

        $last_week1 = Carbon::parse('last week - 7 days')->format('Y-m-d');
        $last_week2 = Carbon::parse('this week + 7 days')->format('Y-m-d');

        $lastWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added, COUNT(email_id) AS count from `mw_group_email` where `date_added` between "'.$last_week1.'" and "'.$last_week2.'" GROUP BY DATE(date_added)'));

        return $this->respond(['stats' => $lastWeek]);

    }

    public function getBounceStats()
    {
        return;

        $this_week1 = Carbon::parse('last week - 7 days')->format('Y-m-d');
        $this_week2 = Carbon::parse('this week + 7 days')->format('Y-m-d');

        $thisWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(log_id) AS count from `mw_group_email_bounce_log` where `date_added` between "'.$this_week1.'" and "'.$this_week2.'" GROUP BY DATE(date_added)'));

        return $this->respond(['stats' => $thisWeek]);

    }

    public function getAbuseStats()
    {
        return;


        $this_week1 = Carbon::parse('this week - 14 days')->format('Y-m-d');
        $this_week2 = Carbon::parse('this week')->format('Y-m-d');

        $thisWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(report_id) AS count from `mw_group_email_abuse_report` where `date_added` between "'.$this_week1.'" and "'.$this_week2.'" GROUP BY DATE(date_added)'));

        return $this->respond(['stats' => $thisWeek]);

    }

    public function getUnsubscribeStats()
    {

        return;

        $this_week1 = Carbon::parse('this week - 14 days')->format('Y-m-d');
        $this_week2 = Carbon::parse('this week')->format('Y-m-d');

        $thisWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(id) AS count from `mw_group_email_unsubscribe` where `date_added` between "'.$this_week1.'" and "'.$this_week2.'" GROUP BY DATE(date_added)'));

        return $this->respond(['stats' => $thisWeek]);

    }

    public function getAllTransactionalEmails()
    {
        return;


        $Emails = TransactionalEmailModel::select('mw_transactional_email.customer_id',
            'mw_transactional_email.to_name', 'mw_transactional_email.to_email', 'mw_transactional_email.subject',
            'mw_transactional_email.status', 'mw_transactional_email.retries', 'mw_transactional_email.date_added',
            'log.message')
            ->join('mw_transactional_email_log AS log', 'log.email_id', '=', 'mw_transactional_email.email_id')
            ->orderBy('mw_transactional_email.email_id', 'desc')
            ->get();

        return $this->respond(['emails' => $Emails]);

    }

    public function getTraceLogs()
    {
        return;


        $logs = TraceLog::select('*')->orderBy('id', 'DESC')->take(100)->get()->toArray();

        $traceLogs = [];

        foreach ($logs as $key => $value)
        {
            $traceLogs[$key] = $value;

            $traceLogs[$key]['url'] = 'http://m-prod.markethero.io/mhapi/logs/viewLog/'.$value['id'];

        }


        return $this->respond(['logs' => $traceLogs]);
    }

    public function getBounceServerStatus()
    {
        return;

        $bounceServers = BounceServer::all();


        return $this->respond(['bounceservers' => $bounceServers]);

    }

    public function getDeliveryServerStatus()
    {

        return;

        $deliveryServers = DeliveryServerModel::all();

        return $this->respond(['deliveryServers' => $deliveryServers]);

    }

    public function getSMTPBounceRate()
    {
        return;

        $this_week1 = Carbon::parse('this week - 14 days')->format('H:i d-m-Y');

        $this_week2 = Carbon::parse('this week')->format('H:i d-m-Y');


        $post_fields = [
            'api_key' => '066b2b41ba4b16d6514adbc3a4cc4d251133e250',
            'time_start' => $this_week1,
            'time_end' => $this_week2
        ];


        $url = 'http://rest.smtp.com/v1/account/senders/5017936/statistics/summary.json';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, 'russell@smallfri.com:KjV9g2JcyFGAHng');

        $result = json_decode(curl_exec($ch));

        $stats = [];
        $stats['bounces'] = $result->summary->bounced_count/$result->summary->delivered_count;
        $stats['complaints'] = $result->summary->complaints_count/$result->summary->delivered_count;

        return $this->respond([$stats]);

    }

    public function getGodStats()
    {

        $stats = DB::table('mw_group_email_stats')
            ->join('mw_customer', 'mw_customer.customer_id', '=', 'mw_group_email_stats.customer_id')
            ->orderBy('send_volume', 'DESC')
            ->take(50)
            ->get()
            ->toArray();

        return $this->respond(['stats' => $stats]);
    }

    public function getGodFrame()
    {

        $customers = Customer::all();

        $html
            = '<div style="height:100px"><form action="https://m-prod.markethero.io/mhapi/v1/klipfolio/getGodFrame" method="post"><select name="customer_id">';
        foreach ($customers AS $customer)
        {
            $html .= '<option value="'.$customer->customer_id.'">'.$customer->customer_id.'</option>';
        }

        $html .= '</select>';

        $html
            .= <<<END
        <select name = "pool_group_id">
        <option value = "1">MarketHero 1 (low)</option>
        <option value = "2">MarketHero 2 (mid)</option>
        <option value = "3">MarketHero (high)</option>
        <input type="submit" value="Move Customer" style="margin-left:5px"><span style="color:white;margin-left:20px">%message%</span></form>
        </div>
END;

        if (!empty($_POST))
        {
            $customer = $_POST;
            Customer::where('customer_id', $customer['customer_id'])
                ->update([
                        'pool_group_id' => $customer['pool_group_id'],
                        'last_updated' => new \DateTime()
                    ]
                );
            echo str_replace('%message%', 'Customer Moved', $html);
        }
        else
        {
            echo str_replace('%message%', '', $html);

        }

    }

    public function bouncesByCustomerId()
    {

        return;

        $date = new Carbon();

        $date->subDays(2);

        $sql = 'SELECT count(b.log_id) AS bounces , b.customer_id, c.email, s.send_volume from mw_group_email_bounce_log AS b JOIN mw_customer AS c ON c.customer_id = b.customer_id JOIN mw_group_email_stats AS s ON s.customer_id = c.customer_id WHERE 1 AND b.date_added >= "'.$date->toDateTimeString().'" GROUP BY b.customer_id ORDER BY bounces DESC';

        $bounces = DB::select(DB::raw($sql));

        return $this->respond([$bounces]);

    }

}

