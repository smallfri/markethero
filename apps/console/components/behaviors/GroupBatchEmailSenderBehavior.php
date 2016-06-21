<?php

/**
 * /**
 * groupsenderBehavior
 *
 * @author russell hudson <russell@smallfri.com>
 * @since 2.0
 */
class GroupBatchEmailSenderBehavior extends CBehavior
{

    public $emails_at_once = 0;

    public $emails_per_minute = 0;

    // reference flag for campaigns limit
    public $groups_limit = 0;

    public $compliance_limit = 0;

    // reference flag for campaigns offset
    public $groups_offset = 0;

    public $verbose = 0;

    public $error_level = 0; //0=off

    public function sendBatches()
    {


        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Starting sendGroups command!\n";
        }
        $batch = $this->getOwner();

//        if (!$group->getIsActive())
//        {
//            if ($this->error_level>0)
//            {
//                Yii::log(Yii::t('groups', 'This customer is inactive!'), CLogger::LEVEL_ERROR);
//            }
//
//            $group->saveStatus(Group::STATUS_PAUSED);
//
//            if ($this->verbose)
//            {
//                echo "[".date("Y-m-d H:i:s")."] The above customer is not active, group has been paused!\n";
//            }
//
//            return 0;
//        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Sending batch id ".$batch->group_batch_id."!\n";
        }

        // Get count of emails for this group
        $count = Yii::app()->db->createCommand()
            ->select('count(*) as count')
            ->from('mw_group_email')
            ->where('group_batch_id=:id AND status = "pending-sending"', array(':id' => (int)$batch->group_batch_id))
            ->queryRow();

        if ($count['count']<1)
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] This group has no emails to process...\n";
            }

            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'Group '.$batch->group_email_id.' This group has no emails to process...'),
                    CLogger::LEVEL_INFO);
            }
            return 0;
        }

        // put proper status
        $batch->saveStatus(Group::STATUS_PROCESSING);

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Found ".$count['count']." email(s)...\n";
        }

        if ($this->error_level>0)
        {
            Yii::log(Yii::t('groups', 'Group '.$batch->group_email_id.' Count '.$count['count']), CLogger::LEVEL_INFO);
        }


        $criteria = new CDbCriteria();
        $criteria->select = '*';
        $criteria->condition
            = '`status` = "'.GROUP::STATUS_PENDING_SENDING.'" AND `send_at` < NOW() AND group_batch_id = '.$batch->group_batch_id.' AND log.email_id IS NULL';
        $criteria->order = 'email_id ASC';
        $criteria->join = 'LEFT JOIN mw_group_email_log AS log ON log.email_id = t.email_id';
        $emails = GroupEmail::model()->findAll($criteria);

        if ($this->error_level>2)
        {
            Yii::log(Yii::t('groups', 'Group '.$batch->group_email_id.' gross email count '.count($emails)),
                CLogger::LEVEL_INFO);
        }
        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Gross Emails Count ".count($emails)."...\n";

        }

        $dsParams = array('customerCheckQuota' => false, 'useFor' => array(DeliveryServer::USE_FOR_GROUPS));
        $server = DeliveryServer::pickGroupServers(0, $batch, $dsParams);

        if (empty($server))
        {
            if ($this->error_level>2)
            {
                Yii::log(Yii::t('groups',
                    'Cannot find a valid server to send the Group email, aborting until a delivery server is available!'),
                    CLogger::LEVEL_ERROR);
            }
            if ($this->verbose)
            {
                echo "\n[".date("Y-m-d H:i:s")."] Unable to find a valid delivery server, aborting until a delivery server is available!\n";
            }

            return 0;
        }

        if ($this->verbose)
        {
            echo "OK\n";
        }

        if ($this->verbose)
        {
            $timeStart = microtime(true);
            echo "[".date("Y-m-d H:i:s")."] Searching for emails to send for this group...\n";
        }

        try
        {

            $mailerPlugins = array(
                'loggerPlugin' => true,
            );

            $sendAtOnce = $this->emails_at_once;
            if (!empty($sendAtOnce))
            {
                $mailerPlugins['antiFloodPlugin'] = array(
                    'sendAtOnce' => $sendAtOnce,
                    'pause' => 0,
                );
            }

            $perMinute = $this->emails_per_minute;
            if (!empty($perMinute))
            {
                $mailerPlugins['throttlePlugin'] = array(
                    'perMinute' => $perMinute,
                );
            }

            $processedCounter = 0;
            $serverHasChanged = false;
            $changeServerAt = 100;

            //since 1.3.4.9
            $dsParams = array(
                'customerCheckQuota' => false,
                'serverCheckQuota' => false,
                'useFor' => array(DeliveryServer::USE_FOR_GROUPS),
            );

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Running email cleanup for ".count($emails)." emails...\n";
            }

            if ($this->error_level>2)
            {
                Yii::log(Yii::t('groups',
                    'Group '.$batch->group_batch_id.' running clean up for '.count($emails).' emails'),
                    CLogger::LEVEL_INFO);
            }

            $beforeForeachTime = microtime(true);
            $sendingAloneTime = 0;

            // sort emails
            $emails = $this->sortEmails($emails);

            if (empty($emails))
            {
                $batch->saveStatus(Group::STATUS_SENT);

                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Group status has been set to SENT.\n";

                }
            }

            $index = 1;
            foreach ($emails AS $email)
            {
                if ($this->verbose)
                {
                    $timeStart = microtime(true);
                    echo "\n[".date("Y-m-d H:i:s")."] Current progress: ".($index)." out of ".count($emails);
                    echo "\n[".date("Y-m-d H:i:s")."] Checking if the delivery server is allowed to send to the subscriber email address domain...\n";
                }

                // if blacklisted, goodbye.
                if ($email->getIsBlacklisted())
                {
                    if ($this->verbose)
                    {
                        echo "\n[".date("Y-m-d H:i:s")."] The email address has been found in the blacklist, sending is denied!\n";
                    }

                    if ($this->error_level>0)
                    {
                        Yii::log(Yii::t('groups',
                            'Group '.$group->group_email_id.' email address found in blacklist '.$email),
                            CLogger::LEVEL_WARNING);
                    }
                    continue;
                }

                if ($this->verbose)
                {
                    echo "OK, took ".round(microtime(true)-$timeStart, 3)." seconds.\n";
                    echo "[".date("Y-m-d H:i:s")."] Checking server sending quota...";
                    $timeStart = microtime(true);
                }
                // in case the server is over quota
                if ($server->getIsOverQuota())
                {
                    if ($this->verbose)
                    {
                        echo "\n[".date("Y-m-d H:i:s")."] The delivery server is over quota, picking another one...\n";
                    }
                    $currentServerId = $server->server_id;
                    if (!($server = DeliveryServer::pickGroupServers($currentServerId, $group, $dsParams)))
                    {
                        throw new Exception(Yii::t('groups',
                            'Cannot find a valid server to send the campaign email, aborting until a delivery server is available!'),
                            99);
                    }
                }

                if ($this->verbose)
                {
                    echo "OK, took ".round(microtime(true)-$timeStart, 3)." seconds.\n";
                    echo "[".date("Y-m-d H:i:s")."] Preparing email...";
                    $timeStart = microtime(true);
                }

                $headerPrefix = 'X-Mw-';
                $emailParams = array(
                    'from' => array($email['from_email'] => $email['from_name']),
                    'fromName' => $email['from_name'],
                    'email_id' => $email['email_id'],
                    'from_email' => $email['from_email'],
                    'return_path' => 'bounces@marketherobounce1.com',
                    'Return_Path' => 'bounces@marketherobounce1.com',
                    'from_name' => $email['from_name'],
                    'to' => array($email['to_email'] => $email['to_name']),
                    'subject' => $email['subject'],
                    'replyTo' => $email['reply_to_email'],
                    'body' => $email['body'],
                    'plainText' => $email['plain_text'],
                );

                $emailParams['headers'] = array(
                    $headerPrefix.'Group-Uid' => $group->group_email_uid,
                    $headerPrefix.'Customer-Id' => $group->customer_id
                );

                $emailParams['mailerPlugins'] = $mailerPlugins;

                $email->status = GROUP::STATUS_SENT;
                $email->save();

                $criteria = new CDbCriteria();
                $criteria->select = '*';
                $criteria->condition
                    = 'group_batch_id = '.$batch->group_batch_id.' AND group_email_id = '.$batch->group_email_id.' AND log.email_id = '.$email['email_id'];
                $criteria->join = 'LEFT JOIN mw_group_email_log AS log ON log.email_id = t.email_id';
                $emailsToBeSent = GroupEmail::model()->findAll($criteria);

                if (count($emailsToBeSent)>0)
                {
                    continue;
                }

                $sent = $server->sendEmail($emailParams);

                if ($sent['email_id']==0)
                {
                    $email->status = GROUP::STATUS_UNSENT;
                    $email->save();
                }

                $this->logGroupEmailDelivery($sent, $server);

                if ($this->verbose)
                {
                    echo "\n[".date("Y-m-d H:i:s")."] Sending email id  ".$sent['email_id']."...\n";

                }

                if ($this->error_level>0)
                {
                    Yii::log(Yii::t('groups', 'Batch '.$batch->group_batch_id.' Sent email  '.$sent['email_id']),
                        CLogger::LEVEL_INFO);
                }
                $index++;

            }


            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'Batch '.$batch->group_batch_id.' status set to sent'),
                    CLogger::LEVEL_INFO);
            }
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Batch status has been set to SENT.\n";

            }


            if ($this->verbose)
            {
                $num = $index-1;
                echo "\n[".date("Y-m-d H:i:s")."] Exiting from the foreach loop, took ".round(microtime(true)-$beforeForeachTime,
                        3)." seconds to send for all ".$num." emails from which ".round($sendingAloneTime,
                        3)." seconds only to communicate with remote ends.\n";
            }

            $emailSent = $index+$batch->emails_sent;

            $total_in_group = Yii::app()->db->createCommand()
                ->select('count(*) as count')
                ->from('mw_group_email')
                ->where('group_email_id=:id', array(':id' => (int)$batch->group_batch_id))
                ->queryRow();

