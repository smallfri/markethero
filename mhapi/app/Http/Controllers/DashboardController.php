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
use App\DeliveryLogModel;
use App\Lists;
use App\Segment;
use App\SubscriberModel;
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

        $delivery = null;
        $d = 1;
        for($i = 1;$i<7;$i++)
        {

            switch($d)
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

            $last_week = Carbon::parse('last week - 7 days');
            $this_week = Carbon::parse('this week - 7 days');

            $last_week = DeliveryLogModel::where('date_added', '>=', Carbon::parse($last_week.' + '.$i.' day'))
                ->get()
                ->count();

            $this_week = DeliveryLogModel::where('date_added', '>=', Carbon::parse($this_week.' + '.$i.' day'))
                ->get()
                ->count();

            $delivery .= '["'.$day.'",'.$last_week.','.$this_week.'],';
            $d++;
        }

        $delivery_stats = rtrim($delivery, ",");

        /*
         * This gathers Bounce stats for 2 weeks starting today
         */

        $bounce = null;
        $d = 1;
        for($i = 1;$i<7;$i++)
        {

            switch($d)
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

            $last_week = Carbon::parse('last week - 7 days');
            $this_week = Carbon::parse('this week - 7 days');

            $last_week = Bounce::where('date_added', '>=', Carbon::parse($last_week.' + '.$i.' day'))
                ->get()
                ->count();

            $this_week = Bounce::where('date_added', '>=', Carbon::parse($this_week.' + '.$i.' day'))
                ->get()
                ->count();

            $bounce .= '["'.$day.'",'.$last_week.','.$this_week.'],';
            $d++;
        }

        $bounce_stats = rtrim($bounce, ",");

        /*
         * This gathers Abuse stats for 2 weeks starting today
         */

        $abuse = null;
        $d = 1;
        for($i = 1;$i<7;$i++)
        {

            switch($d)
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

            $last_week = Carbon::parse('last week - 7 days');
            $this_week = Carbon::parse('this week - 7 days');

            $last_week = CampaignAbuseModel::where('date_added', '>=', Carbon::parse($last_week.' + '.$i.' day'))
                ->get()
                ->count();

            $this_week = CampaignAbuseModel::where('date_added', '>=', Carbon::parse($this_week.' + '.$i.' day'))
                ->get()
                ->count();

            $abuse .= '["'.$day.'",'.$last_week.','.$this_week.'],';
            $d++;
        }

        $abuse_stats = rtrim($bounce, ",");

        $Subs_month = SubscriberModel::select('subscriber_id', 'date_added', DB::raw('count(1) AS count'))->groupBy(DB::raw('MONTH(date_added)'))->get();

        $monthly_subscriptions = null;
        foreach($Subs_month as $month)
        {
            $monthly_subscriptions .= '["'.date('M',strtotime($month['date_added'])).'", '.$month['count'].'],';
        }

        $monthly_subscriptions = rtrim($monthly_subscriptions, ",");

        /*
         *  Get counts
         */
        $subscribers = SubscriberModel::all()->count();
        $unigue_subscribers = SubscriberModel::all()->groupBy('email')->count();
        $lists = Lists::all()->count();
        $campaigns = CampaignModel::all()->count();
        $transactionals = TransactionalEmailModel::all()->count();
        $segments = Segment::all()->count();


        $data = [
            'delivery_stats' => $delivery_stats,
            'bounce_stats' => $bounce_stats,
            'abuse_stats' => $abuse_stats,
            'subscribers' => $subscribers,
            'lists' => $lists,
            'campaigns' => $campaigns,
            'transactionals' => $transactionals,
            'unigue_subscribers' => $unigue_subscribers,
            'segments' => $segments,
            'monthly_subscriptions' => $monthly_subscriptions



        ];

        return view('dashboard.index', $data);

    }

    public function subscribers()
    {
        $Subscribers = SubscriberModel::all();

        $data = [
                    'subscribers' => $Subscribers


                ];

                return view('dashboard.subscribers.index', $data);
    }

    public function campaigns()
    {
        $Campaigns = CampaignModel::select('mw_campaign.*','l.name AS listname', 's.name AS segmentname')->join('mw_list AS l','l.list_id','=','mw_campaign.list_id')->Leftjoin('mw_list_segment AS s','s.segment_id','=','mw_campaign.segment_id')->get();

//        dd($Campaigns);

        $data = [
                    'campaigns' => $Campaigns


                ];

                return view('dashboard.campaigns.index', $data);
    }

    public function transactional_emails()
    {
        $Emails = TransactionalEmailModel::select('mw_transactional_email.*','log.message')->join('mw_transactional_email_log AS log','log.email_id','=','mw_transactional_email.email_id')->get();


        $data = [
                    'emails' => $Emails


                ];

                return view('dashboard.emails.index', $data);
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