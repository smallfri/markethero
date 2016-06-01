<?php use App\GroupEmailGroupsModel;

defined('MW_PATH')||exit('No direct script access allowed');

/**
 * groupsenderBehavior
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
class GroupEmailSenderBehavior extends CBehavior
{

    // reference flag for the type of groups we're sending
    public $groups_type;

    // reference flag for groups limit
    public $groups_limit = 0;

    public $groups_offset = 0;

    // whether this should be verbose and output to console
    public $verbose = 0;

    public $error_level = 0; // 0 = off

    public function sendGroups()
    {

        $group = $this->getOwner();

        if (!$group->getIsActive())
        {
            if($this->error_level > 0)
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

        $options = Yii::app()->db->createCommand()
            ->select('*')
            ->from('mw_group_email_options')
            ->where('id=:id', array(':id' => 1))
            ->queryRow();

        if($this->error_level > 2)
        {
            Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Options '.print_r($options, true)), CLogger::LEVEL_INFO);
        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Options ".print_r($options, true)."\n";
        }

        // Set Options

        $emailsAtOnce = $options['emails_at_once'];

        $complianceLimit = $options['compliance_limit'];


        // Get count of emails for this group
        $count = Yii::app()->db->createCommand()
            ->select('count(*) as count')
            ->from('mw_group_email')
            ->where('group_email_id=:id AND status = "pending-sending"', array(':id' => (int)$group->group_email_id))
            ->queryRow();

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Found ".$count['count']." email(s)...\n";
        }


        if($this->error_level > 0)
        {
            Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Count '.$count['count']), CLogger::LEVEL_INFO);
        }

        $emailsToBeSent = $count['count'];

        // If the count is greater than the option emails at once, set emailsToBeSent to emails at once
        if ($count['count']>=$emailsAtOnce)
        {
            $emailsToBeSent = $emailsAtOnce;
        }


        if($this->error_level > 0)
        {
            Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Emails to be sent '.$emailsAtOnce),
                CLogger::LEVEL_INFO);
        }
        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] There are ".$emailsToBeSent." emails to be sent...\n";
        }

        /*
         * Check whether or not this group is in compliance review
         *
         */
        $complianceReview = false;
        if ($group->compliance->compliance_status==GROUP::STATUS_IN_REVIEW AND $count['count']>=$complianceLimit)
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] This Group is in Compliance Review...\n";
            }


            if($this->error_level > 0)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' is in compliance review'),
                    CLogger::LEVEL_INFO);
            }

            $complianceReview = true;

            // Set emails to be sent = threshold X count
            $emailsToBeSent = round($count['count']*$group->compliance->compliance_levels->threshold);

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] There are ".$emailsToBeSent." emails to be sent...\n";
            }


            if($this->error_level > 0)
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


            if($this->error_level > 2)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Emails set to in-review '.$in_review_count),
                    CLogger::LEVEL_INFO);
            }
            $this->setComplianceStatus($group, $in_review_count);

            if($this->error_level > 2)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' "Compliance" Status set to in-review'),
                    CLogger::LEVEL_INFO);
            }
            $group->saveStatus(Group::STATUS_IN_COMPLIANCE);

            if($this->error_level > 0)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Compliance Status set to in-review'),
                    CLogger::LEVEL_INFO);
            }

        }
        elseif ($group->compliance->compliance_status== GROUP::STATUS_APPROVED)
        {
            if($this->error_level > 2)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Status approved'), CLogger::LEVEL_INFO);
            }

            $this->setGroupEmailStatusPendingSending($group);

            if($this->error_level > 2)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Status pending-sending'),
                    CLogger::LEVEL_INFO);
            }
            $group->saveStatus(Group::STATUS_PENDING_SENDING);

        }

        $emails = $this->findAllGroupEmail($group);

        if($this->error_level > 2)
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
            if($this->error_level > 2)
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

            $sendAtOnce = 100;
            if (!empty($sendAtOnce))
            {
                $mailerPlugins['antiFloodPlugin'] = array(
                    'sendAtOnce' => $sendAtOnce,
                    'pause' => 0,
                );
            }

            $perMinute = 100;
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
                'useFor' => array(DeliveryServer::USE_FOR_CAMPAIGNS),
            );

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Running email cleanup for ".count($emails)." emails...\n";
            }

            if($this->error_level > 2)
            {
                Yii::log(Yii::t('groups',
                    'Group '.$group->group_email_id.' running clean up for '.count($emails).' emails'),
                    CLogger::LEVEL_INFO);
            }

            $beforeForeachTime = microtime(true);
            $sendingAloneTime = 0;

            // sort emails
            $emails = $this->sortEmails($emails);

            // put proper status
            $group->saveStatus(Group::STATUS_PROCESSING);

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Group status has been set to PROCESSING.\n";

            }

            if($this->error_level > 0)
            {
                Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Status sent to '.Group::STATUS_PROCESSING),
                    CLogger::LEVEL_INFO);
            }

            if ($complianceReview)
            {
                $group->saveStatus(Group::STATUS_IN_COMPLIANCE);

                if($this->error_level > 0)
                {
                    Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Status '.Group::STATUS_IN_COMPLIANCE),
                        CLogger::LEVEL_INFO);
                }

                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Group status has been changed to COMPLIANCE REVIEW.\n";

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

                    if($this->error_level > 0)
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

