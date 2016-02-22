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
use App\SubscriberModel;
use App\User;
use Carbon\Carbon;

class DashboardController extends ApiController
{

    function __construct()
    {

    }

    public function index()
    {

        $Deliveries = DeliveryLogModel::where('date_added', '>=', Carbon::parse('this week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week + 5 days'))
            ->get()
            ->count();

        //weekly delivery %
        $lastWeekDeliveries = DeliveryLogModel::where('date_added', '>=', Carbon::parse('last week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week - 2 days'))
            ->get()
            ->count();

        $thisWeekDeliveries = DeliveryLogModel::where('date_added', '>=', Carbon::parse('this week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week + 5 days'))
            ->get()
            ->count();

        $deliveries = 0;
        if($thisWeekDeliveries>0 and $lastWeekDeliveries>0)
        {
            $deliveries = $lastWeekDeliveries/$thisWeekDeliveries*100;
        }

        $Bounces = Bounce::where('bounce_type', '=', 'hard')
            ->where('date_added', '>=', Carbon::parse('this week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week + 5 days'))
            ->get()
            ->count();

        $lastWeekBounces = Bounce::where('date_added', '>=', Carbon::parse('last week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week - 2 days'))
            ->get()
            ->count();

        $thisWeekBounces = Bounce::where('date_added', '>=', Carbon::parse('this week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week + 5 days'))
            ->get()
            ->count();

        $bounces = 0;
        if($lastWeekBounces>0 and $thisWeekBounces>0)
        {
            $bounces = $lastWeekBounces/$thisWeekBounces*100;
        }

        $Abuses = CampaignAbuseModel::where('date_added', '>=', Carbon::parse('this week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week + 5 days'))
            ->get()
            ->count();

        $Campaigns = CampaignModel::where('date_added', '>=', Carbon::parse('this week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week + 5 days'))
            ->get()
            ->count();

        $lastWeekCampagigns = CampaignModel::where('date_added', '>=', Carbon::parse('last week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week - 2 days'))
            ->get()
            ->count();

        $thisWeekCampaigns = CampaignModel::where('date_added', '>=', Carbon::parse('this week - 1 day'))
            ->where('date_added', '<', Carbon::parse('this week + 5 days'))
            ->get()
            ->count();

        $campaigns = 0;
        if($lastWeekCampagigns>0 and $thisWeekCampaigns>0)
        {
            $bounces = $lastWeekCampagigns/$thisWeekCampaigns*100;
        }

        $Lists = Lists::all()->count();

        $Subscribers = SubscriberModel::all()->count();

        $data = [
            'deliveries' => $Deliveries,
            'deliveries_percent' => $deliveries,
            'bounces' => $Bounces,
            'bounce_percent' => $bounces,
            'abuses' => $Abuses,
            'campaigns' => $Campaigns,
            'campaigns_precent' => $campaigns,
            'lists'=> $Lists,
            'subscribers' => $Subscribers
        ];

        return view('dashboard.index', $data);

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