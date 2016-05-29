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

    public function init()
    {

        parent::init();

    }

    public function actionIndex()
    {

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Selecting Groups that are in-review...\n";
        }

        //Get Groups that are in-review
        $criteria = new CDbCriteria();
        $criteria->addCondition('status = "in-review"');
        $Groups = Group::model()->findAll($criteria);

        $compliance = [];
        foreach ($Groups as $group)
        {
            //Get Bounces order by group id
            $criteria = new CDbCriteria();
            $criteria->addCondition('group_email_id = :group_email_id');
            $criteria->params = array('group_email_id' => $group->group_email_id);
            $bounce = GroupBounceLog::Model()->findAll($criteria);

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Bounce Count: ".count($bounce)."...\n";
            }

            //get emails by group id
            $criteria = new CDbCriteria();
            $criteria->addCondition('group_email_id = :group_email_id');
            $criteria->params = array('group_email_id' => $group->group_email_id);
            $emails = GroupUnsubscribeReport::Model()->findAll($criteria);

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Emails Count: ".count($emails)."...\n";
            }

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


            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Unsubscribe Count: ".count($unsub)."...\n";
            }

            $compliance[$group->group_email_id]['unsubscribe_report'] = count($unsub);
        }


        print_r($compliance);


        //check agaist normal ranges

        //if over normal, don't change email status and set group to requires manual review


    }

}