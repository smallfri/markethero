<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * DeliveryServerAmazonSesWebApi
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.4.8
 * 
 */
 
class DeliveryServerAmazonSesWebApi extends DeliveryServerSmtpAmazon
{
    protected $serverType = 'amazon-ses-web-api';
    
    protected $_initStatus;
    
    protected $_preCheckSesSnsError;
    
    protected $notificationTypes = array('Bounce', 'Complaint');
    
    public $topic_arn;
    
    public $subscription_arn;
    
    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServer the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    public function sendEmail(array $params = array())
    {
        $params = (array)Yii::app()->hooks->applyFilters('delivery_server_before_send_email', $this->getParamsArray($params), $this);

        if (!isset($params['from'], $params['to'], $params['subject'], $params['body'])) {
            return false;
        }
        
        list($fromEmail, $fromName) = $this->getMailer()->findEmailAndName($params['from']);
        list($toEmail, $toName)     = $this->getMailer()->findEmailAndName($params['to']);
        
        if (!empty($params['fromName'])) {
            $fromName = $params['fromName'];
        }
        
        $sent = false;
        try {
            if (!$this->preCheckSesSns()) {
                throw new Exception($this->_preCheckSesSnsError);
            }
            
            if (empty($params['attachments']) && empty($params['embedImages'])) {
                // if no attachments, bypass the mailer message generation and use the ses class directly since it is faster
                $message = array(
                    'Source'      => sprintf('=?%s?B?%s?= <%s>', strtolower(Yii::app()->charset), base64_encode($fromName), $this->from_email),
                    'Destination' => array(
                        'ToAddresses' => array(sprintf('=?%s?B?%s?= <%s>', strtolower(Yii::app()->charset), base64_encode($toName), $toEmail)),
                    ),
                    'Message' => array(
                        'Subject' => array(
                            'Data'    => $params['subject'],
                            'Charset' => Yii::app()->charset,
                        ),
                        'Body' => array(
                            'Text' => array(
                                'Data'    => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
                                'Charset' => Yii::app()->charset,
                            ),
                            'Html' => array(
                                'Data'    => $params['body'],
                                'Charset' => Yii::app()->charset,
                            ),
                        ),
                    ),
                );
                
                if (!empty($params['replyTo'])) {
                    list($replyToEmail) = $this->getMailer()->findEmailAndName($params['replyTo']);
                    $message['ReplyToAddresses'] = array($replyToEmail);
                }
                
                if (!empty($params['onlyPlainText']) && $params['onlyPlainText'] === true) {
                    unset($message['Message']['Body']['Html']);
                } 
                
                $response = $this->getSesClient()->sendEmail($message);
            
            } else {
                // use the mailer to create the message for us because it properly embeds the attachments
                $message = array(
                    'Source'      => sprintf('=?%s?B?%s?= <%s>', strtolower(Yii::app()->charset), base64_encode($fromName), $this->from_email),
                    'Destination' => array(
                        'ToAddresses' => array(sprintf('=?%s?B?%s?= <%s>', strtolower(Yii::app()->charset), base64_encode($toName), $toEmail)),
                    ),
                    'RawMessage' => array(
                        'Data' => base64_encode($this->getMailer()->getEmailMessage($params)),
                    ),
                );
                
                $response = $this->getSesClient()->sendRawEmail($message);
            }
            
            if ($response->get('MessageId')) {
                $sent = array('message_id' => $response->get('MessageId'));
                $this->getMailer()->addLog('OK');
            } else {
                throw new Exception(Yii::t('servers', 'Unable to make the delivery!'));
            }
        } catch (Exception $e) {
            $this->getMailer()->addLog($e->getMessage());
        }
        
        if ($sent) {
            $this->logUsage();
        }

        Yii::app()->hooks->doAction('delivery_server_after_send_email', $params, $this, $sent);
        
        return $sent;
    }

    public function getDefaultParamsArray()
    {
        $params = array(
            'transport' => self::TRANSPORT_AMAZON_SES_WEB_API,
        );
        
        return CMap::mergeArray(parent::getDefaultParamsArray(), $params);
    }
    
    public function attributeHelpTexts()
    {
        $texts = array(
            'username' => Yii::t('servers', 'Your Amazon SES SMTP username, something like: i.e: AKIAIYYYYYYYYYYUBBFQ. Please make sure this user has enough rights for SES but also for SNS'),
        );
        
        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }
    
