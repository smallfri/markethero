<?php

/**
 * /**
 * groupsenderBehavior
 *
 * @author russell hudson <russell@smallfri.com>
 * @since 2.0
 */
class NewGroupEmailSenderBehavior extends CBehavior
{

    public $emails_at_once = 0;

    public $emails_per_minute = 0;

    // reference flag for campaigns limit
    public $groups_limit = 0;

    public $compliance_limit = 0;

    // reference flag for campaigns offset
    public $groups_offset = 0;

    public $verbose = 0;

    public $silent = 0;

    public $error_level = 0; //0=off

    public function sendGroups()
    {


        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Starting sendGroups command!\n";
        }
        $group = $this->getOwner();

        if (!$group->getIsActive())
        {
            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'This customer is inactive!'), CLogger::LEVEL_ERROR);
            }

            $group->saveStatus(Group::STATUS_PAUSED);

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] The above customer is not active, group has been paused!\n";
            }

            return 0;
        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Sending group id ".$group->group_email_id."!\n";
        }

        // Get count of emails for this group
        $count = Yii::app()->db->createCommand()
            ->select('count(*) as count')
            ->from('mw_group_email')
            ->where('group_email_id=:id AND status = "pending-sending"', array(':id' => (int)$group->group_email_id))
            ->queryRow();

        if ($count['count']<1)
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] This group has no emails to process...\n";
            }

            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' This group has no emails to process...'),
                    CLogger::LEVEL_INFO);
            }
            return 0;
        }

        // put proper status
        $group->saveStatus(Group::STATUS_PROCESSING);

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Found ".$count['count']." email(s)...\n";
        }

        if ($this->error_level>0)
        {
            Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Count '.$count['count']), CLogger::LEVEL_INFO);
        }

        $emailsToBeSent = $count['count'];


        // If the count is greater than the option emails at once, set emailsToBeSent to emails at once
        if ($count['count']>=$this->emails_at_once)
        {
            $emailsToBeSent = $this->emails_at_once;
        }

        if ($this->error_level>0)
        {
            Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Emails to be sent '.$this->emails_at_once),
                CLogger::LEVEL_INFO);
        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] There are ".$emailsToBeSent." emails to be sent...\n";
        }

        /*
         * This begins the compliance review logic
         *
         */
        $complianceReview = false;

        $complianceLevel = GroupComplianceLevels::model()->findByPk($group->compliance[0]->compliance_level_id);

        if ($group->compliance[0]->compliance_status==GROUP::STATUS_IN_REVIEW AND $count['count']>=$this->compliance_limit)
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] This Group is in Compliance Review...\n";
            }


            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' is in compliance review'),
                    CLogger::LEVEL_INFO);
            }

            $complianceReview = true;

            // Set emails to be sent = threshold X count
            $emailsToBeSent = round($count['count']*$complianceLevel->threshold);


            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] There are ".$emailsToBeSent." emails to be sent...\n";
            }


            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups',
                    'Group '.$group->group_email_id.' Compliance Emails to be sent '.$emailsToBeSent),
                    CLogger::LEVEL_INFO);
            }

            // Determine how many emails should be set to in-review status
            $in_review_count = $count['count']-$emailsToBeSent;

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Setting ".$in_review_count." emails to in-review...\n";
            }


            if ($this->error_level>2)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Emails set to in-review '.$in_review_count),
                    CLogger::LEVEL_INFO);
            }
            $this->setComplianceStatus($group, $in_review_count);

            if ($this->error_level>2)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' "Compliance" Status set to in-review'),
                    CLogger::LEVEL_INFO);
            }
            $group->saveStatus(Group::STATUS_IN_COMPLIANCE);

            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Compliance Status set to in-review'),
                    CLogger::LEVEL_INFO);
            }

        }
        elseif ($group->compliance->compliance_status==GROUP::STATUS_APPROVED)
        {
            if ($this->error_level>2)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Status approved'), CLogger::LEVEL_INFO);
            }

            $this->setGroupEmailStatusPendingSending($group);

            if ($this->error_level>2)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Status pending-sending'),
                    CLogger::LEVEL_INFO);
            }
            $group->saveStatus(Group::STATUS_PENDING_SENDING);

        }

        /*
         * Group compliance logic ends here.
         *
         */


        $criteria = new CDbCriteria();
        $criteria->select = '*';
        $criteria->condition
            = '`status` = "'.GROUP::STATUS_PENDING_SENDING.'" AND `send_at` < NOW() AND group_email_id = '.$group->group_email_id.' AND log.email_id IS NULL';
        $criteria->order = 'email_id ASC';
        $criteria->limit = $emailsToBeSent;
        $criteria->join = 'LEFT JOIN mw_group_email_log AS log ON log.email_id = t.email_id';
        $emails = GroupEmail::model()->findAll($criteria);

        if ($this->error_level>2)
        {
            Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' gross email count '.count($emails)),
                CLogger::LEVEL_INFO);
        }
        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Gross Emails Count ".count($emails)."...\n";

        }

        $dsParams = array('customerCheckQuota' => false, 'useFor' => array(DeliveryServer::USE_FOR_GROUPS));
        $server = DeliveryServer::pickGroupServers(0, $group, $dsParams);

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
                    'Group '.$group->group_email_id.' running clean up for '.count($emails).' emails'),
                    CLogger::LEVEL_INFO);
            }

            $beforeForeachTime = microtime(true);
            $sendingAloneTime = 0;

            // sort emails
            $emails = $this->sortEmails($emails);

            if ($complianceReview)
            {
                $group->saveStatus(Group::STATUS_IN_COMPLIANCE);

                if ($this->error_level>0)
                {
                    Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Status '.Group::STATUS_IN_COMPLIANCE),
                        CLogger::LEVEL_INFO);
                }

                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Group status has been changed to COMPLIANCE REVIEW.\n";

                }
            }

            if (empty($emails))
            {
                $group->saveStatus(Group::STATUS_SENT);

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
//                if ($email->getIsBlacklisted())
//                {
//                    if ($this->verbose)
//                    {
//                        echo "\n[".date("Y-m-d H:i:s")."] The email address has been found in the blacklist, sending is denied!\n";
//                    }
//
//                    if ($this->error_level>0)
//                    {
//                        Yii::log(Yii::t('groups',
//                            'Group '.$group->group_email_id.' email address found in blacklist '.$email),
//                            CLogger::LEVEL_WARNING);
//                    }
//                    continue;
//                }

                if ($this->verbose)
                {
                    echo "OK, took ".round(microtime(true)-$timeStart, 3)." seconds.\n";
                    echo "[".date("Y-m-d H:i:s")."] Checking server sending quota...";
                    $timeStart = microtime(true);
                }
                // in case the server is over quota
//                if ($server->getIsOverQuota())
//                {
//                    if ($this->verbose)
//                    {
//                        echo "\n[".date("Y-m-d H:i:s")."] The delivery server is over quota, picking another one...\n";
//                    }
//                    $currentServerId = $server->server_id;
//                    if (!($server = DeliveryServer::pickGroupServers($currentServerId, $group, $dsParams)))
//                    {
//                        throw new Exception(Yii::t('groups',
//                            'Cannot find a valid server to send the campaign email, aborting until a delivery server is available!'),
//                            99);
//                    }
//                }

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


                /*
                 *
                 * This might not be needed, double checking for dups
                 *
                 */
//                $criteria = new CDbCriteria();
//                $criteria->select = '*';
//                $criteria->condition
//                    = 'group_email_id = '.$group->group_email_id.' AND log.email_id = '.$email['email_id'];
//                $criteria->join = 'LEFT JOIN mw_group_email_log AS log ON log.email_id = t.email_id';
//                $emailsExist = GroupEmail::model()->findAll($criteria);
//
//                if (count($emailsExist)>0)
//                {
//                    continue;
//                }


                /*
                 * New for queueing
                 *
                 */
                if ($this->verbose)
                {
                    echo "done, took ".round(microtime(true)-$timeStart, 3)." seconds.\n";
                    echo "[".date("Y-m-d H:i:s")."] -> Sending the email for ".$email['to_email'];
                    if ($server->getUseQueue())
                    {
                        echo " by using the queue method";
                    }
                    else
                    {
                        echo " by using direct method";
                    }
                    echo "...";
                }


                if ($this->verbose)
                {
                    echo "done, took ".round(microtime(true)-$timeStart, 3)." seconds.\n";
                    echo "[".date("Y-m-d H:i:s")."] -> Sending the email for ".$email['to_email'];
                    if ($server->getUseQueue())
                    {
                        echo " by using the queue method";
                    }
                    else
                    {
                        echo " by using direct method";
                    }
                    echo "...";
                }

                // set delivery object
                $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_GROUP)->setDeliveryObject($group);

                // default status
                $status = CampaignDeliveryLog::STATUS_SUCCESS;

                // since 1.3.5 - try via queue
                $sent = null;
                if ($server->getUseQueue())
                {
                    $sent = array('message_id' => $server->server_id.StringHelper::random(40));
                    $response = 'OK';
                    $allParams = array_merge(array(
                        'server_id' => $server->server_id,
                        'server_type' => $server->type,
                        'group_id' => $group->group_email_id,
                        'params' => $emailParams
                    ), $sent);

                    if ($server->getCampaignQueueEmailsChunkSize()>1)
                    {
                        if (!$server->pushEmailInCampaignQueue($allParams))
                        {
                            $sent = $response = null;
                        }
                        else
                        {
                            $server->logUsage();
                        }
                    }
                    else
                    {
                        if (!Yii::app()->queue->enqueue($server->getQueueName(), 'SendEmailFromQueue', $allParams))
                        {
                            $sent = $response = null;
                        }
                        else
                        {
                            $server->logUsage();
                        }
                    }

                    unset($allParams);
                }

                // if not via queue or queue failed
                if (!$sent)
                {
                    $sent = $server->sendEmail($emailParams);
                    $response = $server->getMailer()->getLog();
                }


