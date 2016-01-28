<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * DswhController
 * 
 * Delivery Servers Web Hooks (DSWH) handler
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.4.8
 */
 
class DswhController extends Controller
{
    public function init()
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);
        sleep(rand(1, 5)); // this helps when slow database servers
        parent::init();
    }
    
    public function actionIndex($id)
    {
        $server = DeliveryServer::model()->findByPk((int)$id);
        if (empty($server)) {
            Yii::app()->end();
        }
        
        if ($server->type == 'mandrill-web-api') {
            $this->processMandrill();
        } elseif ($server->type == 'amazon-ses-web-api') {
            $this->processAmazonSes($server);
        } elseif ($server->type == 'mailgun-web-api') {
            $this->processMailgun();
        } elseif ($server->type == 'sendgrid-web-api') {
            $this->processSendgrid();
        } elseif ($server->type == 'leadersend-web-api') {
            $this->processLeadersend();
        } elseif ($server->type == 'elasticemail-web-api') {
            $this->processElasticemail();
        } elseif ($server->type == 'dyn-web-api') {
            $this->processDyn();
        } elseif ($server->type == 'sparkpost-web-api') {
            $this->processSparkpost();
        }
        
        Yii::app()->end();
    }
    
    protected function processMandrill()
    {
        if (!MW_COMPOSER_SUPPORT) {
            Yii::app()->end();
        }
        
        $request = Yii::app()->request;
        $mandrillEvents = $request->getPost('mandrill_events');
        
        if (empty($mandrillEvents)) {
            Yii::app()->end();
        }
        
        $mandrillEvents = CJSON::decode($mandrillEvents);
        if (empty($mandrillEvents) || !is_array($mandrillEvents)) {
            $mandrillEvents = array();
        }
        
        foreach ($mandrillEvents as $evt) {
            if (!empty($evt['type']) && $evt['type'] == 'blacklist' && !empty($evt['action']) && $evt['action'] == 'add') {
                if (!empty($evt['reject']['email'])) {
                    EmailBlacklist::addToBlacklist($evt['reject']['email'], (!empty($evt['reject']['detail']) ? $evt['reject']['detail'] : null));
                }
                continue;
            }
            
            if (empty($evt['msg']) || !is_array($evt['msg'])) {
                continue;
            }
            
            $msgData = $evt['msg'];
            $event   = !empty($evt['event']) ? $evt['event'] : null;
            
            $globalMetaData    = !empty($msgData['metadata']) && is_array($msgData['metadata']) ? $msgData['metadata'] : array();
            $recipientMetaData = !empty($msgData['recipient_metadata']) && is_array($msgData['recipient_metadata']) ? $msgData['recipient_metadata'] : array();
            $metaData          = array_merge($globalMetaData, $recipientMetaData);
            
            if (empty($metaData['campaign_uid']) || empty($metaData['subscriber_uid'])) {
                continue;
            }
            
            $campaignUid   = trim($metaData['campaign_uid']);
            $subscriberUid = trim($metaData['subscriber_uid']);
            
            $campaign = Campaign::model()->findByUid($campaignUid);
            if (empty($campaign)) {
                continue;
            }
            
            $subscriber = ListSubscriber::model()->findByAttributes(array(
                'list_id'           => $campaign->list_id,
                'subscriber_uid'    => $subscriberUid,
                'status'            => ListSubscriber::STATUS_CONFIRMED,
            ));
            
            if (empty($subscriber)) {
                continue;
            }
            
            $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));
            
            if (!empty($bounceLog)) {
                continue;
            }
            
            $returnReason = array();
            if (!empty($msgData['diag'])) {
                $returnReason[] = $msgData['diag'];
            }
            if (!empty($msgData['bounce_description'])) {
                $returnReason[] = $msgData['bounce_description'];
            }
            $returnReason = implode(" ", $returnReason);
            
            if (in_array($event, array('hard_bounce', 'soft_bounce'))) {
                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id     = $campaign->campaign_id;
                $bounceLog->subscriber_id   = $subscriber->subscriber_id;
                $bounceLog->message         = $returnReason;
                $bounceLog->bounce_type     = $event == 'soft_bounce' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;  
                $bounceLog->save();  
                
                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }
                
                continue;
            } 
            
            if (in_array($event, array('reject', 'blacklist'))) {
                $subscriber->addToBlacklist($returnReason);
                continue;
            }
            
            if(in_array($event, array('spam', 'unsub'))) {
                if ($event == 'spam' && Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                    $subscriber->delete();
                    continue;
                }
                
                $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
                $subscriber->save(false);
                
                $trackUnsubscribe = new CampaignTrackUnsubscribe();
                $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
                $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
                $trackUnsubscribe->note          = 'Unsubscribed via Web Hook!';
                $trackUnsubscribe->save(false);
                
                continue;
            }    
        }
        
        Yii::app()->end();
    }
    
    protected function processAmazonSes($server)
    {
        if (!MW_COMPOSER_SUPPORT || !version_compare(PHP_VERSION, '5.3.3', '>=')) {
            Yii::app()->end();
        }
        
        try {
            $message = \Aws\Sns\MessageValidator\Message::fromRawPostData();
            $validator = new \Aws\Sns\MessageValidator\MessageValidator();
            $validator->validate($message);
        } catch (Exception $e) {
            Yii::app()->end();
        }
        
        if ($message->get('Type') === 'SubscriptionConfirmation') {
            try {
                
                $types  = DeliveryServer::getTypesMapping();
                $type   = $types[$server->type];
                $server = DeliveryServer::model($type)->findByPk((int)$server->server_id);
                $result = $server->getSnsClient()->confirmSubscription(array(
                    'TopicArn'  => $message->get('TopicArn'),
                    'Token'     => $message->get('Token'),
                ));
                if (stripos($result->get('SubscriptionArn'), 'pending') === false) {
                    $server->subscription_arn = $result->get('SubscriptionArn');
                    $server->save(false);
                }
                Yii::app()->end();
            
            } catch (Exception $e) {}
            
            $client   = new \Guzzle\Http\Client();
            $client->get($message->get('SubscribeURL'))->send();
            Yii::app()->end();
        }
        
        if ($message->get('Type') !== 'Notification') {
            Yii::app()->end();
        }
        
        $data = new CMap((array)CJSON::decode($message->get('Message')));
        if (!$data->itemAt('notificationType') || $data->itemAt('notificationType') == 'AmazonSnsSubscriptionSucceeded' || !$data->itemAt('mail')) {
            Yii::app()->end();
        }
        
        $mailMessage = $data->itemAt('mail');
        if (empty($mailMessage['messageId'])) {
            Yii::app()->end();
        }
        
        $deliveryLog = CampaignDeliveryLog::model()->findByAttributes(array(
            'email_message_id' => $mailMessage['messageId'],
            'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
        ));
        
        if (empty($deliveryLog)) {
            $deliveryLog = CampaignDeliveryLogArchive::model()->findByAttributes(array(
                'email_message_id' => $mailMessage['messageId'],
                'status'           => CampaignDeliveryLogArchive::STATUS_SUCCESS,
            ));
        }
        
        if (empty($deliveryLog)) {
            Yii::app()->end();
        }
        
        $campaign = Campaign::model()->findByPk($deliveryLog->campaign_id);
        if (empty($campaign)) {
            Yii::app()->end();
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'list_id'          => $campaign->list_id,
            'subscriber_id'    => $deliveryLog->subscriber_id,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ));
        
        if (empty($subscriber)) {
            Yii::app()->end();
        }
        
        $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
            'campaign_id'   => $campaign->campaign_id,
            'subscriber_id' => $subscriber->subscriber_id,
        ));
        
        if (!empty($bounceLog)) {
            Yii::app()->end();
        }
        
        if ($data->itemAt('notificationType') == 'Bounce' && ($bounce = $data->itemAt('bounce'))) {
            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = $campaign->campaign_id;
            $bounceLog->subscriber_id   = $subscriber->subscriber_id;
            $bounceLog->message         = !empty($bounce['bouncedRecipients'][0]['diagnosticCode']) ? $bounce['bouncedRecipients'][0]['diagnosticCode'] : 'BOUNCED BACK';
            $bounceLog->bounce_type     = $bounce['bounceType'] !== 'Permanent' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;  
            $bounceLog->save();
            
            if ($bounce['bounceType'] === 'Permanent') {
                $subscriber->addToBlacklist($bounceLog->message);
            }
            Yii::app()->end();
        } 
        
        if ($data->itemAt('notificationType') == 'Complaint' && ($complaint = $data->itemAt('complaint'))) {
            if (Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                $subscriber->delete();
                Yii::app()->end();
            }
            
            $trackUnsubscribe = CampaignTrackUnsubscribe::model()->findByAttributes(array(
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));
            if (!empty($trackUnsubscribe)) {
                Yii::app()->end();
            }

            $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
            $subscriber->save(false);
            
            $trackUnsubscribe = new CampaignTrackUnsubscribe();
            $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
            $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
            $trackUnsubscribe->note          = 'Abuse complaint!';
            $trackUnsubscribe->save(false);
            
            Yii::app()->end();
        }

        Yii::app()->end();
    }
    
    protected function processMailgun()
    {
        if (!MW_COMPOSER_SUPPORT || !version_compare(PHP_VERSION, '5.3.2', '>=')) {
            Yii::app()->end();
        }
        
        $request  = Yii::app()->request;
        $event    = $request->getPost('event');
        $metaData = $request->getPost('metadata');
        
        if (empty($metaData) || empty($event)) {
            Yii::app()->end();
        }
        
        $metaData = CJSON::decode($metaData);
        if (empty($metaData['campaign_uid']) || empty($metaData['subscriber_uid'])) {
            Yii::app()->end();
        }
        
        $campaign = Campaign::model()->findByAttributes(array(
            'campaign_uid' => $metaData['campaign_uid']
        ));
        if (empty($campaign)) {
            Yii::app()->end();
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'list_id'          => $campaign->list_id,
            'subscriber_uid'   => $metaData['subscriber_uid'],
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ));
        
        if (empty($subscriber)) {
            Yii::app()->end();
        }
        
        $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
            'campaign_id'   => $campaign->campaign_id,
            'subscriber_id' => $subscriber->subscriber_id,
        ));
        
        if (!empty($bounceLog)) {
            Yii::app()->end();
        }
            
        if ($event == 'bounced') {
            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = $campaign->campaign_id;
            $bounceLog->subscriber_id = $subscriber->subscriber_id;
            $bounceLog->message       = $request->getPost('error') . ' ' . $request->getPost('reason');
            $bounceLog->bounce_type   = CampaignBounceLog::BOUNCE_HARD;  
            $bounceLog->save();
            
            $subscriber->addToBlacklist($bounceLog->message);
            
            Yii::app()->end();
        }
        
        if ($event == 'dropped') {
            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = $campaign->campaign_id;
            $bounceLog->subscriber_id = $subscriber->subscriber_id;
            $bounceLog->message       = $request->getPost('error') . ' ' . $request->getPost('reason');
            $bounceLog->bounce_type   = $request->getPost('reason') != 'hardfail' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;  
            $bounceLog->save();
            
            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }
            
            Yii::app()->end();
        }
        
        if ($event == 'complained') {
            if (Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                $subscriber->delete();
                Yii::app()->end();
            }
            
            $trackUnsubscribe = CampaignTrackUnsubscribe::model()->findByAttributes(array(
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));
            if (!empty($trackUnsubscribe)) {
                Yii::app()->end();
            }

            $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
            $subscriber->save(false);
            
            $trackUnsubscribe = new CampaignTrackUnsubscribe();
            $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
            $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
            $trackUnsubscribe->note          = 'Abuse complaint!';
            $trackUnsubscribe->save(false);
            
            Yii::app()->end();
        }
        
        Yii::app()->end();
    }
    
    protected function processSendgrid()
    {
        if (!MW_COMPOSER_SUPPORT || !version_compare(PHP_VERSION, '5.3.3', '>=')) {
            Yii::app()->end();
        }
        
        $events = file_get_contents("php://input");
        if (empty($events)) {
            Yii::app()->end();
        }

        $events = CJSON::decode($events);
        if (empty($events) || !is_array($events)) {
            $events = array();
        }
        $events = Yii::app()->ioFilter->xssClean($events);
        
        foreach ($events as $evt) {
            if (empty($evt['event']) || !in_array($evt['event'], array('dropped', 'deferred' , 'bounce', 'spamreport'))) {
                continue;
            }
            
            if (empty($evt['campaign_uid']) || empty($evt['subscriber_uid'])) {
                continue;
            }
            
            $campaignUid   = trim($evt['campaign_uid']);
            $subscriberUid = trim($evt['subscriber_uid']);
            
            $campaign = Campaign::model()->findByUid($campaignUid);
            if (empty($campaign)) {
                continue;
            }
            
            $subscriber = ListSubscriber::model()->findByAttributes(array(
                'list_id'           => $campaign->list_id,
                'subscriber_uid'    => $subscriberUid,
                'status'            => ListSubscriber::STATUS_CONFIRMED,
            ));
            
            if (empty($subscriber)) {
                continue;
            }
            
            $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));
            
            if (!empty($bounceLog)) {
                continue;
            }
            
            if (in_array($evt['event'], array('dropped', 'deferred' , 'bounce'))) {
                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id     = $campaign->campaign_id;
                $bounceLog->subscriber_id   = $subscriber->subscriber_id;
                $bounceLog->message         = isset($evt['reason']) ? $evt['reason'] : 'BOUNCED BACK';
                $bounceLog->bounce_type     = $evt['event'] == 'bounce' ? CampaignBounceLog::BOUNCE_HARD : CampaignBounceLog::BOUNCE_SOFT;  
                $bounceLog->save();  
                
                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }
                
                continue;
            } 

            if(in_array($evt['event'], array('spamreport'))) {
                if ($evt['event'] == 'spamreport' && Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                    $subscriber->delete();
                    continue;
                }
                
                $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
                $subscriber->save(false);
                
                $trackUnsubscribe = new CampaignTrackUnsubscribe();
                $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
                $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
                $trackUnsubscribe->note          = 'Unsubscribed via Web Hook!';
                $trackUnsubscribe->save(false);
                
                continue;
            }    
        }
        
        Yii::app()->end();
    }
    
    protected function processLeadersend()
    {
        $request = Yii::app()->request;
        $events  = $request->getPost('leadersend_events');
        
        if (empty($events)) {
            Yii::app()->end();
        }
        
        $events = CJSON::decode($events);
        if (empty($events) || !is_array($events)) {
            $events = array();
        }

        foreach ($events as $evt) {
            if (empty($evt['msg']) || empty($evt['msg']['id'])) {
                continue;
            }
            if (empty($evt['event']) || !in_array($evt['event'], array('spam', 'soft_bounce', 'hard_bounce', 'reject'))) {
                continue;
            }

            $deliveryLog = CampaignDeliveryLog::model()->findByAttributes(array(
                'email_message_id' => $evt['msg']['id'],
                'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
            ));
            
            if (empty($deliveryLog)) {
                $deliveryLog = CampaignDeliveryLogArchive::model()->findByAttributes(array(
                    'email_message_id' => $evt['msg']['id'],
                    'status'           => CampaignDeliveryLogArchive::STATUS_SUCCESS,
                ));
            }
            
            if (empty($deliveryLog)) {
                continue;
            }
            
            $campaign = Campaign::model()->findByPk($deliveryLog->campaign_id);
            if (empty($campaign)) {
                continue;
            }
            
            $subscriber = ListSubscriber::model()->findByAttributes(array(
                'list_id'          => $campaign->list_id,
                'subscriber_id'    => $deliveryLog->subscriber_id,
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ));
            
            if (empty($subscriber)) {
                continue;
            }
            
            $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));
            
            if (!empty($bounceLog)) {
                continue;
            }
            
            if (in_array($evt['event'], array('soft_bounce', 'hard_bounce'))) {
                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id     = $campaign->campaign_id;
                $bounceLog->subscriber_id   = $subscriber->subscriber_id;
                $bounceLog->message         = !empty($evt['msg']['delivery_report']) ? $evt['msg']['delivery_report'] : 'BOUNCED BACK';
                $bounceLog->bounce_type     = $evt['event'] == 'soft_bounce' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;  
                $bounceLog->save();  
                
                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }
                continue;
            }
            
            if ($evt['event'] == 'spam') {
                if (Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                    $subscriber->delete();
                    continue;
                }
                
                $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
                $subscriber->save(false);
                
                $trackUnsubscribe = new CampaignTrackUnsubscribe();
                $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
                $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
                $trackUnsubscribe->note          = 'Unsubscribed via Web Hook!';
                $trackUnsubscribe->save(false);
                
                continue;
            }
            
            if ($evt['event'] == 'reject') {
                $subscriber->addToBlacklist(!empty($evt['msg']['delivery_report']) ? $evt['msg']['delivery_report'] : 'BOUNCED BACK');
                continue;
            }
        }
        
        Yii::app()->end();
    }
    
    protected function processElasticemail()
    {
        $request     = Yii::app()->request;
        $category    = trim($request->getQuery('category'));
        $transaction = trim($request->getQuery('transaction'));
        $status      = trim($request->getQuery('status'));
        
        if (empty($transaction) || empty($category)) {
            Yii::app()->end();
        }
        
        $deliveryLog = CampaignDeliveryLog::model()->findByAttributes(array(
            'email_message_id' => $transaction,
            'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
        ));
        
        if (empty($deliveryLog)) {
            $deliveryLog = CampaignDeliveryLogArchive::model()->findByAttributes(array(
                'email_message_id' => $transaction,
                'status'           => CampaignDeliveryLogArchive::STATUS_SUCCESS,
            ));
        }
        
        if (empty($deliveryLog)) {
            Yii::app()->end();
        }
        
        $campaign = Campaign::model()->findByPk($deliveryLog->campaign_id);
        if (empty($campaign)) {
            Yii::app()->end();
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'list_id'          => $campaign->list_id,
            'subscriber_id'    => $deliveryLog->subscriber_id,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ));
        
        if (empty($subscriber)) {
            Yii::app()->end();
        }
        
        $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
            'campaign_id'   => $campaign->campaign_id,
            'subscriber_id' => $subscriber->subscriber_id,
        ));
        
        if (!empty($bounceLog)) {
            Yii::app()->end();
        }
        
        if (in_array($category, array('Ignore', 'DNSProblem', 'NotDelivered', 'NoMailbox', 'AccountProblem', 'ConnectionTerminated', 'ContentFilter'))) {
            $softBounce = in_array($category, array('AccountProblem', 'ConnectionTerminated', 'ContentFilter'));
            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = $campaign->campaign_id;
            $bounceLog->subscriber_id   = $subscriber->subscriber_id;
            $bounceLog->message         = 'BOUNCED BACK';
            $bounceLog->bounce_type     = $softBounce ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;  
            $bounceLog->save();  
            
            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }
            Yii::app()->end();
        }
        
        if ($category == 'Spam' || ($category == 'Unknown' && $status == 'AbuseReport')) {
            if (Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                $subscriber->delete();
                Yii::app()->end();
            }
            
            $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
            $subscriber->save(false);
            
            $trackUnsubscribe = new CampaignTrackUnsubscribe();
            $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
            $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
            $trackUnsubscribe->note          = 'Unsubscribed via Web Hook!';
            $trackUnsubscribe->save(false);
            
            Yii::app()->end();
        }
        
        Yii::app()->end();
    }
    
    protected function processDyn()
    {
        $request    = Yii::app()->request;
        $event      = $request->getQuery('event');
        $bounceRule = $request->getQuery('rule', $request->getQuery('bouncerule')); // bounce rule
        $bounceType = $request->getQuery('type', $request->getQuery('bouncetype')); // bounce type
        $campaign   = $request->getQuery('campaign'); // campaign uid
        $subscriber = $request->getQuery('subscriber'); // subscriber uid
        
        $allowedEvents = array('bounce', 'complaint', 'unsubscribe');
        if (!in_array($event, $allowedEvents)) {
            Yii::app()->end();
        }
        
        $campaign = Campaign::model()->findByUid($campaign);
        if (empty($campaign)) {
            Yii::app()->end();
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'list_id'          => $campaign->list_id,
            'subscriber_uid'   => $subscriber,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ));
        
        if (empty($subscriber)) {
            Yii::app()->end();
        }
        
        $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
            'campaign_id'   => $campaign->campaign_id,
            'subscriber_id' => $subscriber->subscriber_id,
        ));
        
        if (!empty($bounceLog)) {
            Yii::app()->end();
        }
        
        if ($event == 'bounce') {
            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = $campaign->campaign_id;
            $bounceLog->subscriber_id   = $subscriber->subscriber_id;
            $bounceLog->message         = $bounceRule;
            $bounceLog->bounce_type     = $bounceType == 'soft' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;  
            $bounceLog->save();  
            
            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }
            Yii::app()->end();
        }
        
        if (in_array($event, array('complaint', 'unsubscribe'))) {
            if (Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                $subscriber->delete();
                Yii::app()->end();
            }
            
            $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
            $subscriber->save(false);
            
            $trackUnsubscribe = new CampaignTrackUnsubscribe();
            $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
            $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
            $trackUnsubscribe->note          = 'Unsubscribed via Web Hook!';
            $trackUnsubscribe->save(false);
            
            Yii::app()->end();
        }
        
        Yii::app()->end();
    }
    
    protected function processSparkpost()
    {
        $events = file_get_contents("php://input");
        if (empty($events)) {
            Yii::app()->end();
        }
        $events = CJSON::decode($events);

        if (empty($events) || !is_array($events)) {
            $events = array();
        }
        $events = Yii::app()->ioFilter->xssClean($events);
        
        foreach ($events as $evt) {
            if (empty($evt['msys']['message_event'])) {
                continue;
            }
            $evt = $evt['msys']['message_event'];
            if (empty($evt['type']) || !in_array($evt['type'], array('bounce', 'spam_complaint', 'list_unsubscribe', 'link_unsubscribe'))) {
                continue;
            }
            
            if (empty($evt['rcpt_meta']) || empty($evt['rcpt_meta']['campaign_uid']) || empty($evt['rcpt_meta']['subscriber_uid'])) {
                continue;
            }
            
            $campaign = Campaign::model()->findByUid($evt['rcpt_meta']['campaign_uid']);
            if (empty($campaign)) {
                continue;
            }
            
            $subscriber = ListSubscriber::model()->findByAttributes(array(
                'list_id'          => $campaign->list_id,
                'subscriber_uid'   => $evt['rcpt_meta']['subscriber_uid'],
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ));
            
            if (empty($subscriber)) {
                continue;
            }
            
            $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));
            
            if (!empty($bounceLog)) {
                continue;
            }
        
            if (in_array($evt['type'], array('bounce'))) {
                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id     = $campaign->campaign_id;
                $bounceLog->subscriber_id   = $subscriber->subscriber_id;
                $bounceLog->message         = isset($evt['reason']) ? $evt['reason'] : 'BOUNCED BACK';
                // https://www.sparkpost.com/docs/bounce-classification-codes
                $bounceLog->bounce_type = in_array($evt['bounce_class'], array(1, 10, 30)) ? CampaignBounceLog::BOUNCE_SOFTBOUNCE_HARD : CampaignBounceLog::BOUNCE_SOFT; 
                $bounceLog->save();  
                
                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }
                
                continue;
            } 

            if (in_array($evt['type'], array('spam_complaint', 'list_unsubscribe', 'link_unsubscribe'))) {
                if ($evt['type'] == 'spam_complaint' && Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                    $subscriber->delete();
                    continue;
                }
                
                $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
                $subscriber->save(false);
                
                $trackUnsubscribe = new CampaignTrackUnsubscribe();
                $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
                $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
                $trackUnsubscribe->note          = 'Unsubscribed via Web Hook!';
                $trackUnsubscribe->save(false);
                
                continue;
            }    
        }
        
        Yii::app()->end();
    }
    
    public function actionDrh()
    {
        $request = Yii::app()->request;
        $event   = $request->getPost('event_type');
        $cs      = explode('|', $request->getPost('click_tracking_id'));
        
        if (empty($event) || empty($cs) || count($cs) != 2) {
            Yii::app()->end();
        }
        
        list($campaignUid, $subscriberUid) = $cs;
        
        $campaign = Campaign::model()->findByAttributes(array(
            'campaign_uid' => $campaignUid
        ));
        if (empty($campaign)) {
            $this->end();
        }
        
        $subscriber = ListSubscriber::model()->findByAttributes(array(
            'list_id'          => $campaign->list_id,
            'subscriber_uid'   => $subscriberUid,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ));
        
        if (empty($subscriber)) {
            $this->end();
        }
        
        $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
            'campaign_id'   => $campaign->campaign_id,
            'subscriber_id' => $subscriber->subscriber_id,
        ));
        
        if (!empty($bounceLog)) {
            $this->end();
        }
            
        if ($event == 'bounce_bad_address') {
            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = $campaign->campaign_id;
            $bounceLog->subscriber_id = $subscriber->subscriber_id;
            $bounceLog->message       = $request->getPost('bounce_text');
            $bounceLog->bounce_type   = $request->getPost('bounce_type') == 'h' ? CampaignBounceLog::BOUNCE_HARD : CampaignBounceLog::BOUNCE_SOFT;  
            $bounceLog->save();
            
            $subscriber->addToBlacklist($bounceLog->message);
            
            $this->end();
        }
        
        if ($event == 'scomp') {
            if (Yii::app()->options->get('system.cron.process_feedback_loop_servers.subscriber_action', 'unsubscribe') == 'delete') {
                $subscriber->delete();
                $this->end();
            }

            $trackUnsubscribe = CampaignTrackUnsubscribe::model()->findByAttributes(array(
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));
            if (!empty($trackUnsubscribe)) {
                $this->end();
            }
            
            $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
            $subscriber->save(false);
            
            $trackUnsubscribe = new CampaignTrackUnsubscribe();
            $trackUnsubscribe->campaign_id   = $campaign->campaign_id;
            $trackUnsubscribe->subscriber_id = $subscriber->subscriber_id;
            $trackUnsubscribe->note          = 'Abuse complaint!';
            $trackUnsubscribe->save(false);
            
            $this->end();
        }
        
        $this->end();
    }
    
    protected function end($message = "OK")
    {
        if ($message) {
            echo $message;
        }
        Yii::app()->end();
    }
}