    public function requirementsFailed()
    {
        if (!MW_COMPOSER_SUPPORT || !version_compare(PHP_VERSION, '5.3.3', '>=')) {
            return Yii::t('servers', 'The server type {type} requires your php version to be at least {version}!', array(
                '{type}'    => $this->serverType,
                '{version}' => '5.3.3',
            ));
        }
        return false;
    }
    
    public function getRegionFromHostname()
    {
        $parts = explode('.', str_replace('.amazonaws.com', '', $this->hostname));
        return array_pop($parts);
    }
    
    public function getSesClient()
    {
        static $clients = array();
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }
        return $clients[$id] = \Aws\Ses\SesClient::factory(array(
            'key'    => trim($this->username),
            'secret' => trim($this->password),
            'region' => $this->getRegionFromHostname(),
        ));
    }
    
    public function getSnsClient()
    {
        static $clients = array();
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }
        return $clients[$id] = \Aws\Sns\SnsClient::factory(array(
            'key'    => trim($this->username),
            'secret' => trim($this->password),
            'region' => $this->getRegionFromHostname(),
        ));
    }

    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initStatus      = $this->status;
        $this->topic_arn        = $this->getModelMetaData()->itemAt('topic_arn');
        $this->subscription_arn = $this->getModelMetaData()->itemAt('subscription_arn');
    }
    
    protected function afterFind()
    {
        $this->_initStatus      = $this->status;
        $this->topic_arn        = $this->getModelMetaData()->itemAt('topic_arn');
        $this->subscription_arn = $this->getModelMetaData()->itemAt('subscription_arn');
        parent::afterFind();
    }
    
    protected function beforeSave()
    {
        $this->getModelMetaData()->add('topic_arn', $this->topic_arn);
        $this->getModelMetaData()->add('subscription_arn', $this->subscription_arn);
        return parent::beforeSave();
    }
    
    protected function afterDelete()
    {
        try {
            $this->getSesClient()->setIdentityFeedbackForwardingEnabled(array(
                'Identity'          => $this->from_email,
                'ForwardingEnabled' => true,
            ));
            foreach($this->notificationTypes as $type) {
                $this->getSesClient()->setIdentityNotificationTopic(array(
                    'Identity'          => $this->from_email,
                    'NotificationType'  => $type,
                    'SnsTopic'          => null,
                ));
            }
            if (!empty($this->subscription_arn)) {
                $this->getSnsClient()->unsubscribe(array('SubscriptionArn' => $this->subscription_arn));
            }
        } catch (Exception $e) {
            
        }
        parent::afterDelete();
    }
    
    protected function preCheckSesSns()
    {
        if (MW_IS_CLI || $this->isNewRecord || $this->_initStatus !== self::STATUS_INACTIVE) {
            return true;
        }
        
        try {
            
            $this->getSesClient()->setIdentityFeedbackForwardingEnabled(array(
                'Identity'          => $this->from_email,
                'ForwardingEnabled' => true,
            ));
            foreach($this->notificationTypes as $type) {
                $this->getSesClient()->setIdentityNotificationTopic(array(
                    'Identity'          => $this->from_email,
                    'NotificationType'  => $type,
                    'SnsTopic'          => null,
                ));
            }
                
            if (!empty($this->subscription_arn)) {
                try {
                    $this->getSnsClient()->unsubscribe(array('SubscriptionArn' => $this->subscription_arn));
                } catch (Exception $e) {}
            }
            
            $result          = $this->getSnsClient()->createTopic(array('Name' => 'MWZSESHANDLER'));
            $this->topic_arn = $result->get('TopicArn');
            $subscribeUrl    = Yii::app()->options->get('system.urls.frontend_absolute_url') . sprintf('dswh/%d', $this->server_id);
            
            $result = $this->getSnsClient()->subscribe(array(
                'TopicArn' => $this->topic_arn,
                'Protocol' => stripos($subscribeUrl, 'https') === 0 ? 'https' : 'http',
                'Endpoint' => $subscribeUrl,
            ));
            if (stripos($result->get('SubscriptionArn'), 'pending') === false) {
                $this->subscription_arn = $result->get('SubscriptionArn');
            }
        
            foreach($this->notificationTypes as $type) {
                $this->getSesClient()->setIdentityNotificationTopic(array(
                    'Identity'          => $this->from_email,
                    'NotificationType'  => $type,
                    'SnsTopic'          => $this->topic_arn,
                ));
            }
            
            $this->getSesClient()->setIdentityFeedbackForwardingEnabled(array(
                'Identity'          => $this->from_email,
                'ForwardingEnabled' => false,
            ));

        } catch (Exception $e) {
            $this->_preCheckSesSnsError = $e->getMessage();
            return false;
        }
        
        return $this->save(false);
    }
}
