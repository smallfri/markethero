<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * SendTransactionalEmailsCommand
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.4.5
 */
class SendTransactionalEmailsCommand extends CConsoleCommand
{

    protected $_lockName;

    public $verbose = 0;

    public function actionIndex()
    {

        $mutex = Yii::app()->mutex;
        $lockName = $this->getLockName();

        if (!$mutex->acquire($lockName))
        {
            return 1;
        }

        // added in 1.3.4.7
        Yii::app()->hooks->doAction('console_command_transactional_emails_before_process', $this);

        $this->process();

        // added in 1.3.4.7
        Yii::app()->hooks->doAction('console_command_transactional_emails_after_process', $this);

        $mutex->release($lockName);
        return 0;
    }

    protected function process()
    {

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Processing of transactional emails starting...\n";
        }

        /*
         * Get Group options from the database, these are global
         *
         */

        $options = Yii::app()->db->createCommand()
            ->select('*')
            ->from('mw_transactional_email_options')
            ->where('id=:id', array(':id' => 1))
            ->queryRow();


        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Options ".print_r($options, true)."\n";
        }

        // Set Group Options
        $groupLimit = $options['groups_at_once'];

        $emailsAtOnce = $options['emails_at_once'];

        $complianceLimit = $options['compliance_limit'];

        // Get Groups that have status != sent
        $transactionalEmailsGroups = Yii::app()->db->createCommand()
            ->select('te.transactional_email_group_id, cl.threshold, tec.*')
            ->from('mw_transactional_email te')
            ->join('mw_transactional_email_group teg',
                'te.transactional_email_group_id=teg.transactional_email_group_id')
            ->join('mw_transactional_email_compliance tec',
                'tec.transactional_email_group_id=te.transactional_email_group_id')
            ->join('mw_compliance_levels cl', 'cl.id = tec.compliance_level_type_id')
            ->where('tec.compliance_status != "sent"')
            ->group('te.transactional_email_group_id')
            ->limit($groupLimit)
            ->queryAll();


        /*
         * If we don't have any groups, bail out.
         *
         */
        if (empty($transactionalEmailsGroups))
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] No Email Groups found for processing!\n";
            }
            return 0;
        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] transactionalEmailsGroup ".print_r($transactionalEmailsGroups, true)."\n";
        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Found ".count($transactionalEmailsGroups)." email groups for processing, starting...\n";
        }

        /*
         * Begin looping through the Groups
         *
         */

        foreach ($transactionalEmailsGroups as $group)
        {

            // Get count of emails for this group
            $count = Yii::app()->db->createCommand()
                ->select('count(*) as count')
                ->from('mw_transactional_email')
                ->where('transactional_email_group_id=:id', array(':id' => (int)$group['transactional_email_group_id']))
                ->queryRow();

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Found ".$count['count']." email(s)...\n";
            }

            $emailsToBeSent = $count['count'];

            //If the count is greater than the option emails at once, set emailsToBeSent to emails at once
            if ($count['count']>=$emailsAtOnce)
            {
                $emailsToBeSent = $emailsAtOnce;
            }

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] There are ".$emailsToBeSent." emails to be sent...\n";
            }

            /*
             * Check whether or not this group is in compliance review
             *
             */
            if ($group['compliance_status']=='first-review' AND $count['count']>=$complianceLimit)
            {
                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] This Group is in Compliance Review...\n";
                }

                // Set emails to be sent = threshold X count
                $emailsToBeSent = round($count['count']*$group['threshold']);

                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] There are ".$emailsToBeSent." emails to be sent...\n";
                }

                // Determine how many emails should be set to in-review status
                $in_review_count = $count['count']-$emailsToBeSent;

                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Setting ".$in_review_count." emails to in-review...\n";
                }

                // Update emails to in-review status
                TransactionalEmail::model()
                    ->updateAll(['status' => 'in-review'],
                        'transactional_email_group_id= '.$group['transactional_email_group_id'].' AND status = "pending-sending" ORDER BY email_id DESC LIMIT '.$in_review_count
                    );

                //update status of the group so we don't send anymore emails
                $TransactionalEmailCompliance = TransactionalEmailCompliance::model()
                    ->findByPk($group['transactional_email_group_id']);
                $TransactionalEmailCompliance->compliance_status = 'compliance-review';
                $TransactionalEmailCompliance->update();

            }
            elseif ($group['compliance_status']=='approved')
            {
                // Update emails to pending-sending status if this Group is no longer under review
                TransactionalEmail::model()
                    ->updateAll(['status' => 'pending-sending'],
                        'transactional_email_group_id= '.$group['transactional_email_group_id'].' AND status = "in-review"'
                    );
            }

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Preparing to send ".$emailsToBeSent." email(s)...\n";
            }

            $emails = TransactionalEmail::model()->findAll(array(
                'condition' => '`status` = "pending-sending" AND `send_at` < NOW() AND `retries` < `max_retries`',
                'order' => 'email_id ASC',
                'limit' => $emailsToBeSent
//                'offset' => $group['offset']
            ));
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Emails Count ".count($emails)."...\n";
            }
            /*
             * Send emails
             */
            foreach ($emails as $email)
            {
                $email->send();
            }
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Sent ".count($emails)." email(s)...\n";
            }

            // Set offset
//            $compliance = TransactionalEmailCompliance::model()->findByPk($group['transactional_email_group_id']);
//            $compliance->offset = count($emails)+$group['offset'];
//            $compliance->update();

        }

        /*
         *
         * originial code
         *
         *
         */
//        $offset = (int)Yii::app()->options->get('system.cron.transactional_emails.offset', 0);
//        $limit = 100;
//
//        $emails = TransactionalEmail::model()->findAll(array(
//            'condition' => '`status` = "unsent" AND `send_at` < NOW() AND `retries` < `max_retries`',
//            'order' => 'email_id ASC',
//            'limit' => $limit,
//            'offset' => $offset
//        ));
//
//        if (empty($emails))
//        {
//            Yii::app()->options->set('system.cron.transactional_emails.offset', 0);
//            return $this;
//        }
//        Yii::app()->options->set('system.cron.transactional_emails.offset', $offset+$limit);
//
//        foreach ($emails as $email)
//        {
//            $email->send();
//        }
//
//        Yii::app()
//            ->getDb()
//            ->createCommand('UPDATE {{transactional_email}} SET `status` = "sent" WHERE `status` = "unsent" AND send_at < NOW() AND retries >= max_retries')
//            ->execute();
//        Yii::app()
//            ->getDb()
//            ->createCommand('DELETE FROM {{transactional_email}} WHERE `status` = "unsent" AND send_at < NOW() AND date_added < DATE_SUB(NOW(), INTERVAL 1 MONTH)')
//            ->execute();
//
//        return $this;
    }

    protected function getLockName()
    {

        if ($this->_lockName!==null)
        {
            return $this->_lockName;
        }
        $offset = (int)Yii::app()->options->get('system.cron.transactional_emails.offset', 0);
        return $this->_lockName = md5(__FILE__.__CLASS__.$offset);
    }

}

/*
 * Limit each group to a max of X emails until the compliance review standards are met
 *
 * Record the limit that was used in the table mw_transaction_email_group as offset
 *
 * Before sending each group, we will need to pull back the offset and add it to the limit
 *
 * Before sending each group, we will need to pull back the compliance_status column of mw_transactiona_email_group
 *
 *
 *
 *
 */