//                $sent = $server->sendEmail($emailParams);

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
                    Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Sent email  '.$sent['email_id']),
                        CLogger::LEVEL_INFO);
                }
                $index++;

            }


            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' status set to sent'),
                    CLogger::LEVEL_INFO);
            }
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Group status has been set to SENT.\n";

            }


            if ($this->verbose)
            {
                $num = $index-1;
                echo "\n[".date("Y-m-d H:i:s")."] Exiting from the foreach loop, took ".round(microtime(true)-$beforeForeachTime,
                        3)." seconds to send for all ".$num." emails from which ".round($sendingAloneTime,
                        3)." seconds only to communicate with remote ends.\n";
            }

            $emailSent = $index+$group->emails_sent;

            $total_in_group = Yii::app()->db->createCommand()
                ->select('count(*) as count')
                ->from('mw_group_email')
                ->where('group_email_id=:id', array(':id' => (int)$group->group_email_id))
                ->queryRow();

//            mail('smallfriinc@gmail.com', 'Counts '.$emailSent.'/'.$total_in_group['count'],
//                $emailSent.'/'.$total_in_group['count']);

            if ($emailSent>=$total_in_group['count'])
            {
                $group->saveStatus(Group::STATUS_SENT);

                if ($this->error_level>0)
                {
                    Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' status set to sent'),
                        CLogger::LEVEL_INFO);
                }
                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Group status has been set to SENT.\n";

                }

            }
            elseif (!$complianceReview)
            {
                $group->saveStatus(Group::STATUS_PENDING_SENDING);

            }

            $group->saveNumberSent($group, $emailSent);

            if ($this->error_level>0)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' number of emails sent: '.$emailSent-1),
                    CLogger::LEVEL_INFO);
            }

        } catch (Exception $e)
        {

            // exception code to be returned later
            $code = (int)$e->getCode();

            // make sure sending is resumed next time.
            $email->status = GROUP::STATUS_PENDING_SENDING;
            $group->saveStatus(Group::STATUS_PENDING_SENDING);
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Caught exception with message: ".$e->getMessage()."\n";
                echo "[".date("Y-m-d H:i:s")."] Email status has been changed to: ".strtoupper($email->status)."\n";
            }
            // return the exception code

            Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Error: '.$code), CLogger::LEVEL_ERROR);

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