//            mail('smallfriinc@gmail.com', 'Counts '.$emailSent.'/'.$total_in_group['count'],
//                $emailSent.'/'.$total_in_group['count']);

            if ($emailSent>=$total_in_group['count'])
            {
                $batch->saveStatus(Group::STATUS_SENT);

                if ($this->error_level>0)
                {
                    Yii::log(Yii::t('groups', 'Batch '.$batch->group_batch_id.' status set to sent'),
                        CLogger::LEVEL_INFO);
                }
                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Batch status has been set to SENT.\n";

                }

            }

            $batch->saveNumberSent($group, $emailSent);

            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'Batch '.$batch->group_batch_id.' number of emails sent: '.$emailSent-1),
                    CLogger::LEVEL_INFO);
            }

        } catch (Exception $e)
        {

            // exception code to be returned later
            $code = (int)$e->getCode();

            // make sure sending is resumed next time.
            $email->status = GROUP::STATUS_PENDING_SENDING;
            $batch->saveStatus(Group::STATUS_PENDING_SENDING);
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Caught exception with message: ".$e->getMessage()."\n";
                echo "[".date("Y-m-d H:i:s")."] Email status has been changed to: ".strtoupper($email->status)."\n";
            }
            // return the exception code

            Yii::log(Yii::t('groups', 'Batch '.$batch->group_batch_id.' Error: '.$code), CLogger::LEVEL_ERROR);

            return $code;
        }


        return 0;
    }

    /**
     * Tries to:
     * 1. Group the subscribers by domain
     * 2. Sort them so that we don't send to same domain two times in a row.
     */
    protected function sortEmails($emails)
    {

        $emailsCount = count($emails);
        $_emails = array();
        foreach ($emails as $index => $email)
        {
            $emailParts = explode('@', $email->to_email);
            $domainName = $emailParts[1];
            if (!isset($_emails[$domainName]))
            {
                $_emails[$domainName] = array();
            }
            $_emails[$domainName][] = $email;
            unset($emails[$index]);
        }

        $emails = array();
        while ($emailsCount>0)
        {
            foreach ($_emails as $domainName => $subs)
            {
                foreach ($subs as $index => $sub)
                {
                    $emails[] = $sub;
                    unset($_emails[$domainName][$index]);
                    break;
                }
            }
            $emailsCount--;
        }

        return $emails;
    }

    public function logGroupEmailDelivery($sent, $server)
    {

        $log = new GroupEmailLog();
        $log->email_id = $sent['email_id'];
        $log->message = $server->getMailer()->getLog();
        $log->save(false);
    }

    public function setComplianceStatus($group, $in_review_count)
    {

        // Update emails to in-review status
        GroupEmail::model()
            ->updateAll(['status' => GROUP::STATUS_IN_REVIEW],
                'group_email_id= '.$group->group_email_id.' AND status = "'.GROUP::STATUS_PENDING_SENDING.'" ORDER BY email_id DESC LIMIT '.$in_review_count
            );

        //update status of the group so we don't send anymore emails
        $GroupEmailCompliance = GroupEmailCompliance::model()
            ->findByPk($group->group_email_id);
        $GroupEmailCompliance->compliance_status = GROUP::STATUS_IN_COMPLIANCE;
        $GroupEmailCompliance->update();
    }

    public function setGroupEmailStatusPendingSending($group)
    {

        // Update emails to pending-sending status if this Group is no longer under review
        GroupEmail::model()
            ->updateAll(['status' => GROUP::STATUS_PENDING_SENDING],
                'group_email_id= '.$group['group_email_id'].' AND status = "'.GROUP::STATUS_IN_REVIEW.'"'
            );
    }

    protected function countEmails($group_email_id)
    {

        $criteria = new CDbCriteria();
        $criteria->select = false;
        $criteria->condition = 'group_email_id=:id AND status = "pending-sending"';
        $criteria->params = array(':id' => $group_email_id);

        // and find them
        return GroupEmail::model()->count($criteria);
    }

    // find subscribers
    protected function findEmails($limit = 1, $group_email_id)
    {

        $criteria = new CDbCriteria();
        $criteria->with['logs'] = array(
            'select' => false,
            'limit' => $limit,
            'together' => true,
            'joinType' => 'LEFT OUTER JOIN',
            'on' => 'logs.email_id = t.email_id',
            'condition' => '`status` = "'.GROUP::STATUS_PENDING_SENDING.'" AND `send_at` < NOW() AND group_email_id = :id AND logs.email_id IS NULL',
            'params' => array(':id' => $group_email_id),
        );

        // and find them
        return GroupEmail::model()->findAll($criteria);
    }
}