//                $server = DeliveryServer::pickGroupServers($currentServerId, $group, $dsParams);
//
//                if ($changeServerAt > 0 && $processedCounter >= $changeServerAt && !$serverHasChanged) {
//                    $currentServerId = $server->server_id;;
//                    if ($newServer = DeliveryServer::pickServer($currentServerId, $group, $dsParams)) {
//                        $server = $newServer;
//                        print_r($server);
//
//                        unset($newServer);
//                    }
//
//                    $processedCounter = 0;
//                    $serverHasChanged = true;
//                }
//
//                $listUnsubscribeHeaderValue = $options->get('system.urls.frontend_absolute_url');
//                $listUnsubscribeHeaderValue .= 'lists/'.$list->list_uid.'/unsubscribe/'.$subscriber->subscriber_uid . '/' . $campaign->campaign_uid;
//                $listUnsubscribeHeaderValue = '<'.$listUnsubscribeHeaderValue.'>';
//
//                $reportAbuseUrl  = $options->get('system.urls.frontend_absolute_url');
//                $reportAbuseUrl .= 'groups/'. $campaign->campaign_uid . '/report-abuse/' . $list->list_uid . '/' . $subscriber->subscriber_uid;

                // since 1.3.4.9
//                if (!empty($campaign->reply_to)) {
//                    $_subject = 'Unsubscribe';
//                    $_body    = 'Please unsubscribe me from ' . $list->display_name . ' list.';
//                    $mailToUnsubscribeHeader    = sprintf(', <mailto:%s?subject=%s&body=%s>', '[LIST_UNSUBSCRIBE_EMAIL]', $_subject, $_body);
////                    $listUnsubscribeHeaderValue .= $mailToUnsubscribeHeader;
//                }


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
//                    $headerPrefix . 'Customer-Gid'     => (string)intval($customer->group_id), // because of sendgrid
//                    $headerPrefix . 'Delivery-Sid'     => (string)intval($server->server_id), // because of sendgrid
//                    $headerPrefix . 'Tracking-Did'     => (string)intval($server->tracking_domain_id), // because of sendgrid
//                    'List-Unsubscribe'      => $listUnsubscribeHeaderValue,
//                    'List-Id'               => $list->list_uid . ' <' . $list->display_name . '>',
//                    'X-Report-Abuse'        => 'Please report abuse for this campaign here: ' . $reportAbuseUrl,
                );
                // since 1.3.4.6
//                $headers = !empty($server->additional_headers) && is_array($server->additional_headers) ? $server->additional_headers : array();
//                $headers = (array)Yii::app()->hooks->applyFilters('console_command_send_groups_campaign_custom_headers', $headers, $campaign, $subscriber, $customer, $server, $emailParams);

//                if (!empty($headers)) {
//                    $headerSearchReplace = array(
//                        '[CAMPAIGN_UID]'    => $group->group_email_uid,
//                        '[SUBSCRIBER_EMAIL]'=> $subscriber->email,
//                    );
//                    foreach ($headers as $name => $value) {
//                        $headers[$name] = str_replace(array_keys($headerSearchReplace), array_values($headerSearchReplace), $value);
//                    }
//                    $emailParams['headers'] = array_merge($headers, $emailParams['headers']);
//                    unset($headers);
//                }
                $emailParams['mailerPlugins'] = $mailerPlugins;

