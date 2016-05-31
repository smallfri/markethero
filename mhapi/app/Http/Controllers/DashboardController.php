<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/27/16
 * Time: 7:35 AM
 */

namespace App\Http\Controllers;

use App\Bounce;
use App\CampaignAbuseModel;
use App\CampaignModel;
use App\Customer;
use App\DeliveryLogModel;
use App\GroupAbuseModel;
use App\GroupControlsModel;
use App\GroupEmailGroupsModel;
use App\GroupEmailModel;
use App\Lists;
use App\Segment;
use Illuminate\Http\Request;
use App\TransactionalEmailModel;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiController
{

    function __construct()
    {
    }

    public function index()
    {

        /*
         * This gathers delivery stats for 2 weeks starting today
         */


        $last_week1 = Carbon::parse('last week - 7 days');
        $last_week2 = Carbon::parse('last week');
        $this_week1 = Carbon::parse('this week - 7 days');
        $this_week2 = Carbon::parse('this week');

        $thisWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(email_id) AS count from `mw_group_email_log` where `date_added` between "'.$this_week1.'" and "'.$this_week2.'" GROUP BY DATE(date_added)'));
        $lastWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(email_id) AS count from `mw_group_email_log` where `date_added` between "'.$last_week1.'" and "'.$last_week2.'" GROUP BY DATE(date_added)'));

        $last_week = [];
        foreach ($lastWeek AS $key => $value)
        {
            $last_week[date('D', strtotime($value->date_added))] = $value->count;
        }

        $this_week = [];
        foreach ($thisWeek AS $key => $value)
        {
            $this_week[date('D', strtotime($value->date_added))] = $value->count;
        }

        $d = 1;
        $delivery_stats = [];
        for ($i = 1;$i<7;$i++)
        {

            switch ($d)
            {
                case 1:
                    $day = "Sun";
                    break;
                case 2:
                    $day = "Mon";
                    break;
                case 3:
                    $day = "Tues";
                    break;
                case 4:
                    $day = "Wed";
                    break;
                case 5:
                    $day = "Thurs";
                    break;
                case 6:
                    $day = "Fri";
                    break;
                case 7:
                    $day = "Sat";
                    break;

            }

            if (array_key_exists($day, $this_week))
            {
                $delivery_stats[$day] = $day.','.$this_week[$day];
            }
            else
            {
                $delivery_stats[$day] = $day.',0';
            }

            if (array_key_exists($day, $last_week))
            {
                $delivery_stats[$day] = $delivery_stats[$day].','.$last_week[$day];
            }
            else
            {
                $delivery_stats[$day] = $delivery_stats[$day].',0';
            }

            $d++;

        }
        $d_stats = null;
        foreach ($delivery_stats AS $key => $value)
        {
            $newkey = '"'.$key.'"';
            $value = str_replace($key, $newkey, $value);
            $d_stats .= '['.$value.'],';
        }
        $delivery_stats = rtrim($d_stats, ",");


        $thisWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(log_id) AS count from `mw_group_email_bounce_log` where `date_added` between "'.$this_week1.'" and "'.$this_week2.'" GROUP BY DATE(date_added)'));
        $lastWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(log_id) AS COUNT from `mw_group_email_bounce_log` where `date_added` between "'.$last_week1.'" and "'.$last_week2.'" GROUP BY DATE(date_added)'));

        $last_week = [];
        foreach ($lastWeek AS $key => $value)
        {
            $last_week[date('D', strtotime($value->date_added))] = $value->count;
        }

        $this_week = [];
        foreach ($thisWeek AS $key => $value)
        {
            $this_week[date('D', strtotime($value->date_added))] = $value->count;
        }

        $d = 1;
        $bounce_stats = [];
        for ($i = 1;$i<7;$i++)
        {

            switch ($d)
            {
                case 1:
                    $day = "Sun";
                    break;
                case 2:
                    $day = "Mon";
                    break;
                case 3:
                    $day = "Tues";
                    break;
                case 4:
                    $day = "Wed";
                    break;
                case 5:
                    $day = "Thurs";
                    break;
                case 6:
                    $day = "Fri";
                    break;
                case 7:
                    $day = "Sat";
                    break;

            }

            if (array_key_exists($day, $this_week))
            {
                $bounce_stats[$day] = $day.','.$this_week[$day];
            }
            else
            {
                $bounce_stats[$day] = $day.',0';
            }

            if (array_key_exists($day, $last_week))
            {
                $bounce_stats[$day] = $bounce_stats[$day].','.$last_week[$day];
            }
            else
            {
                $bounce_stats[$day] = $bounce_stats[$day].',0';
            }

            $d++;

        }
        $b_stats = null;
        foreach ($bounce_stats AS $key => $value)
        {
            $newkey = '"'.$key.'"';
            $value = str_replace($key, $newkey, $value);
            $b_stats .= '['.$value.'],';
        }
        $bounce_stats = rtrim($b_stats, ",");


        /*
         * This gathers Abuse stats for 2 weeks starting today
         */

        $thisWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(report_id) AS count from `mw_group_email_abuse_report` where `date_added` between "'.$this_week1.'" and "'.$this_week2.'" GROUP BY DATE(date_added)'));
        $lastWeek
            = DB::select(DB::raw('select DATE(date_added) AS date_added,COUNT(report_id) AS COUNT from `mw_group_email_abuse_report` where `date_added` between "'.$last_week1.'" and "'.$last_week2.'" GROUP BY DATE(date_added)'));

        $last_week = [];
        foreach ($lastWeek AS $key => $value)
        {
            $last_week[date('D', strtotime($value->date_added))] = $value->count;
        }

        $this_week = [];
        foreach ($thisWeek AS $key => $value)
        {
            $this_week[date('D', strtotime($value->date_added))] = $value->count;
        }

        $d = 1;
        $abuse_stats = [];
        for ($i = 1;$i<7;$i++)
        {

            switch ($d)
            {
                case 1:
                    $day = "Sun";
                    break;
                case 2:
                    $day = "Mon";
                    break;
                case 3:
                    $day = "Tues";
                    break;
                case 4:
                    $day = "Wed";
                    break;
                case 5:
                    $day = "Thurs";
                    break;
                case 6:
                    $day = "Fri";
                    break;
                case 7:
                    $day = "Sat";
                    break;

            }

            if (array_key_exists($day, $this_week))
            {
                $abuse_stats[$day] = $day.','.$this_week[$day];
            }
            else
            {
                $abuse_stats[$day] = $day.',0';
            }

            if (array_key_exists($day, $last_week))
            {
                $abuse_stats[$day] = $abuse_stats[$day].','.$last_week[$day];
            }
            else
            {
                $abuse_stats[$day] = $abuse_stats[$day].',0';
            }

            $d++;

        }
        $a_stats = null;
        foreach ($abuse_stats AS $key => $value)
        {
            $newkey = '"'.$key.'"';
            $value = str_replace($key, $newkey, $value);
            $a_stats .= '['.$value.'],';
        }
        $abuse_stats = rtrim($a_stats, ",");


        $emails_monthly = GroupEmailModel::select('email_id', 'date_added', DB::raw('count(1) AS count'))
            ->groupBy(DB::raw('MONTH(date_added)'))
            ->get();


        $monthly_emails = null;
        foreach ($emails_monthly as $month)
        {
            $monthly_emails .= '["'.date('M', strtotime($month['date_added'])).'", '.$month['count'].'],';
        }

        $monthly_emails = rtrim($monthly_emails, ",");
//        dd($monthly_emails);

        /*
         *  Get counts
         */
        $groups = GroupEmailGroupsModel::all()->count();
        $transactionals = TransactionalEmailModel::all()->count();
        $group_emails_count = GroupEmailGroupsModel::all()->count();
        $customer_count = Customer::all()->count();

        $data = [
            'delivery_stats' => $delivery_stats,
            'bounce_stats' => $bounce_stats,
            'abuse_stats' => $abuse_stats,
            'groups' => $groups,
            'transactionals' => $transactionals,
            'monthly_emails' => $monthly_emails,
            'group_emails_count' => $group_emails_count,
            'customer_count' => $customer_count,

        ];

        return view('dashboard.index', $data);

    }

//    public function subscribers()
//    {
//        $Subscribers = SubscriberModel::all();
//
//        $data = [
//                    'subscribers' => $Subscribers
//
//
//                ];
//
//                return view('dashboard.subscribers.index', $data);
//    }

    public function groups()
    {

        $Groups = GroupEmailGroupsModel::select('mw_customer.*', 'mw_group_email_groups.status AS status', 'mw_group_email_groups.*')
            ->Join('mw_customer', 'mw_customer.customer_id', '=', 'mw_group_email_groups.customer_id')
            ->orderBy('mw_group_email_groups.group_email_id', 'desc')
            ->get();

        $data = [
            'groups' => $Groups


        ];

        return view('dashboard.groups.index', $data);
    }

    public function customers()
    {

        $Customer = Customer::select('*')
            ->get();

        $data = [
            'customers' => $Customer


        ];

        return view('dashboard.customers.index', $data);
    }

    public function transactional_emails()
    {

        $Emails = TransactionalEmailModel::select('mw_transactional_email.*', 'log.message')
            ->join('mw_transactional_email_log AS log', 'log.email_id', '=', 'mw_transactional_email.email_id')
            ->orderBy('mw_transactional_email.email_id', 'desc')
            ->get();


        $data = [
            'emails' => $Emails


        ];

        return view('dashboard.emails.index', $data);
    }

    public function group_emails()
    {

        if (isset($_GET['id']))

        {
            $Emails = GroupEmailModel::select('mw_group_email.*', 'log.message')
                ->leftJoin('mw_group_email_log AS log', 'log.email_id', '=', 'mw_group_email.email_id')
                ->where('group_email_id', '=', $_GET['id'])
                ->orderBy('group_email_id', 'desc')
                ->get();
        }
        else
        {
            $Emails = GroupEmailModel::select('mw_group_email.*', 'log.message')
                ->leftJoin('mw_group_email_log AS log', 'log.email_id', '=', 'mw_group_email.email_id')
                ->orderBy('group_email_id', 'desc')
                ->get();
        }


        $data = [
            'emails' => $Emails


        ];

        return view('dashboard.group-emails.index', $data);
    }

    public function controls(Request $request)
    {


        $Controls = GroupControlsModel::find(1);
        if ($request->input('submit'))
        {
            $Controls->groups_at_once = $request->input('groups_at_once');
            $Controls->emails_at_once = $request->input('emails_at_once');
            $Controls->change_server_at = $request->input('change_server_at');
            $Controls->compliance_limit = $request->input('compliance_limit');
            $Controls->compliance_abuse_range = $request->input('compliance_abuse_range');
            $Controls->compliance_unsub_range = $request->input('compliance_unsub_range');
            $Controls->compliance_bounce_range = $request->input('compliance_bounce_range');
            $Controls->save();
        }
        $data = ['controls' => $Controls];
        return view('dashboard.groups.controls', $data);

    }

    public function store()
    {

        echo "user created";

        $user = User::find(4);

        $user->password = bcrypt('KjV9g2JcyFGAHng');

        $user->save();

        echo "updated";
        exit;

        return User::create([
            'first_name' => 'russell',
            'email' => 'russell@smallfri.com',
            'password' => bcrypt('jack1999'),
        ]);
    }
}