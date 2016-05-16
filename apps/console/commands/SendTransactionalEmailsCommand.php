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

        $options = Yii::app()->db->createCommand()
            ->select('*')
            ->from('mw_transactional_email_options')
            ->where('id=:id', array(':id' => 1))
            ->queryRow();


        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Options ".print_r($options, true)."\n";
        }

//        $statuses = array(TransactionalEmail::STATUS_SENDING, TransactionalEmail::STATUS_PENDING_SENDING);
        $campaignLimit = $options['groups_at_once'];

        //todo replace with model

        $transactionalEmailsGroupIds = Yii::app()->db->createCommand()
            ->select('transactional_email_group_id')
            ->from('mw_transactional_email')
            ->group('transactional_email_group_id')
            ->limit($campaignLimit)
            ->queryAll();

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] transactionalEmailsGroupIds ".print_r($transactionalEmailsGroupIds, true)."\n";
        }


        if (empty($transactionalEmailsGroupIds))
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] No Email Groups found for processing!\n";
            }
            return 0;
        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Found ".count($transactionalEmailsGroupIds)."
             email groups for processing, starting...\n";
        }

        $groupIds = array();
        foreach ($transactionalEmailsGroupIds as $groupId)
        {
            $groupIds[] = $groupId['transactional_email_group_id'];
        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Email Group IDs ".print_r($groupIds,
                    true)." email groups for processing, starting...\n";
        }

        $emailLimit = $options['emails_at_once'];

        foreach ($groupIds as $groupId)
        {
            $emails = TransactionalEmail::model()->findEmails(array($groupId, $emailLimit));

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Sending ".count($emails, true)." emails...\n";
            }
            $emails->send();
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