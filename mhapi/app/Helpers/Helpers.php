<?php

namespace App\Helpers;

use App\Logger;
use App\Models\BlacklistModel;
use App\Models\BounceServer;
use App\Models\GroupControlsModel;
use App\Models\GroupEmailComplianceLevelsModel;
use App\Models\GroupEmailComplianceModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;

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

        $bounceServer = \App\Models\BounceServer::find($bounce_server_id);
        return $bounceServer->email;
    }

    /**
     * This method returns the options and settings from the db.
     *
     * @return \stdClass
     */
    public function getOptions()
    {

        $options = GroupControlsModel::find(1);

        return $options;

    }

    function checkComplianceStatus($group_email_id)
    {

        $compliance = GroupEmailComplianceModel::find($group_email_id);
        if ($compliance['compliance_status']=='in-review')
        {
            return $compliance['compliance_level_type_id'];
        }

        return false;
    }

    function getLeadsCount($group_email_id)
    {

        $compliance = GroupEmailComplianceModel::find($group_email_id);

        if (!empty($compliance))
        {
            $compliance->leads_count;
        }

        return false;
    }

    function countSent($group_email_id)
    {

        $count = GroupEmailModel::where('group_email_id', '=', $group_email_id)->where('status','=','sent')->count();

            return $count;
    }

    /**
     * Checks for an email on the blacklist
     *
     * @param $email
     * @param $customerId
     * @return bool
     */
    public function isBlacklisted($email, $customerId)
    {

        $blacklist = BlacklistModel::where('email', '=', $email)->where('customer_id', '=', $customerId)->first();

        if (!empty($blacklist))
        {
            return true;
        }
        return false;
    }

    /**
     * Adds an email to the blacklist by customer id and email id
     *
     * @param $email
     * @param $customerId
     */
    public function addToBlacklist($email, $customerId)
    {

        $blackList = new BlacklistModel();

        $blackList->email_id = $email['primaryKey'];
        $blackList->reason = 'Invalid email address format!';
        $blackList->customer_id = $customerId;
        $blackList->date_added = new \DateTime();
        $blackList->Save();

        Logger::addProgress('This email has been blacklisted '.$email['primaryKey'], 'Email Blacklisted');

    }

    /**
     * This method handles compliance, it will send a number of emails determined by the options, and place the
     * remainder in-review status until the group has been approved.
     *
     * @param $groups
     */
    protected function complianceHandler($groups)
    {

        foreach ($groups AS $group)
        {

            $group->compliance = GroupEmailComplianceModel::find($group->group_email_id);

            if (empty($group->compliance))
            {
                continue;
            }

            $group->compliance->compliance_levels
                = GroupEmailComplianceLevelsModel::find($group->compliance->compliance_level_type_id);

            $count = GroupEmailModel::where('group_email_id', '=', $group->group_email_id)->count();

            $options = $this->getOptions();

            if ($group->compliance->compliance_status=='in-review' AND $count>=$options->compliance_limit)
            {

                $this->updateGroupStatus($group->group_email_id, GroupEmailGroupsModel::STATUS_COMPLIANCE_REVIEW);

                // Set emails to be sent = threshold X count
                $emailsToBeSent = ceil($count*$group->compliance->compliance_levels->threshold);


                // Determine how many emails should be set to in-review status
                $in_review_count = $count-$emailsToBeSent;

                // Update emails to in-review status
                GroupEmailModel::where('group_email_id', '=', $group->group_email_id)
                    ->where('status', '=', 'pending-sending')
                    ->orderBy('email_id', 'asc')
                    ->limit($in_review_count)
                    ->update(['status' => GroupEmailGroupsModel::STATUS_IN_REVIEW]);

            }
            elseif ($group->compliance->compliance_status=='approved')
            {
                // Update emails to pending-sending status if this Group is no longer under review
                GroupEmailModel::where('group_email_id', '=', $group->group_email_id)->where('status', '=', 'in-review')
                    ->update(['status' => GroupEmailGroupsModel::STATUS_PENDING_SENDING]);
            }
        }
    }

    /**
     * Updates the group status by id and status.
     *
     * @param $id
     * @param $status
     */
    public function updateGroupStatus($id, $status)
    {

        GroupEmailGroupsModel::where('group_email_id', $id)
            ->update(['status' => $status]);

        return;
    }
}