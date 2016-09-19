<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;


/**
 * Class GroupsComplianceHandlerCommand
 * @package App\Console\Commands
 */
use App\Models\ComplianceScoreModel;
use App\Models\GroupControlsModel;
use App\Models\GroupEmailAbuseModel;
use App\Models\GroupEmailBounceLogModel;
use App\Models\GroupEmailComplianceModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;
use App\Models\UnsubscribeModel;
use Illuminate\Console\Command;
use DB;

class GroupsComplianceHandlerCommand extends Command
{

    protected $signature = 'compliance:check';

    protected $description = 'Checks for compliance';

    public $verbose = 0;

    public $automate = 0;

    public function init()
    {

        parent::init();

    }

    public function handle()
    {

        if ($this->automate)
        {

            $normal = ComplianceScoreModel::select(DB::raw('AVG (bounce_report) as compliance_bounce_range,
                                          AVG (abuse_report) as compliance_abuse_range,
                                          AVG (unsubscribe_report) as compliance_unsub_range,
                                          AVG (score) as score'))->get();

        }
        else
        {
            //check against normal ranges
            $normal = $this->getOptions();
        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Selecting Groups that are in-review...\n";
        }

        //Get Groups that are in-review
        $Groups
            = DB::select("SELECT * FROM mw_group_email_groups WHERE status = 'in-compliance' AND DATE_ADD(now(), INTERVAL 2 HOUR) > started_at");

        $compliance = [];
        $GroupIds = null;
        foreach ($Groups as $group)
        {
            $GroupIds .= $group->group_email_id.',';
        }
        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Groups that are in-review ".rtrim($GroupIds, ',')."...\n";
        }


        foreach ($Groups as $group)
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Group ID ".$group->group_email_id."...\n";
            }

            $bounce = GroupEmailBounceLogModel::where('group_id', '=', $group->group_email_id)->get();

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Bounce Count: ".count($bounce)."...\n";
            }

//            //get emails by group id
//            $criteria = new CDbCriteria();
//            $criteria->addCondition('group_email_id = :group_email_id');
//            $criteria->addCondition('status = "in-review"');
//            $criteria->params = array('group_email_id' => $group->group_email_id);
//            $emails = GroupEmail::Model()->findAll($criteria);
//

            $emails = GroupEmailModel::where('status', '=', 'in-review')
                ->where('group_email_id', '=', $group->group_email_id)
                ->get();

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Emails Count: ".count($emails)."...\n";
            }


            $compliance[$group->group_email_id] = [
                'customer_id' => $group->customer_id,
                'email_count' => count($emails),
                'bounce_count' => count($bounce)

            ];

//            //get abuse by customer id
//            $criteria = new CDbCriteria();
//            $criteria->addCondition('customer_id = :customer_id');
//            $criteria->params = array('customer_id' => $group->customer_id);
//            $abuse = GroupAbuseReport::Model()->findAll($criteria);

            $abuse = GroupEmailAbuseModel::where('customer_id', '=', $group->customer_id)->get();


            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Abuse Count: ".count($abuse)."...\n";
            }

            $compliance[$group->group_email_id]['abuse_report'] = count($abuse);

            //get unsubs by group id
//            $criteria = new CDbCriteria();
//            $criteria->addCondition('group_email_id = :group_email_id');
//            $criteria->params = array('group_email_id' => $group->group_email_id);
//            $unsub = GroupUnsubscribeReport::Model()->findAll($criteria);

            $unsub = UnsubscribeModel::where('group_email_id', '=', $group->group_email_id)->get();


            $score = $bounceScore = $abuseScore = $unsubScore = 0;

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Unsubscribe Count: ".count($unsub)."...\n";
            }

            $compliance[$group->group_email_id]['unsubscribe_report'] = count($unsub);

            if (count($bounce)>0)
            {
                $bounceScore = count($bounce)/count($emails);

                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Bounce Score: ".$bounceScore."...\n";
                }
            }

            if (count($abuse)>0)
            {
                $abuseScore = count($abuse)/count($emails)*2.0; //weighted to count more

                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Abuse Score: ".$abuseScore."...\n";
                }
            }

            if (count($unsub)>0)
            {
                $unsubScore = count($unsub)/count($emails)*1.5; //weighted to count more

                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Unsubscribe Score: ".$unsubScore."...\n";
                }
            }

            $score = $bounceScore+$abuseScore+$unsubScore/3;

            $compliance_status = $group_status = $group_email_status = null;
            print_r($normal);
            if (
                $bounceScore<=$normal['compliance_bounce_range']&&
                $abuseScore<=$normal['compliance_abuse_range']&&
                $unsubScore<=$normal['compliance_unsub_range']
            )
            {
                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Compliance Status set to approved...\n";
                }

                $compliance_status = $this->setComplianceStatus($group, GroupEmailGroupsModel::STATUS_APPROVED);
                $this->setGroupStatus($group, GroupEmailGroupsModel::STATUS_PENDING_SENDING);
                $this->setGroupEmailStatus($group, GroupEmailGroupsModel::STATUS_PENDING_SENDING);

            }
            else
            {
                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Compliance Status set to manual-review...\n";
                }

                $compliance_status = $this->setComplianceStatus($group, GroupEmailGroupsModel::STATUS_MANUAL_REVIEW);
                $this->setGroupStatus($group, GroupEmailGroupsModel::STATUS_MANUAL_REVIEW);
            }

            $this->setGroupEmailComplianceReport($bounceScore, $abuseScore, $unsubScore, $compliance_status, $score);

        }

    }

    public function setComplianceStatus($group, $status)
    {

        //update status of the status of group compliance table to pending-sending
//        GroupEmailCompliance::model()->updateByPk(
//            $group->group_email_id,
//            [
//                'compliance_status' => $status,
//                'group_email_id = '.$group->group_email_id
//            ]);


        GroupEmailComplianceModel::where('group_email_id', '=', $group->group_email_id)
            ->update(['compliance_status' => $status]);
        return $status;

    }

    /**
     * @param $group
     * @param $status
     */
    public function setGroupStatus($group, $status)
    {

        //update the group status
//        Group::model()->updateByPk(
//            $group->group_email_id,
//            [
//                'status' => $status,
//                'group_email_id = '.$group->group_email_id
//            ]);

        GroupEmailGroupsModel::where('group_email_id', '=', $group->group_email_id)
            ->update(['status' => $status]);
        return $status;

    }

    /**
     * @param $group
     * @param $status
     */
    public function setGroupEmailStatus($group, $status)
    {

        // Update emails to pending-sending status

//        GroupEmail::model()
//            ->updateAll(array('status' => $status), 'group_email_id = :group_email_id AND status = "in-review"',
//                array('group_email_id' => $group->group_email_id));

        GroupEmailGroupsModel::where('group_email_id', '=', $group->group_email_id)
            ->where('status', '=', 'in-review');
        update(['status' => $status]);
        return $status;

    }

    /**
     * @param $bounceScore
     * @param $abuseScore
     * @param $unsubScore
     * @param $score
     * @param $compliance_status
     */
    public function setGroupEmailComplianceReport($bounceScore, $abuseScore, $unsubScore, $compliance_status, $score)
    {

        $sql
            = 'INSERT INTO mw_group_email_compliance_score SET
                  bounce_report ='.$bounceScore.'
                  , abuse_report='.$abuseScore.'
                  , unsubscribe_report = '.$unsubScore.'
                  , score = '.$score.'
                  , date_added = now()';
        DB::select($sql);
    }

    protected function getOptions()
    {

        $options = GroupControlsModel::find(1);

        return $options;

    }
}