//                $processedCounter++;
//                if ($processedCounter >= $changeServerAt) {
//                    $serverHasChanged = false;
//                }

                $sent = $server->sendEmail($emailParams);
                $this->setStatus($email);

                $this->logGroupEmailDelivery($sent, $server);

                if ($this->verbose)
                {
                    echo "\n[".date("Y-m-d H:i:s")."] Sending email id  ".$sent['email_id']."...\n";

                }

                if($this->error_level > 0)
                {
                    Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Sent email  '.$sent['email_id']),
                        CLogger::LEVEL_INFO);
                }
                $index++;
            }

            $emails = $this->findAllGroupEmail($group);

            if (empty($emails))
            {
                $group->saveStatus(Group::STATUS_SENT);

                if($this->error_level > 0)
                {
                    Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' status set to sent'),
                        CLogger::LEVEL_INFO);
                }
                if ($this->verbose)
                {
                    echo "[".date("Y-m-d H:i:s")."] Group status has been set to SENT.\n";

                }
            }

            if ($this->verbose)
            {
                echo "\n[".date("Y-m-d H:i:s")."] Exiting from the foreach loop, took ".round(microtime(true)-$beforeForeachTime,
                        3)." seconds to send for all ".$index." emails from which ".round($sendingAloneTime,
                        3)." seconds only to communicate with remote ends.\n";
            }

            $emailSent = $index + $group->emails_sent;
            $group->saveNumberSent($group, $emailSent);

            if($this->error_level > 0)
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

            Yii::log(Yii::t('groups', 'Group '.$group->group_email_id.' Error: '. $code), CLogger::LEVEL_ERROR);

            return $code;
        }


        return 0;
    }

    /**
     * @param $email
     */
    public function setStatus($email)
    {

        $email->status = GROUP::STATUS_SENT;
        $email->save();
    }

    /**
     * @param $sent
     * @param $server
     */
    public function logGroupEmailDelivery($sent, $server)
    {

        $log = new GroupEmailLog();
        $log->email_id = $sent['email_id'];
        $log->message = $server->getMailer()->getLog();
        $log->save(false);
    }

    /**
     * @param $group
     * @return array|mixed|null
     */
    public function findAllGroupEmail($group)
    {

        $emails = GroupEmail::model()->findAll(array(
            'condition' => '`status` = "'.GROUP::STATUS_PENDING_SENDING.'" AND `send_at` < NOW() AND `retries` < `max_retries` AND group_email_id = '.$group->group_email_id,
            'order' => 'email_id ASC',
            'limit' => 100
        ));
        return $emails;
    }

    /**
     * @param $group
     */
    public function setGroupEmailStatusPendingSending($group)
    {

        // Update emails to pending-sending status if this Group is no longer under review
        GroupEmail::model()
            ->updateAll(['status' => GROUP::STATUS_PENDING_SENDING],
                'group_email_id= '.$group['group_email_id'].' AND status = "'.GROUP::STATUS_IN_REVIEW.'"'
            );
    }

    /**
     * @param $group
     * @param $in_review_count
     */
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

    protected function logDelivery(ListSubscriber $subscriber, $message, $status, $messageId = null)
    {

        $campaign = $this->getOwner();

        $deliveryLog = CampaignDeliveryLog::model()->findByAttributes(array(
            'campaign_id' => (int)$campaign->campaign_id,
            'subscriber_id' => (int)$subscriber->subscriber_id,
        ));

        if (empty($deliveryLog))
        {
            $deliveryLog = new CampaignDeliveryLog();
            $deliveryLog->campaign_id = $campaign->campaign_id;
            $deliveryLog->subscriber_id = $subscriber->subscriber_id;
        }

        $deliveryLog->email_message_id = $messageId;
        $deliveryLog->message = str_replace("\n\n", "\n", $message);
        $deliveryLog->status = $status;

        return $deliveryLog->save();
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

    protected function prepareEmail($subscriber)
    {

        $group = $this->getOwner();

        print_r($group);

        // how come ?
        if (empty($group->template))
        {
            return false;
        }

//        $list           = $group->list;
        $customer = $group->customer_id;
        $emailContent = $group->template->content;
        $embedImages = array();
        $emailFooter = null;
        $onlyPlainText
            = !empty($group->template->only_plain_text)&&$group->template->only_plain_text===CampaignTemplate::TEXT_YES;
        $emailAddress = $subscriber->email;

        // Plain TEXT only supports basic tags transform, no xml/json feeds nor tracking.
        $emailPlainText = null;
        if (!empty($group->option)&&$group->option->plain_text_email==CampaignOption::TEXT_YES)
        {
            if ($group->template->auto_plain_text===CampaignTemplate::TEXT_YES /* && empty($group->template->plain_text)*/)
            {
                $emailPlainText = CampaignHelper::htmlToText($emailContent);
            }

            if (empty($emailPlainText)&&!empty($group->template->plain_text)&&!$onlyPlainText)
            {
                $_emailData = CampaignHelper::parseContent($group->template->plain_text, $group, $subscriber, false);
                list(, , $emailPlainText) = $_emailData;
            }
        }

        if ($onlyPlainText)
        {
            $_emailData = CampaignHelper::parseContent($group->template->plain_text, $group, $subscriber, false);
            list($toName, $emailSubject, $emailPlainText) = $_emailData;
            if (($emailFooter = $customer->getGroupOption('groups.email_footer'))&&strlen(trim($emailFooter))>5)
            {
                $emailPlainText .= "\n\n\n";
                $emailPlainText .= strip_tags($emailFooter);
            }
            $emailPlainText = preg_replace('%<br(\s{0,}?/?)?>%i', "\n", $emailPlainText);
        }

        // since 1.3.5.3
        if (!empty($group->option)&&$group->option->xml_feed==CampaignOption::TEXT_YES)
        {
            $emailSubject = CampaignXmlFeedParser::parseContent($emailSubject, $group, $subscriber, true,
                $group->subject);
        }

        if (!empty($group->option)&&$group->option->json_feed==CampaignOption::TEXT_YES)
        {
            $emailSubject = CampaignJsonFeedParser::parseContent($emailSubject, $group, $subscriber, true,
                $group->subject);
        }

        return array(
            'to' => array($emailAddress => $toName),
            'subject' => $emailSubject,
            'body' => $emailContent,
            'plainText' => $emailPlainText,
            'embedImages' => $embedImages,
            'onlyPlainText' => $onlyPlainText,
            // below disabled since 1.3.5.3
            //'trackingEnabled' => !empty($group->option) && $group->option->url_tracking == CampaignOption::TEXT_YES,
        );
    }

}