<?php use App\Bounce;

defined('MW_PATH')||exit('No direct script access allowed');

/**
 * BounceHandlerCommand
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
class GroupsComplianceHandlerCommand extends CConsoleCommand
{

    public $verbose = 0;

    public $automate = 1;

    public function init()
    {

        parent::init();

    }

    public function actionIndex()
    {

        if ($this->automate)
        {
            $normal = Yii::app()->db->createCommand()
                ->select('AVG (bounce_report) as compliance_bounce_range,
                          AVG (abuse_report) as compliance_abuse_range,
                          AVG (unsubscribe_report) as compliance_unsub_range,
                          AVG (score) as score')
                ->from('mw_group_email_compliance_score')
                ->queryAll();

            $normal = new ArrayObject($normal[0]);

        }
        else
        {
            //check against normal ranges
            $criteria = new CDbCriteria();
            $criteria->addCondition('id = :id');
            $criteria->params = array('id' => 1);
            $normal = GroupOptions::Model()->findAll($criteria);
        }

        Yii::log(Yii::t('groups', 'Group Normal Parameters'), CLogger::LEVEL_INFO);

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Selecting Groups that are in-review...\n";
        }

        //Get Groups that are in-review
        $criteria = new CDbCriteria();
        $criteria->addCondition('status = "in-compliance" AND DATE_ADD(NOW(), INTERVAL 2 HOUR) > date_added ');
        $Groups = Group::model()->findAll($criteria);

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

        Yii::log(Yii::t('groups', 'Groups in-review '.rtrim($GroupIds, ',')), CLogger::LEVEL_INFO);

        foreach ($Groups as $group)
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Group ID ".$group->group_email_id."...\n";
            }

            Yii::log(Yii::t('groups', 'Groups ID '.$group->group_email_id), CLogger::LEVEL_INFO);

            //Get Bounces order by group id
            $criteria = new CDbCriteria();
            $criteria->addCondition('group_email_id = :group_email_id');
            $criteria->params = array('group_email_id' => $group->group_email_id);
            $bounce = GroupBounceLog::Model()->findAll($criteria);

            Yii::log(Yii::t('groups', 'Groups ID '.$group->group_email_id.' bounces: '.count($bounce)), CLogger::LEVEL_INFO);


            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Bounce Count: ".count($bounce)."...\n";
            }

            //get emails by group id
            $criteria = new CDbCriteria();
            $criteria->addCondition('group_email_id = :group_email_id');
            $criteria->addCondition('status = "in-review"');
            $criteria->params = array('group_email_id' => $group->group_email_id);
            $emails = GroupEmail::Model()->findAll($criteria);

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Emails Count: ".count($emails)."...\n";
            }

            Yii::log(Yii::t('groups', 'Groups ID '.$group->group_email_id.' emails: '.count($emails)), CLogger::LEVEL_INFO);


            $compliance[$group->group_email_id] = [
                'customer_id' => $group->customer_id,
                'email_count' => count($emails),
                'bounce_count' => count($bounce)

            ];

            //get abuse by customer id
            $criteria = new CDbCriteria();
            $criteria->addCondition('customer_id = :customer_id');
            $criteria->params = array('customer_id' => $group->customer_id);
            $abuse = GroupAbuseReport::Model()->findAll($criteria);

            Yii::log(Yii::t('groups', 'Groups ID '.$group->group_email_id.' abuse reports: '.count($abuse)), CLogger::LEVEL_INFO);

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Abuse Count: ".count($abuse)."...\n";
            }

            $compliance[$group->group_email_id]['abuse_report'] = count($abuse);

            //get unsubs by group id
            $criteria = new CDbCriteria();
            $criteria->addCondition('group_email_id = :group_email_id');
            $criteria->params = array('group_email_id' => $group->group_email_id);
            $unsub = GroupUnsubscribeReport::Model()->findAll($criteria);

            Yii::log(Yii::t('groups', 'Groups ID '.$group->group_email_id.' unsubs: '.count($unsub)), CLogger::LEVEL_INFO);

            $score = $bounceScore = $abuseScore = $unsubScore = 0;

            Yii::log(Yii::t('groups', 'Groups ID '.$group->group_email_id.' score: '.$score), CLogger::LEVEL_INFO);

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

            $score = $bounceScore + $abuseScore + $unsubScore / 3;

            $compliance_status = $group_status = $group_email_status = null;
            if (
                $bounceScore<=$normal[0]->compliance_bounce_range&&
                $abuseScore<=$normal[0]->compliance_abuse_range&&
                $unsubScore<=$normal[0]->compliance_unsub_range
            )
            {
                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Compliance Status set to approved...\n";
                }

                $compliance_status = $this->setComplianceStatus($group, GROUP::STATUS_APPROVED);
                $this->setGroupStatus($group, GROUP::STATUS_PENDING_SENDING);
                $this->setGroupEmailStatus($group, GROUP::STATUS_PENDING_SENDING);

                Yii::log(Yii::t('groups', 'Groups ID '.$group->group_email_id.' compliance status set to approved'), CLogger::LEVEL_INFO);

            }
            else
            {
                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Compliance Status set to manual-review...\n";
                }

                $compliance_status = $this->setComplianceStatus($group, GROUP::STATUS_MANUAL_REVIEW);
                $this->setGroupStatus($group, GROUP::STATUS_MANUAL_REVIEW);
            }

            $this->setGroupEmailComplianceReport($bounceScore, $abuseScore, $unsubScore, $compliance_status, $score);
            Yii::log(Yii::t('groups', 'Groups ID '.$group->group_email_id.' compliance status set to '.GROUP::STATUS_MANUAL_REVIEW), CLogger::LEVEL_INFO);


        }

    }

    public function setComplianceStatus($group, $status)
    {

        //update status of the status of group compliance table to pending-sending
        GroupEmailCompliance::model()->updateByPk(
            $group->group_email_id,
            [
                'compliance_status' => $status,
                'group_email_id = '.$group->group_email_id
            ]);

        return $status;

    }

    /**
     * @param $group
     * @param $status
     */
    public function setGroupStatus($group, $status)
    {

        //update the group status
        Group::model()->updateByPk(
            $group->group_email_id,
            [
                'status' => $status,
                'group_email_id = '.$group->group_email_id
            ]);
        return $status;

    }

    /**
     * @param $group
     * @param $status
     */
    public function setGroupEmailStatus($group, $status)
    {

        // Update emails to pending-sending status

        GroupEmail::model()
            ->updateAll(array('status' => $status), 'group_email_id = :group_email_id AND status = "in-review"',
                array('group_email_id' => $group->group_email_id));

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
        Yii::app()->db->createCommand($sql)->query();
    }

}