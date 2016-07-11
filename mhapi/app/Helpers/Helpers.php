<?php

namespace App\Helpers;

use App\BounceServer;

class Helpers
{

    function getCustomerByCampaignId($campaign_id)
    {

        $Customer = DB::table('mw_campaign')->where('campaign_id', '=', $campaign_id)->get();

        if (!empty($Customer[0]))
        {
            return $Customer->customer_id;
        }

    }

    function getCampaignIdsByCustomer($customer_id)
    {

        $Campaign_ids = DB::table('mw_campaign')->select('campaign_id')->where('customer_id', '=', $customer_id)->get();

        $campaign_array = array();
        foreach ($Campaign_ids as $row)
        {
            $campaign_array[] = $row->campaign_id;
        }

        return $campaign_array;
    }

    function getBouncesByCampaignIds($campaign_ids = array())
    {

        $Bounces = DB::table('mw_campaign_bounce_log')->whereIn('campaign_id', $campaign_ids)->get();

        return $Bounces;
    }

    function getSpamByCampaignIds($campaign_ids = array())
    {

        $Spam = DB::table('mw_campaign_abuse_report')->where('campaign_id', '=', $campaign_ids)->get();

        return $Spam;
    }

    function getBouncesByCustomerId($customer_id)
    {

        $Campaign_ids = $this->getCampaignIdsByCustomer($customer_id);

        $Bounces = $this->getBouncesByCampaignIds($Campaign_ids);

        return $Bounces;
    }

    function getSpamByCustomerId($customer_id)
    {

        $Campaign_ids = $this->getCampaignIdsByCustomer($customer_id);

        $Spam = $this->getSpamByCampaignIds($Campaign_ids);

        return $Spam;

    }

    static function mapToClass($args)
    {
        if (isset($args[0]))
        {
            return (object)$args[0];
        }
        return false;
    }

    static function findBounceServerSenderEmail($bounce_server_id)
    {
        $bounceServer = BounceServer::find($bounce_server_id);
        return $bounceServer->email;
    }
}