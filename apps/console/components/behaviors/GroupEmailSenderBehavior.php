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

    // reference flag for groups offset
    public $groups_offset = 0;

    // whether this should be verbose and output to console
    public $verbose = 0;

    public function sendGroups()
    {

        $group = $this->getOwner();

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Sending group id ".$group->group_email_id."!\n";
        }

        $options = Yii::app()->db->createCommand()
            ->select('*')
            ->from('mw_group_email_options')
            ->where('id=:id', array(':id' => 1))
            ->queryRow();


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


        $emailsToBeSent = $count['count'];

        // If the count is greater than the option emails at once, set emailsToBeSent to emails at once
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

        if ($group->compliance->compliance_status=='first-review' AND $count['count']>=$complianceLimit)
        {
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] This Group is in Compliance Review...\n";
            }

            // Set emails to be sent = threshold X count
            $emailsToBeSent = round($count['count']*$group->compliance->compliance_levels->threshold);

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
            GroupEmail::model()
                ->updateAll(['status' => 'in-review'],
                    'group_email_id= '.$group->group_email_id.' AND status = "pending-sending" ORDER BY email_id DESC LIMIT '.$in_review_count
                );

            //update status of the group so we don't send anymore emails
            $GroupEmailCompliance = GroupEmailCompliance::model()
                ->findByPk(5);
            $GroupEmailCompliance->compliance_status = 'compliance-review';
            $GroupEmailCompliance->update();

        }
        elseif ($group->compliance->compliance_status=='approved')
        {
            // Update emails to pending-sending status if this Group is no longer under review
            GroupEmail::model()
                ->updateAll(['status' => 'pending-sending'],
                    'group_email_id= '.$group['group_email_id'].' AND status = "in-review"'
                );
        }


        $emails = GroupEmail::model()->findAll(array(
            'condition' => '`status` = "pending-sending" AND `send_at` < NOW() AND `retries` < `max_retries` AND group_email_id = '.$group->group_email_id,
            'order' => 'email_id ASC',
            'limit' => 100
        ));

        $count = Yii::app()->db->createCommand()
            ->select('count(*) as count')
            ->from('mw_group_email')
            ->where('group_email_id=:id AND (status = "pending-sending" OR status ="in-review")',
                array(':id' => (int)$group->group_email_id))
            ->queryRow();

        if ($count['count']<1)
        {
            Group::model()
                ->updateAll(['status' => 'sent'],
                    'group_email_id= '.$group->group_email_id.' AND status = "pending-sending"'
                );

            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] No emails pending-sending or in-review, setting group to sent...\n";

            }

        }

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Gross Emails Count ".count($emails)."...\n";

        }

        $dsParams = array('customerCheckQuota' => false, 'useFor' => array(DeliveryServer::USE_FOR_GROUPS));
        $server = DeliveryServer::pickGroupServers(0, $group, $dsParams);

        if (empty($server))
        {
            Yii::log(Yii::t('groups',
                'Cannot find a valid server to send the Group email, aborting until a delivery server is available!'),
                CLogger::LEVEL_ERROR);

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

        // put proper status
//        $group->saveStatus(Group::STATUS_PROCESSING);

        if ($this->verbose)
        {
            $timeStart = microtime(true);
            echo "[".date("Y-m-d H:i:s")."] Campaign status has been set to PROCESSING.\n";
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

            $beforeForeachTime = microtime(true);
            $sendingAloneTime = 0;

            $index = 0;
            foreach ($emails AS $email)
            {
                if ($this->verbose)
                {
                    $timeStart = microtime(true);
                    echo "\n[".date("Y-m-d H:i:s")."] Current progress: ".($index)." out of ".count($emails);
                    echo "\n[".date("Y-m-d H:i:s")."] Checking if the delivery server is allowed to send to the subscriber email address domain...\n";
                }
                $index++;
//                // if this server is not allowed to send to this email domain, then just skip it.
//                if (!$server->canSendToDomainOf($subscriber->email)) {
//                    if ($this->verbose) {
//                        echo "\n[".date("Y-m-d H:i:s")."] Server is not allowed to send to the subscriber domain, skipping this subscriber!\n";
//                    }
//                    continue;
//                }
//
//                if ($this->verbose) {
//                    echo "OK, took " . round(microtime(true) - $timeStart, 3) . " seconds.\n";
//                    echo "[".date("Y-m-d H:i:s")."] Checking the subscriber email address into the blacklist...";
//                    $timeStart = microtime(true);
//                }
//
                // if blacklisted, goodbye.
                if ($email->getIsBlacklisted())
                {
                    if ($this->verbose)
                    {
                        echo "\n[".date("Y-m-d H:i:s")."] The email address has been found in the blacklist, sending is denied!\n";
                    }
                    continue;
                }
////
//                if ($this->verbose) {
//                    echo "OK, took " . round(microtime(true) - $timeStart, 3) . " seconds.\n";
//                    echo "[".date("Y-m-d H:i:s")."] Checking server sending quota...";
                $timeStart = microtime(true);
//                }
//
//                // in case the server is over quota
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
                $email->status = 'sent';
                $email->save();

                $log = new GroupEmailLog();
                $log->email_id = $sent['email_id'];
                $log->message = $server->getMailer()->getLog();
                $log->save(false);

                if ($this->verbose)
                {
                    echo "\n[".date("Y-m-d H:i:s")."] Sending email id  ".$sent['email_id']."...\n";

                }

            }
            if ($this->verbose)
            {
                echo "\n[".date("Y-m-d H:i:s")."] Exiting from the foreach loop, took ".round(microtime(true)-$beforeForeachTime,
                        3)." seconds to send for all ".count($emails)." emails from which ".round($sendingAloneTime,
                        3)." seconds only to communicate with remote ends.\n";
            }

        } catch (Exception $e)
        {

            // exception code to be returned later
            $code = (int)$e->getCode();

            // make sure sending is resumed next time.
            $email->status = 'pending-sending';
            if ($this->verbose)
            {
                echo "[".date("Y-m-d H:i:s")."] Caught exception with message: ".$e->getMessage()."\n";
                echo "[".date("Y-m-d H:i:s")."] Email status has been changed to: ".strtoupper($email->status)."\n";
            }
            // return the exception code
            return $code;
        }


        return 0;
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

    protected function countSubscribers()
    {

        $criteria = new CDbCriteria();
        $criteria->with['deliveryLogs'] = array(
            'select' => false,
            'together' => true,
            'joinType' => 'LEFT OUTER JOIN',
            'on' => 'deliveryLogs.campaign_id = :cid',
            'condition' => '(deliveryLogs.subscriber_id IS NULL OR deliveryLogs.`status` = :tstatus)',
            'params' => array(
                ':cid' => $this->getOwner()->campaign_id,
                ':tstatus' => CampaignDeliveryLog::STATUS_TEMPORARY_ERROR
            ),
        );

        return $this->getOwner()->countSubscribers($criteria);
    }

    // find subscribers
    protected function findSubscribers($limit = 300)
    {

        $criteria = new CDbCriteria();
        $criteria->with['deliveryLogs'] = array(
            'select' => false,
            'together' => true,
            'joinType' => 'LEFT OUTER JOIN',
            'on' => 'deliveryLogs.campaign_id = :cid',
            'condition' => '(deliveryLogs.subscriber_id IS NULL OR deliveryLogs.`status` = :tstatus)',
            'params' => array(
                ':cid' => $this->getOwner()->campaign_id,
                ':tstatus' => CampaignDeliveryLog::STATUS_TEMPORARY_ERROR
            ),
        );

        // and find them
        return $this->getOwner()->findSubscribers(0, $limit, $criteria);
    }

    /**
     * Tries to:
     * 1. Group the subscribers by domain
     * 2. Sort them so that we don't send to same domain two times in a row.
     */
    protected function sortSubscribers($subscribers)
    {

        $subscribersCount = count($subscribers);
        $_subscribers = array();
        foreach ($subscribers as $index => $subscriber)
        {
            $emailParts = explode('@', $subscriber->email);
            $domainName = $emailParts[1];
            if (!isset($_subscribers[$domainName]))
            {
                $_subscribers[$domainName] = array();
            }
            $_subscribers[$domainName][] = $subscriber;
            unset($subscribers[$index]);
        }

        $subscribers = array();
        while ($subscribersCount>0)
        {
            foreach ($_subscribers as $domainName => $subs)
            {
                foreach ($subs as $index => $sub)
                {
                    $subscribers[] = $sub;
                    unset($_subscribers[$domainName][$index]);
                    break;
                }
            }
            $subscribersCount--;
        }

        return $subscribers;
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

        if (!$onlyPlainText)
        {

//            if(!empty($group->option)&&$group->option->send_referral_url==1)
//            {
//                $emailContent = CampaignHelper::injectReferralLink($emailContent,$group->customer_id);
//            }

//            if(($emailFooter = $customer->getGroupOption('groups.email_footer'))&&strlen(trim($emailFooter))>5)
//            {
//                $emailContent = CampaignHelper::injectEmailFooter($emailContent,$emailFooter,$group);
//            }
//
//            if (!empty($group->option) && !empty($group->option->embed_images) && $group->option->embed_images == CampaignOption::TEXT_YES) {
//                list($emailContent, $embedImages) = CampaignHelper::embedContentImages($emailContent, $group);
//            }
//
//            if (!empty($group->option) && $group->option->xml_feed == CampaignOption::TEXT_YES) {
//                $emailContent = CampaignXmlFeedParser::parseContent($emailContent, $group, $subscriber, true);
//            }
//
//            if (!empty($group->option) && $group->option->json_feed == CampaignOption::TEXT_YES) {
//                $emailContent = CampaignJsonFeedParser::parseContent($emailContent, $group, $subscriber, true);
//            }
//
//            if (!empty($group->option) && $group->option->url_tracking == CampaignOption::TEXT_YES) {
//                $emailContent = CampaignHelper::transformLinksForTracking($emailContent, $group, $subscriber, true);
//            }
//
//            $emailData = CampaignHelper::parseContent($emailContent, $group, $subscriber, true);
//            list($toName, $emailSubject, $emailContent) = $emailData;
        }

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

    protected function markgroupsent()
    {

        $campaign = $this->getOwner();

        if ($campaign->isAutoresponder)
        {
            $campaign->saveStatus(Campaign::STATUS_SENDING);
            return;
        }

        $campaign->saveStatus(Campaign::STATUS_SENT);

        if (Yii::app()->options->get('system.customer.action_logging_enabled', true))
        {
            $list = $campaign->list;
            $customer = $list->customer;
            if (!($logAction = $customer->asa('logAction')))
            {
                $customer->attachBehavior('logAction', array(
                    'class' => 'customer.components.behaviors.CustomerActionLogBehavior',
                ));
                $logAction = $customer->asa('logAction');
            }
            $logAction->groupsent($campaign);
        }

        // since 1.3.4.6
        Yii::app()->hooks->doAction('console_command_send_groups_campaign_sent', $campaign);

        $this->sendgroupstats();

        // since 1.3.5.3
        $campaign->tryReschedule(true);
    }

    protected function sendgroupstats()
    {

        $campaign = $this->getOwner();
        if (empty($campaign->option->email_stats))
        {
            return $this;
        }

        if (!($server = DeliveryServer::pickServer(0, $campaign)))
        {
            return $this;
        }

        $campaign->attachBehavior('stats', array(
            'class' => 'customer.components.behaviors.groupstatsProcessorBehavior',
        ));
        $viewData = compact('campaign');

        // prepare and send the email.
        $emailTemplate = Yii::app()->options->get('system.email_templates.common');
        $emailBody = Yii::app()->command->renderFile(Yii::getPathOfAlias('console.views.campaign-stats').'.php',
            $viewData, true);
        $emailTemplate = str_replace('[CONTENT]', $emailBody, $emailTemplate);

        $recipients = explode(',', $campaign->option->email_stats);
        $recipients = array_map('trim', $recipients);

        $emailParams = array();
        $emailParams['fromName'] = $campaign->from_name;
        $emailParams['replyTo'] = array($campaign->reply_to => $campaign->from_name);
        $emailParams['subject'] = Yii::t('campaign_reports',
            'The campaign {name} has finished sending, here are the stats', array('{name}' => $campaign->name));
        $emailParams['body'] = $emailTemplate;

        foreach ($recipients as $recipient)
        {
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL))
            {
                continue;
            }
            $emailParams['to'] = array($recipient => $campaign->from_name);
            $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_CAMPAIGN)
                ->setDeliveryObject($campaign)
                ->sendEmail($emailParams);
        }

        return $this;
    }

    protected function getFailStatusFromResponse($response)
    {

        if (empty($response)||strlen($response)<5)
        {
            return CampaignDeliveryLog::STATUS_ERROR;
        }

        $status = CampaignDeliveryLog::STATUS_TEMPORARY_ERROR;

        if (preg_match('/code\s"(\d+)"/ix', $response, $matches))
        {
            $code = (int)$matches[1];
            if ($code>=450&&!in_array($code, array(503)))
            {
                $status = CampaignDeliveryLog::STATUS_FATAL_ERROR;
            }
        }

        $temporaryErrors = array(
            'graylist',
            'greylist',
            'nested mail command',
            'incorrect authentication',
            'failed',
            'timed out'
        );

        foreach ($temporaryErrors as $error)
        {
            if (stripos($response, $error)!==false)
            {
                $status = CampaignDeliveryLog::STATUS_TEMPORARY_ERROR;
                break;
            }
        }

        return $status;
    }
}