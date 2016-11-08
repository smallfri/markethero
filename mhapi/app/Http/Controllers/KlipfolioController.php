<?php

namespace App\Http\Controllers;

use App\EmailOne\Transformers\CustomerTransformer;

use App\Logger;
use App\Models\BounceServer;
use App\Models\Customer;
use App\Http\Requests;
use App\Models\DeliveryServerModel;
use App\Models\GroupEmailBounceLogModel;
use App\Models\GroupEmailGroupsModel;
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

        $pendingGroups = GroupEmailGroupsModel::join('mw_customer as c','c.customer_id', '=', 'mw_group_email_groups.customer_id')->where('mw_group_email_groups.status', '=', 'pending-sending')
            ->orWhere('mw_group_email_groups.status', '=', 'processing')
            ->take(50)
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
        	g.group_email_id ORDER BY g.group_email_id DESC LIMIT 25'
        ));

        return $this->respond(['stats' => $Groups]);

    }

    public function getAllGroupEmails()
    {

        $Emails = GroupEmailModel::select('mw_group_email.email_id', 'mw_group_email.customer_id',
            'mw_group_email.group_email_id', 'mw_group_email.from_email', 'mw_group_email.subject')
            ->orderBy('group_email_id', 'desc')
            ->groupBy('group_email_id')
            ->get();

        return $this->respond(['stats' => $Emails]);

    }

    public function getSpamReports()
    {

        $Emails = GroupEmailBounceLogModel::select(
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

        $last_week1 = Carbon::parse('last week - 7 days')->format('Y-m-d');
        $last_week2 = Carbon::parse('this week + 7 days')->format('Y-m-d');

        $lastWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(log_id) AS count from `mw_group_email_log` where `date_added` between "'.$last_week1.'" and "'.$last_week2.'" GROUP BY DATE(date_added)'));

        return $this->respond(['stats' => $lastWeek]);

    }

    public function getBounceStats()
    {

        $this_week1 = Carbon::parse('last week - 7 days')->format('Y-m-d');
        $this_week2 = Carbon::parse('this week + 7 days')->format('Y-m-d');

        $thisWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(log_id) AS count from `mw_group_email_bounce_log` where `date_added` between "'.$this_week1.'" and "'.$this_week2.'" GROUP BY DATE(date_added)'));

        return $this->respond(['stats' => $thisWeek]);

    }

    public function getAbuseStats()
    {

        $this_week1 = Carbon::parse('this week - 14 days')->format('Y-m-d');
        $this_week2 = Carbon::parse('this week')->format('Y-m-d');

        $thisWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(report_id) AS count from `mw_group_email_abuse_report` where `date_added` between "'.$this_week1.'" and "'.$this_week2.'" GROUP BY DATE(date_added)'));

        return $this->respond(['stats' => $thisWeek]);

    }

    public function getAllTransactionalEmails()
    {

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

        $bounceServers = BounceServer::all();


        return $this->respond(['bounceservers' => $bounceServers]);

    }

    public function getDeliveryServerStatus()
    {

        $deliveryServers = DeliveryServerModel::all();

        return $this->respond(['deliveryServers' => $deliveryServers]);

    }

}
