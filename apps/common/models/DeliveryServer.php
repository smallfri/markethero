<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * DeliveryServer
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
 
/**
 * This is the model class for table "delivery_server".
 *
 * The followings are the available columns in table 'delivery_server':
 * @property integer $server_id
 * @property integer $customer_id
 * @property integer $bounce_server_id
 * @property string $type
 * @property string $name
 * @property string $hostname
 * @property string $username
 * @property string $password
 * @property integer $port
 * @property string $protocol
 * @property integer $timeout
 * @property string $from_email
 * @property string $from_name
 * @property string $reply_to_email
 * @property integer $probability
 * @property integer $hourly_quota
 * @property string $meta_data
 * @property string $confirmation_key
 * @property string $locked
 * @property string $use_for
 * @property string $use_queue
 * @property string $signing_enabled
 * @property string $force_from
 * @property string $force_reply_to
 * @property string $status
 * @property string $date_added
 * @property string $last_updated
 * 
 * The followings are the available model relations:
 * @property Campaign[] $campaigns
 * @property BounceServer $bounceServer
 * @property TrackingDomain $trackingDomain
 * @property Customer $customer
 * @property DeliveryServerUsageLog[] $usageLogs
 * @property DeliveryServerDomainPolicy[] $domainPolicies
 * @property CustomerGroup[] $customerGroups
 */
class DeliveryServer extends ActiveRecord
{
    const TRANSPORT_SMTP = 'smtp';
    
    const TRANSPORT_SMTP_AMAZON = 'smtp-amazon';
    
    const TRANSPORT_SENDMAIL = 'sendmail';
    
    const TRANSPORT_PHP_MAIL = 'php-mail';
    
    const TRANSPORT_PICKUP_DIRECTORY = 'pickup-directory';
    
    const TRANSPORT_TCP_STREAM = 'tcp-stream';
    
    const TRANSPORT_MANDRILL_WEB_API = 'mandrill-web-api';
    
    const TRANSPORT_AMAZON_SES_WEB_API = 'amazon-ses-web-api';
    
    const TRANSPORT_MAILGUN_WEB_API = 'mailgun-web-api';
    
    const TRANSPORT_SENDGRID_WEB_API = 'sendgrid-web-api';
    
    const TRANSPORT_LEADERSEND_WEB_API = 'leadersend-web-api';
    
    const TRANSPORT_ELASTICEMAIL_WEB_API = 'elasticemail-web-api';
    
    const TRANSPORT_DYN_WEB_API = 'dyn-web-api';
    
    const TRANSPORT_SPARKPOST_WEB_API = 'sparkpost-web-api';

    const DELIVERY_FOR_SYSTEM = 'system';
    
    const DELIVERY_FOR_CAMPAIGN_TEST = 'campaign-test';
    
    const DELIVERY_FOR_TEMPLATE_TEST = 'template-test';
    
    const DELIVERY_FOR_CAMPAIGN = 'campaign';
    
    const DELIVERY_FOR_LIST = 'list';
    
    const DELIVERY_FOR_TRANSACTIONAL = 'transactional';
    
    const USE_FOR_ALL = 'all';
    
    const USE_FOR_TRANSACTIONAL = 'transactional';
    
    const USE_FOR_CAMPAIGNS = 'campaigns';
    
    const STATUS_IN_USE = 'in-use';
    
    const STATUS_HIDDEN = 'hidden';
    
    const STATUS_DISABLED = 'disabled';
    
    const TEXT_NO = 'no';
    
    const TEXT_YES = 'yes';
    
    const DEFAULT_QUEUE_NAME = 'emails-queue';
    
    const FORCE_FROM_WHEN_NO_SIGNING_DOMAIN = 'when no valid signing domain';
    
    const FORCE_FROM_ALWAYS = 'always';
    
    const FORCE_FROM_NEVER = 'never';
    
    const FORCE_REPLY_TO_ALWAYS = 'always';
    
    const FORCE_REPLY_TO_NEVER = 'never';
    
    protected $serverType = 'smtp';
    
    // flag to mark what kind of delivery we are making
    protected $_deliveryFor = 'system';
    
    // what do we deliver
    protected $_deliveryObject;
    
    // mailer object
    protected $_mailer;
    
    // list of additional headers to send for this server
    public $additional_headers = array();
    
    // since 1.3.4.9
    protected $_hourlySendingsLeft;
    
    // since 1.3.5 - flag to determine if logging usage
    protected $_logUsage = true;
    
    // since 1.3.5, store campaign emails in queue and flush at __destruct
    protected $_campaignQueueEmails = array();

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{delivery_server}}';
    }
    
    /**
     * @return array bevahiors attached to the model.
     */
    public function behaviors()
    {
        return CMap::mergeArray(array(
            'passwordHandler' => array(
                'class' => 'common.components.db.behaviors.DeliveryServerPaswordHandlerBehavior'
            ),
        ), parent::behaviors());
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        $rules = array(
            array('hostname, from_email', 'required'),
            
            array('name, hostname, username, password, from_email, from_name', 'length', 'min' => 3, 'max'=>150),
            array('port, probability, timeout', 'numerical', 'integerOnly'=>true),
            array('port', 'length', 'min'=> 2, 'max' => 5),
            array('probability', 'length', 'min'=> 1, 'max' => 3),
            array('probability', 'in', 'range' => array_keys($this->getProbabilityArray())),
            array('timeout', 'numerical', 'min' => 5, 'max' => 120),
            array('from_email, reply_to_email', 'email'),
            array('protocol', 'in', 'range' => array_keys($this->getProtocolsArray())),
            array('hourly_quota', 'numerical', 'integerOnly' => true, 'min' => 0),
            array('hourly_quota', 'length', 'max' => 11),
            array('bounce_server_id', 'exist', 'className' => 'BounceServer', 'attributeName' => 'server_id', 'allowEmpty' => true),
            array('tracking_domain_id', 'exist', 'className' => 'TrackingDomain', 'attributeName' => 'domain_id', 'allowEmpty' => true),
            array('hostname, username, from_email, type, status, customer_id', 'safe', 'on' => 'search'),
            array('additional_headers', '_validateAdditionalHeaders'),
            array('customer_id', 'exist', 'className' => 'Customer', 'attributeName' => 'customer_id', 'allowEmpty' => true),
            array('locked, use_queue, signing_enabled', 'in', 'range' => array_keys($this->getYesNoOptions())),
            array('use_for', 'in', 'range' => array_keys($this->getUseForOptions())),
            array('force_from', 'in', 'range' => array_keys($this->getForceFromOptions())),
            array('force_reply_to', 'in', 'range' => array_keys($this->getForceReplyToOptions())),
        );
        
        return CMap::mergeArray($rules, parent::rules());
    }
    
    /**
     * @return array relational rules.
     */
    public function relations()
    {
        $relations = array(
            'campaigns'         => array(self::MANY_MANY, 'Campaign', '{{campaign_to_delivery_server}}(server_id, campaign_id)'),
            'bounceServer'      => array(self::BELONGS_TO, 'BounceServer', 'bounce_server_id'),
            'trackingDomain'    => array(self::BELONGS_TO, 'TrackingDomain', 'tracking_domain_id'),
            'customer'          => array(self::BELONGS_TO, 'Customer', 'customer_id'),
            'usageLogs'         => array(self::HAS_MANY, 'DeliveryServerUsageLog', 'server_id'),
            'domainPolicies'    => array(self::HAS_MANY, 'DeliveryServerDomainPolicy', 'server_id'),
            'customerGroups'    => array(self::MANY_MANY, 'CustomerGroup', 'delivery_server_to_customer_group(server_id, group_id)'),
        );
        
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = array(
            'server_id'                     => Yii::t('servers', 'Server'),
            'customer_id'                   => Yii::t('servers', 'Customer'),
            'bounce_server_id'              => Yii::t('servers', 'Bounce server'),
            'tracking_domain_id'            => Yii::t('servers', 'Tracking domain'),
            'type'                          => Yii::t('servers', 'Type'),
            'name'                          => Yii::t('servers', 'Name'),
            'hostname'                      => Yii::t('servers', 'Hostname'),
            'username'                      => Yii::t('servers', 'Username'),
            'password'                      => Yii::t('servers', 'Password'),
            'port'                          => Yii::t('servers', 'Port'),
            'protocol'                      => Yii::t('servers', 'Protocol'),
            'timeout'                       => Yii::t('servers', 'Timeout'),
            'from_email'                    => Yii::t('servers', 'From email'),
            'from_name'                     => Yii::t('servers', 'From name'),
            'reply_to_email'                => Yii::t('servers', 'Reply-To email'),
            'probability'                   => Yii::t('servers', 'Probability'),
            'hourly_quota'                  => Yii::t('servers', 'Hourly quota'),
            'meta_data'                     => Yii::t('servers', 'Meta data'),
            'additional_headers'            => Yii::t('servers', 'Additional headers'),
            'locked'                        => Yii::t('servers', 'Locked'),
            'use_for'                       => Yii::t('servers', 'Use for'),
            'use_queue'                     => Yii::t('servers', 'Use queue'),
            'signing_enabled'               => Yii::t('servers', 'Signing enabled'),
            'force_from'                    => Yii::t('servers', 'Force FROM'),
            'force_reply_to'                => Yii::t('servers', 'Force Reply-To'),
        );
        
        return CMap::mergeArray($labels, parent::attributeLabels());
    }
    
    /**
    * Retrieves a list of models based on the current search/filter conditions.
    * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
    */
    public function search()
    {
        $criteria=new CDbCriteria;
        
        if (!empty($this->customer_id)) {
            if (is_numeric($this->customer_id)) {
                $criteria->compare('t.customer_id', $this->customer_id);
            } else {
                $criteria->with = array(
                    'customer' => array(
                        'joinType'  => 'INNER JOIN',
                        'condition' => 'CONCAT(customer.first_name, " ", customer.last_name) LIKE :name',
                        'params'    => array(
                            ':name'    => '%' . $this->customer_id . '%',
                        ),
                    )
                );
            }
        }
        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.hostname', $this->hostname, true);
        $criteria->compare('t.username', $this->username, true);
        $criteria->compare('t.from_email', $this->from_email, true);
        $criteria->compare('t.type', $this->type);
        $criteria->compare('t.status', $this->status);
        
        $criteria->addNotInCondition('t.status', array(self::STATUS_HIDDEN));
    
        return new CActiveDataProvider(get_class($this), array(
            'criteria'      => $criteria,
            'pagination'    => array(
                'pageSize'  => (int)$this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ),
            'sort'=>array(
                'defaultOrder'  => array(
                    'server_id' => CSort::SORT_DESC,
                ),
            ),
        ));
    }
    
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
    
    protected function afterConstruct()
    {
        $this->additional_headers = (array)$this->getModelMetaData()->itemAt('additional_headers');
        $this->_deliveryFor = self::DELIVERY_FOR_SYSTEM;
        parent::afterConstruct();
    }
    
    protected function afterFind()
    {
        $this->additional_headers = (array)$this->getModelMetaData()->itemAt('additional_headers');
        $this->_deliveryFor = self::DELIVERY_FOR_SYSTEM;
        parent::afterFind();
    }
    
    // send method
    public function sendEmail(array $params = array())
    {
        return false;
    }
    
    public function getMailer()
    {
        if ($this->_mailer === null) {
            $this->_mailer = clone Yii::app()->mailer;
        }
        return $this->_mailer;
    }
    
    protected function afterValidate()
    {
        if (!$this->isNewRecord && !MW_IS_CLI) {
            if (empty($this->customer_id)) {
                $this->locked = self::TEXT_NO;
            }
        
            $model = self::model()->findByPk((int)$this->server_id);
            $keys = array('hostname', 'username', 'password', 'port', 'protocol', 'from_email');
            if (!empty($this->bounce_server_id)) {
                array_push($keys, 'bounce_server_id');
            }
            foreach ($keys as $key) {
                if ($model->$key !== $this->$key) {
                    $this->status = self::STATUS_INACTIVE;
                    break;
                }
            }
        }        
        return parent::afterValidate();
    }
    
    protected function beforeSave()
    {
        $this->getModelMetaData()->add('additional_headers', (array)$this->additional_headers);
        if (empty($this->type)) {
            $this->type = $this->serverType;
        }
        if (empty($this->use_for) || !empty($this->customer_id)) {
            $this->use_for = self::USE_FOR_ALL;
        }
        return parent::beforeSave();
    }
    
    protected function beforeDelete()
    {
        if (!$this->getCanBeDeleted()) {
            return false;
        }
        
        return parent::beforeDelete();
    }
    
    public function attributeHelpTexts()
    {
        $texts = array(
            'bounce_server_id'  => Yii::t('servers', 'The server that will handle bounce emails for this SMTP server.'),
            'tracking_domain_id'=> Yii::t('servers', 'The domain that will be used for tracking purposes, must be a DNS CNAME of the master domain.'),
            'name'              => Yii::t('servers', 'The name of this server to make a distinction if having multiple servers with same hostname.'),
            'hostname'          => Yii::t('servers', 'The hostname of your SMTP server, usually something like smtp.domain.com.'),
            'username'          => Yii::t('servers', 'The username of your SMTP server, usually something like you@domain.com.'),
            'password'          => Yii::t('servers', 'The password of your SMTP server, used in combination with your username to authenticate your request.'),
            'port'              => Yii::t('servers', 'The port of your SMTP server, usually this is 25, but 465 and 587 are also valid choices for some of the servers depending on the security protocol they are using. If unsure leave it to 25.'),
            'protocol'          => Yii::t('servers', 'The security protocol used to access this server. If unsure, leave it blank or select TLS if blank does not work for you.'),
            'timeout'           => Yii::t('servers', 'The maximum number of seconds we should wait for the server to respond to our request. 30 seconds is a proper value.'),
            'from_email'        => Yii::t('servers', 'The default email address used in the FROM header when nothing is specified'),
            'from_name'         => Yii::t('servers', 'The default name used in the FROM header, together with the FROM email when nothing is specified'),
            'reply_to_email'    => Yii::t('servers', 'The default email address used in the Reply-To header when nothing is specified'),
            'probability'       => Yii::t('servers', 'When having multiple servers from where you send, the probability helps to choose one server more than another. This is useful if you are using servers with various quota limits. A lower probability means a lower sending rate using this server.'),
            'hourly_quota'      => Yii::t('servers', 'In case there are limits that apply for sending with this server, you can set a hourly quota for it and it will only send in one hour as many emails as you set here. Set it to 0 in order to not apply any hourly limit.'),
            'locked'            => Yii::t('servers', 'Whether this server is locked and assigned customer cannot change or delete it'),
            'use_for'           => Yii::t('servers', 'Whether this server can be used only for campaigns(and related sending), transactional emails, or all sending types'),
            'use_queue'         => Yii::t('servers', 'Whether the campaigns sent through this server should queue the emails instead of sending them directly'),
            'signing_enabled'   => Yii::t('servers', 'Whether signing is enabled when sending emails through this delivery server'),
            'force_from'        => Yii::t('servers', 'When to force the FROM email address'),
            'force_reply_to'    => Yii::t('servers', 'When to force the Reply-To email address'),
        );
        
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
    
    public function attributePlaceholders()
    {
        $placeholders = array(
            'hostname'          => Yii::t('servers', 'smtp.your-server.com'),
            'username'          => Yii::t('servers', 'you@domain.com'),
            'password'          => Yii::t('servers', 'your smtp account password'),
            'from_email'        => Yii::t('servers', 'you@domain.com'),
            'reply_to_email'    => Yii::t('servers', 'you@domain.com'),
        );
        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }
    
    public function getBounceServersArray()
    {
        static $_options = array();
        if (!empty($_options)) {
            return $_options;
        }
        
        $criteria = new CDbCriteria();
        $criteria->select = 'server_id, hostname, username, service';
        
        if ($this->customer_id) {
            $criteria->compare('customer_id', (int)$this->customer_id);
        }
        
        $criteria->addInCondition('status', array(BounceServer::STATUS_ACTIVE));
        $criteria->order = 'server_id DESC';
        $models = BounceServer::model()->findAll($criteria);
        
        $_options[''] = Yii::t('app', 'Choose');
        foreach ($models as $model) {
            $_options[$model->server_id] = sprintf('%s - %s(%s)', strtoupper($model->service), $model->hostname, $model->username);
        }
        
        return $_options;
    }
    
    public function getDisplayBounceServer()
    {
        if (empty($this->bounceServer)) {
            return;
        }
        
        $model = $this->bounceServer;
        
        return sprintf('%s - %s(%s)', strtoupper($model->service), $model->hostname, $model->username);
    }
    
    public function getBounceServerNotSupportedTypes()
    {
        $types = array(
            self::TRANSPORT_AMAZON_SES_WEB_API, 
            self::TRANSPORT_MANDRILL_WEB_API, 
            self::TRANSPORT_MAILGUN_WEB_API, 
            self::TRANSPORT_SENDGRID_WEB_API,
            self::TRANSPORT_LEADERSEND_WEB_API,
            self::TRANSPORT_ELASTICEMAIL_WEB_API,
            self::TRANSPORT_DYN_WEB_API,
            self::TRANSPORT_SPARKPOST_WEB_API,
        );   
        return (array)Yii::app()->hooks->applyFilters('delivery_servers_get_bounce_server_not_supported_types', $types); 
    }
    
    public function getBounceServerNotSupported()
    {
        return in_array($this->type, $this->getBounceServerNotSupportedTypes());
    }
    
    public function getSigningSupportedTypes()
    {
        $types = array(
            self::TRANSPORT_PHP_MAIL,
            self::TRANSPORT_PICKUP_DIRECTORY,
            self::TRANSPORT_SENDMAIL,
            self::TRANSPORT_SMTP,
            self::TRANSPORT_TCP_STREAM,
        );
        return (array)Yii::app()->hooks->applyFilters('delivery_servers_get_signing_supported_types', $types); 
    }
    
    public function getTrackingDomainsArray()
    {
        static $_options = array();
        if (!empty($_options)) {
            return $_options;
        }
        
        $criteria = new CDbCriteria();
        $criteria->select = 'domain_id, name';
        
        if ($this->customer_id) {
            $criteria->compare('customer_id', (int)$this->customer_id);
        }

        $criteria->order = 'domain_id DESC';
        $models = TrackingDomain::model()->findAll($criteria);
        
        $_options[''] = Yii::t('app', 'Choose');
        foreach ($models as $model) {
            $_options[$model->domain_id] = $model->name;
        }
        
        return $_options;
    }
    
    public function getProtocolsArray()
    {
        return array(
            ''          => Yii::t('app', 'Choose'),
            'tls'       => 'TLS',
            'ssl'       => 'SSL',
            'starttls'  => 'STARTTLS',
        );
    }
    
    public function getProtocolName()
    {
        $protocols = $this->getProtocolsArray();
        return !empty($this->protocol) && !empty($protocols[$this->protocol]) ? $protocols[$this->protocol] : Yii::t('app', 'Default');
    }
    
    public function getProbabilityArray()
    {
        $options = array('' => Yii::t('app', 'Choose'));
        for ($i = 5; $i <= 100; ++$i) {
            if ($i % 5 == 0) {
                $options[$i] = $i . ' %';
            }
        }
        return $options;
    }
    
    public function getDefaultParamsArray()
    {
        $params = array(
            'transport'       => self::TRANSPORT_SMTP,
            'hostname'        => null,
            'username'        => null,
            'password'        => null,
            'port'            => 25,
            'timeout'         => 30,
            'protocol'        => null,
            'probability'     => 100,
            'headers'         => (array)$this->additional_headers,
            'from'            => $this->from_email,
            'fromName'        => $this->from_name,
            'sender'          => $this->from_email,
            // 'returnPath'      => $this->username && filter_var($this->username, FILTER_VALIDATE_EMAIL) ? $this->username : $this->from_email,
            'returnPath'      => $this->from_email,            
            'replyTo'         => !empty($this->reply_to_email) ? $this->reply_to_email : $this->from_email,
            'to'              => null,
            'subject'         => null,
            'body'            => null,
            'plainText'       => null,
            'trackingEnabled' => $this->getTrackingEnabled(), // changed from 1.3.5.3
            'signingEnabled'  => $this->getSigningEnabled(),
            'forceFrom'       => $this->force_from,
            'forceReplyTo'    => $this->force_reply_to,
            'dkimPrivateKey'  => null,
            'dkimDomain'      => null,
            'dkimSelector'    => SendingDomain::DKIM_SELECTOR,
        );
        
        $customer_id = null;
        $fromEmail   = $this->from_email;
        
        if ($object = $this->getDeliveryObject()) {
            if (is_object($object) && $object instanceof Campaign) {
                $params['fromName'] = $object->from_name;
                $params['from']     = array($object->from_email => $object->from_name);
                $params['sender']   = array($object->from_email => $object->from_name);
                $params['replyTo']  = array($object->reply_to   => $object->from_name);
                
                $customer_id = $object->customer_id;
                $fromEmail   = $object->from_email;
            }
            if (is_object($object) && $object instanceof Lists && !empty($object->default)) {
                $params['fromName'] = $object->default->from_name;
                $params['from']     = array($object->default->from_email => $object->default->from_name);
                $params['sender']   = array($object->default->from_email => $object->default->from_name);
                $params['replyTo']  = array($object->default->from_email => $object->default->from_name);
                
                $customer_id = $object->customer_id;
                $fromEmail   = $object->default->from_email;
            }
        }
        
        if ($params['forceReplyTo'] == self::FORCE_REPLY_TO_ALWAYS) {
            $params['replyTo'] = !empty($this->reply_to_email) ? $this->reply_to_email : $this->from_email;
        }
        
        if ($params['forceFrom'] == self::FORCE_FROM_ALWAYS) {
            $fromEmail = $this->from_email;
        }
        
        if ($params['signingEnabled']) {
            $sendingDomain = null;
            if (!empty($this->bounce_server_id) && !empty($this->bounceServer)) {
                $returnPathEmail = !empty($this->bounceServer->email) ? $this->bounceServer->email : $this->bounceServer->username;
                $sendingDomain   = SendingDomain::model()->signingEnabled()->findVerifiedByEmail($returnPathEmail, $customer_id);
            }
            if (empty($sendingDomain)) {
                $sendingDomain = SendingDomain::model()->signingEnabled()->findVerifiedByEmail($fromEmail, $customer_id);
            }
            if (!empty($sendingDomain)) {
                $params['dkimPrivateKey'] = $sendingDomain->dkim_private_key;
                $params['dkimDomain']     = $sendingDomain->name;
            }    
        }
        
        if ($params['forceFrom'] == self::FORCE_FROM_ALWAYS || ($params['forceFrom'] == self::FORCE_FROM_WHEN_NO_SIGNING_DOMAIN && empty($params['dkimDomain']))) {
            $fromEmail = $this->from_email;
            if (!empty($params['from'])) {
                if (is_array($params['from'])) {
                    foreach ($params['from'] as $key => $value) {
                        break;
                    }
                    $params['from'] = array($fromEmail => $value);
                } else {
                    $params['from'] = $fromEmail;
                }
            }
        }

        if (!empty($this->bounce_server_id) && !empty($this->bounceServer)) {
            $params['returnPath'] = !empty($this->bounceServer->email) ? $this->bounceServer->email : $this->bounceServer->username;
        }
        
        return $params;
    }
    
    public function getParamsArray(array $params = array())
    {
        // avoid merging arrays recursive ending up with multiple arrays when we expect only one.
        $_params    = CMap::mergeArray($this->getDefaultParamsArray(), $this->attributes);
        $uniqueKeys = array('from', 'sender', 'returnPath', 'replyTo', 'to');
        foreach ($uniqueKeys as $key) {
            if (array_key_exists($key, $params) && array_key_exists($key, $_params)) {
                unset($_params[$key]);
            }
        }
        $params = CMap::mergeArray($_params, $params);
        
        // changed since 1.3.5.3
        if ($params['trackingEnabled']) {
            // since 1.3.5.4 - we disabled the action hook in the favor of the direct method.
            $params = $this->_handleTrackingDomain($params);
        }
        
        // and trigger the attached filters
        return (array)Yii::app()->hooks->applyFilters('delivery_server_get_params_array', $params);
    }
    
    public function getFromEmail()
    {
        return $this->from_email;
    }
    
    public function getFromName()
    {
        return $this->from_name;
    }
    
    public function getSenderEmail()
    {
        return $this->from_email;
    }
    
    /**
     * Can be used in order to do checks against missing requirements!
     * If must return false if all requirements are fine, otherwise a message about missing requirements!
     */
    public function requirementsFailed()
    {
        return false;
    }
    
    public static function getNameByType($type)
    {
        $mapping = self::getTypesMapping();
        if (!isset($mapping[$type])) {
            return null;
        }
        return ucwords(str_replace(array('-'), ' ', Yii::t('servers', $type)));
    }
    
    public static function getTypesMapping()
    {
        static $mapping;
        if ($mapping !== null) {
            return (array)$mapping;
        }
        
        $mapping = array(
            self::TRANSPORT_SMTP               => 'DeliveryServerSmtp',
            self::TRANSPORT_SMTP_AMAZON        => 'DeliveryServerSmtpAmazon',
            self::TRANSPORT_SENDMAIL           => 'DeliveryServerSendmail',
            self::TRANSPORT_PHP_MAIL           => 'DeliveryServerPhpMail',
            self::TRANSPORT_PICKUP_DIRECTORY   => 'DeliveryServerPickupDirectory',
            self::TRANSPORT_TCP_STREAM         => 'DeliveryServerTcpStream',
        );
        
        if (MW_COMPOSER_SUPPORT) {
            $mapping = array_merge($mapping, array(
                self::TRANSPORT_MANDRILL_WEB_API   => 'DeliveryServerMandrillWebApi',
                self::TRANSPORT_AMAZON_SES_WEB_API => 'DeliveryServerAmazonSesWebApi',
                self::TRANSPORT_MAILGUN_WEB_API    => 'DeliveryServerMailgunWebApi',
                self::TRANSPORT_SENDGRID_WEB_API   => 'DeliveryServerSendgridWebApi',
                self::TRANSPORT_DYN_WEB_API        => 'DeliveryServerDynWebApi',
            ));
        }
        
        $mapping = array_merge($mapping, array(
            self::TRANSPORT_LEADERSEND_WEB_API   => 'DeliveryServerLeadersendWebApi',
            self::TRANSPORT_ELASTICEMAIL_WEB_API => 'DeliveryServerElasticemailWebApi',
            self::TRANSPORT_SPARKPOST_WEB_API    => 'DeliveryServerSparkpostWebApi',
        ));
        
        return (array)Yii::app()->hooks->applyFilters('delivery_servers_get_types_mapping', $mapping);
    }
    
    public static function getCustomerTypesMapping(Customer $customer = null)
    {
        static $mapping;
        if ($mapping !== null) {
            return (array)$mapping;
        }
        
        $mapping = self::getTypesMapping();
        if (!$customer) {
            $allowed = (array)Yii::app()->options->get('system.customer_servers.allowed_server_types', array());
        } else {
            $allowed = (array)$customer->getGroupOption('servers.allowed_server_types', array());
        }
        
        foreach ($mapping as $type => $name) {
            if (!in_array($type, $allowed)) {
                unset($mapping[$type]);
                continue;
            }
            if (self::model($name)->requirementsFailed()) {
                unset($mapping[$type]);
            }
        }
        
        return (array)Yii::app()->hooks->applyFilters('delivery_servers_get_customer_types_mapping', $mapping);
    }
    
    public function getStatusesList()
    {
        return array(
            self::STATUS_ACTIVE     => ucfirst(Yii::t('app', self::STATUS_ACTIVE)),
            self::STATUS_IN_USE     => ucfirst(Yii::t('app', self::STATUS_IN_USE)),
            self::STATUS_INACTIVE   => ucfirst(Yii::t('app', self::STATUS_INACTIVE)),
            self::STATUS_DISABLED   => ucfirst(Yii::t('app', self::STATUS_DISABLED)),
        );
    }
    
    public static function getTypesList()
    {
        $list = array();
        foreach (self::getTypesMapping() as $key => $value) {
            $list[$key] = self::getNameByType($key);
        }
        return $list;
    }
    
    public static function getCustomerTypesList()
    {
        $list = array();
        foreach (self::getCustomerTypesMapping() as $key => $value) {
            $list[$key] = self::getNameByType($key);
        }
        return $list;
    }
    
    public function setDeliveryObject($object)
    {
        $this->_deliveryObject = $object;
        return $this;   
    }
    
    public function getDeliveryObject()
    {
        return $this->_deliveryObject;
    }
    
    public function setDeliveryFor($deliveryFor)
    {
        $this->_deliveryFor = $deliveryFor;
        return $this;
    }
    
    public function getDeliveryFor()
    {
        return $this->_deliveryFor;
    }
    
    public function isDeliveryFor($for)
    {
        return $this->_deliveryFor == $for;
    }
    
    /**
     * This is deprecated and must be removed in future
     */
    public function markHourlyUsage($refresh = true)
    {
        return $this;
    }
    
    public function logUsage()
    {
        // since 1.3.5.5
        if (MW_PERF_LVL && MW_PERF_LVL & MW_PERF_LVL_DISABLE_DS_LOG_USAGE) {
            return $this;
        }
        
        // since 1.3.5
        if (!$this->_logUsage) {
            return $this;
        }
        
        $log = new DeliveryServerUsageLog();
        $log->server_id = (int)$this->server_id;
        
        if ($customer = $this->getCustomerByDeliveryObject()) {
            $log->customer_id = (int)$customer->customer_id;
            if (!$this->getDeliveryIsCountableForCustomer()) {
                $log->customer_countable = DeliveryServerUsageLog::TEXT_NO;
            }
        }
        
        $log->delivery_for = $this->getDeliveryFor();
        
        if ($this->getCanHaveHourlyQuota() || (!empty($log->customer_id) && $log->customer_countable == DeliveryServerUsageLog::TEXT_YES)) {
            $log->save(false);
            if ($this->_hourlySendingsLeft !== null) {
                $this->_hourlySendingsLeft--;
            }
        }
        
        return $this;
    }
    
    public function getDeliveryIsCountableForCustomer()
    {
        if (!($deliveryObject = $this->getDeliveryObject())) {
            return false;
        }
        
        if (!($customer = $this->getCustomerByDeliveryObject())) {
            return false;
        }
        
        $trackableDeliveryFor = array(self::DELIVERY_FOR_CAMPAIGN, self::DELIVERY_FOR_CAMPAIGN_TEST, self::DELIVERY_FOR_TEMPLATE_TEST, self::DELIVERY_FOR_LIST);
        if (!in_array($this->getDeliveryFor(), $trackableDeliveryFor)) {
            return false;
        }

        if($deliveryObject instanceof Campaign) {
            if ($this->isDeliveryFor(self::DELIVERY_FOR_CAMPAIGN) && $customer->getGroupOption('quota_counters.campaign_emails', self::TEXT_YES) == self::TEXT_YES) {
                return true;
            }
            if ($this->isDeliveryFor(self::DELIVERY_FOR_CAMPAIGN_TEST) && $customer->getGroupOption('quota_counters.campaign_test_emails', self::TEXT_YES) == self::TEXT_YES) {
                return true;
            }
            return false;
        }
        
        if($deliveryObject instanceof CustomerEmailTemplate) {
            if ($this->isDeliveryFor(self::DELIVERY_FOR_TEMPLATE_TEST) && $customer->getGroupOption('quota_counters.template_test_emails', self::TEXT_YES) == self::TEXT_YES) {
                return true;
            }
            return false;
        }
        
        if($deliveryObject instanceof Lists) {
            if ($this->isDeliveryFor(self::DELIVERY_FOR_LIST) && $customer->getGroupOption('quota_counters.list_emails', self::TEXT_YES) == self::TEXT_YES) {
                return true;
            }
            return false;
        }
        
        if($deliveryObject instanceof TransactionalEmail) {
            if ($this->isDeliveryFor(self::DELIVERY_FOR_TRANSACTIONAL) && $customer->getGroupOption('quota_counters.transactional_emails', self::TEXT_YES) == self::TEXT_YES) {
                return true;
            }
            return false;
        }
        
        return false;
    }
    
    public function countHourlyUsage()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('server_id', (int)$this->server_id);
        $criteria->addCondition('`date_added` BETWEEN DATE_FORMAT(NOW(), "%Y-%m-%d %H:00:00") AND DATE_FORMAT(NOW() + INTERVAL 1 HOUR, "%Y-%m-%d %H:00:00")');
        return DeliveryServerUsageLog::model()->count($criteria);
    }
    
    public function getCanHaveHourlyQuota()
    {
        return !$this->isNewRecord && $this->hourly_quota > 0;
    }
    
    public function getIsOverQuota()
    {
        // since 1.3.5.5
        if (MW_PERF_LVL && MW_PERF_LVL & MW_PERF_LVL_DISABLE_DS_QUOTA_CHECK) {
            return false;
        }
        if ($this->isNewRecord || !$this->getCanHaveHourlyQuota()) {
            return false;
        } 
        if ($this->_hourlySendingsLeft === null) {
            $this->_hourlySendingsLeft = $this->hourly_quota - $this->countHourlyUsage();
        }
        return (int)$this->_hourlySendingsLeft <= 0;
    }
    
    public function getCanBeDeleted()
    {
        return !in_array($this->status, array(self::STATUS_IN_USE));
    }
    
    public function getCanBeUpdated()
    {
        return !in_array($this->status, array(self::STATUS_IN_USE, self::STATUS_HIDDEN));
    }
    
    public function setIsInUse($refresh = true)
    {
        if ($this->getIsInUse()) {
            return $this;
        }
        
        $this->status = self::STATUS_IN_USE;
        $this->save(false);
        
        if ($refresh) {
            $this->refresh();
        }
        
        return $this;
    }
    
    public function setIsNotInUse($refresh = true)
    {
        if (!$this->getIsInUse()) {
            return $this;
        }
        
        $this->status = self::STATUS_ACTIVE;
        $this->save(false);
        
        if ($refresh) {
            $this->refresh();
        }
        
        return $this;
    }
    
    public function getIsInUse()
    {
        return $this->status === self::STATUS_IN_USE;
    }
    
    public function getIsLocked()
    {
        return $this->locked === self::TEXT_YES;
    }
    
    public function getDisplayName()
    {
        return empty($this->name) ? $this->hostname : $this->name;
    }
    
    public function canSendToDomainOf($emailAddress)
    {
        // since 1.3.5.5
        if (MW_PERF_LVL && MW_PERF_LVL & MW_PERF_LVL_DISABLE_DS_CAN_SEND_TO_DOMAIN_OF_CHECK) {
            return true;
        }
        return DeliveryServerDomainPolicy::canSendToDomainOf($this->server_id, $emailAddress);
    }
    
    public function getNeverAllowedHeaders()
    {
        $neverAllowed = array(
            'From', 'To', 'Subject', 'Date', 'Return-Path', 'Sender', 
            'Reply-To', 'Message-Id', 'List-Unsubscribe', 
            'Content-Type', 'Content-Transfer-Encoding', 'Content-Length', 'MIME-Version',
            'X-Sender', 'X-Receiver', 'X-Report-Abuse', 'List-Id'
        );
        
        $neverAllowed = (array)Yii::app()->hooks->applyFilters('delivery_server_never_allowed_headers', $neverAllowed);
        return $neverAllowed;
    }
    
    public function getCustomerByDeliveryObject()
    {
        return self::parseDeliveryObjectForCustomer($this->getDeliveryObject());
    }
    
    public static function parseDeliveryObjectForCustomer($deliveryObject)
    {
        $customer = null;
        if ($deliveryObject && is_object($deliveryObject)) {
            if ($deliveryObject instanceof Customer) {
                $customer = $deliveryObject;
            } elseif ($deliveryObject instanceof Campaign) {
                $customer = !empty($deliveryObject->list) && !empty($deliveryObject->list->customer) ? $deliveryObject->list->customer : null;
            } elseif ($deliveryObject instanceof Lists) {
                $customer = !empty($deliveryObject->customer) ? $deliveryObject->customer : null;
            } elseif ($deliveryObject instanceof CustomerEmailTemplate) {
                $customer = !empty($deliveryObject->customer) ? $deliveryObject->customer : null;
            } elseif ($deliveryObject instanceof TransactionalEmail && !empty($deliveryObject->customer_id)) {
                $customer = !empty($deliveryObject->customer) ? $deliveryObject->customer : null;
            }
        }
        if (!$customer && Yii::app()->hasComponent('customer') && Yii::app()->customer->getId() > 0) {
            $customer = Yii::app()->customer->getModel();
        }
        return $customer;
    }
    
    public function _validateAdditionalHeaders($attribute, $params)
    {
        $headers = $this->$attribute;
        if (empty($headers) || !is_array($headers)) {
            $headers = array();
        }

        $this->$attribute   = array();
        $_headers           = array();
        
        $notAllowedHeaders  = (array)$this->getNeverAllowedHeaders();
        $notAllowedHeaders  = array_map('strtolower', $notAllowedHeaders);
        
        // try to be a bit restrictive
        $namePattern        = '/([a-z0-9\-\_])*/i';
        $valuePattern       = '/.*/i';
        
        foreach ($headers as $index => $header) {
            
            if (!is_array($header) || !isset($header['name'], $header['value'])) {
                unset($headers[$index]);
                continue;
            }
            
            $prefix = Yii::app()->params['email.custom.header.prefix'];            
            $name   = preg_replace('/:\s/', '', trim($header['name']));
            $value  = preg_replace('/:/', '', trim($header['value']));

            if (empty($name) || in_array(strtolower($name), $notAllowedHeaders) || stripos($name, $prefix) === 0 || !preg_match($namePattern, $name)) {
                unset($headers[$index]);
                continue;
            }
            
            if (empty($value) || !preg_match($valuePattern, $value)) {
                unset($headers[$index]);
                continue;
            }
            
            $_headers[$name] = $value;
        }
        
        $this->$attribute = $_headers;
    }

    // main entry point to pick a delivery server.
    public static function pickServer($currentServerId = 0, $deliveryObject = null, $params = array())
    {
        $logTableName = DeliveryServerUsageLog::model()->tableName();
        $options      = Yii::app()->options;
        
        if ($customer = self::parseDeliveryObjectForCustomer($deliveryObject)) {
            $checkQuota = is_array($params) && isset($params['customerCheckQuota']) ? $params['customerCheckQuota'] : true;
            if ($checkQuota && $customer->getIsOverQuota()) {
                return false;
            }
            
            $condition = 't.customer_id = :customer_id AND (
            `t`.`hourly_quota` = 0 OR `t`.`hourly_quota` > (
                SELECT COUNT(*) FROM `'.$logTableName.'` 
                    WHERE server_id = `t`.`server_id` AND 
                    `date_added` BETWEEN DATE_FORMAT(NOW(), "%Y-%m-%d %H:00:00") AND DATE_FORMAT(NOW() + INTERVAL 1 HOUR, "%Y-%m-%d %H:00:00")
                )
            )';

            $criteria = new CDbCriteria();
            $criteria->condition = $condition;
            $criteria->params[':customer_id'] = (int)$customer->customer_id;
            
            $server = self::processPickServerCriteria($criteria, $currentServerId, $deliveryObject, $params);
            if (!empty($server)) {
                return $server;
            }

            if (!empty($customer->group_id)) {
                static $groupServers = array();
                if (!isset($groupServers[$customer->group_id])) {
                    $groupServers[$customer->group_id] = array();
                    $criteria = new CDbCriteria();
                    $criteria->select = 'server_id';
                    $criteria->compare('group_id', (int)$customer->group_id);
                    $models = DeliveryServerToCustomerGroup::model()->findAll($criteria);
                    foreach ($models as $model) {
                        $groupServers[$customer->group_id][] = (int)$model->server_id;
                    }
                }
                if (!empty($groupServers[$customer->group_id])) {
                    $condition = 't.customer_id IS NULL AND t.server_id IN('. implode(', ', array_map('intval', $groupServers[$customer->group_id])) .') AND (
                    `t`.`hourly_quota` = 0 OR `t`.`hourly_quota` > (
                        SELECT COUNT(*) FROM `'.$logTableName.'` 
                            WHERE server_id = `t`.`server_id` AND 
                            `date_added` BETWEEN DATE_FORMAT(NOW(), "%Y-%m-%d %H:00:00") AND DATE_FORMAT(NOW() + INTERVAL 1 HOUR, "%Y-%m-%d %H:00:00")
                        )
                    )';
                    $criteria = new CDbCriteria();
                    $criteria->condition = $condition;
                    $server = self::processPickServerCriteria($criteria, $currentServerId, $deliveryObject, $params);
                    if (!empty($server)) {
                        return $server;
                    }
                }
            }
            
            if ($customer->getGroupOption('servers.can_send_from_system_servers', 'yes') != 'yes') {
                return false;
            }
        }
        
        $condition = 't.customer_id IS NULL AND (
        `t`.`hourly_quota` = 0 OR `t`.`hourly_quota` > (
            SELECT COUNT(*) FROM `'.$logTableName.'` 
                WHERE server_id = `t`.`server_id` AND 
                `date_added` BETWEEN DATE_FORMAT(NOW(), "%Y-%m-%d %H:00:00") AND DATE_FORMAT(NOW() + INTERVAL 1 HOUR, "%Y-%m-%d %H:00:00")
            )
        )';

        $criteria = new CDbCriteria();
        $criteria->condition = $condition;
        return self::processPickServerCriteria($criteria, $currentServerId, $deliveryObject, $params);
    }

    protected static function processPickServerCriteria(CDbCriteria $criteria, $currentServerId = 0, $deliveryObject = null, $params = array())
    {
        static $campaignServers = array();
        $campaign_id = !empty($deliveryObject) && $deliveryObject instanceof Campaign ? (int)$deliveryObject->campaign_id : 0;
        
        if ($campaign_id > 0 && !isset($campaignServers[$campaign_id])) {
            $campaignServers[$campaign_id] = array();

            $customer  = $deliveryObject->customer;
            $canSelect = $customer->getGroupOption('servers.can_select_delivery_servers_for_campaign', 'no') == 'yes';

            if ($canSelect) {
                $_campaignServers = CampaignToDeliveryServer::model()->findAllByAttributes(array(
                    'campaign_id' => $deliveryObject->campaign_id,
                ));
                foreach ($_campaignServers as $mdl) {
                    $_criteria = new CDbCriteria();
                    $_criteria->select = 'server_id, hourly_quota';
                    $_criteria->compare('server_id', (int)$mdl->server_id);
                    $_criteria->addInCondition('status', array(self::STATUS_ACTIVE, self::STATUS_IN_USE));
                    $server = self::model()->find($_criteria);
                    if (!empty($server)) {
                        $checkQuota = is_array($params) && isset($params['serverCheckQuota']) ? $params['serverCheckQuota'] : true;
                        if ($checkQuota && !$server->getIsOverQuota()) {
                            $campaignServers[$campaign_id][] = $server->server_id;
                        } elseif (!$checkQuota) {
                            $campaignServers[$campaign_id][] = $server->server_id;
                        }
                    }
                }
                // if there are campaign servers specified but there are no valid servers, we stop!
                // note, not a final decision, maybe just allow this case after all.
                if (count($_campaignServers) > 0 && empty($campaignServers[$campaign_id])) {
                    return false;
                }
                unset($_campaignServers);    
            }
        }
        
        $_criteria = new CDbCriteria();
        $_criteria->select = 't.server_id, t.type';
        if ($campaign_id > 0 && !empty($campaignServers[$campaign_id])) {
            $_criteria->addInCondition('t.server_id', $campaignServers[$campaign_id]);
        }
        $_criteria->addInCondition('t.status', array(self::STATUS_ACTIVE, self::STATUS_IN_USE));
        
        // since 1.3.5
        if (!empty($params['useFor']) && is_array($params['useFor']) && array_search(self::USE_FOR_ALL, $params['useFor']) === false) {
            $_criteria->addInCondition('t.use_for', array_merge(array(self::USE_FOR_ALL), $params['useFor']));
        }
        //
        
        $_criteria->order = 't.probability DESC';
        $_criteria->mergeWith($criteria);

        $_servers = self::model()->findAll($_criteria);
        if (empty($_servers)) {
            return false;
        }
        
        $mapping = self::getTypesMapping();
        foreach ($_servers as $index => $srv) {
            if (!isset($mapping[$srv->type])) {
                unset($_servers[$index]);
                continue;
            }
            $_servers[$index] = self::model($mapping[$srv->type])->findByPk($srv->server_id);
        }

        if (empty($_servers)) {
            return false;
        }
        
        $probabilities  = array();
        foreach ($_servers as $srv) {
            if (!isset($probabilities[$srv->probability])) {
                $probabilities[$srv->probability] = array();
            }
            $probabilities[$srv->probability][] = $srv;
        }
        
        $server                 = null;
        $probabilitySum         = array_sum(array_keys($probabilities));
        $probabilityPercentage  = array();
        $cumulative             = array();
        
        foreach ($probabilities as $probability => $probabilityServers) {
            $probabilityPercentage[$probability] = ($probability / $probabilitySum) * 100;
        }
        asort($probabilityPercentage);
        
        foreach ($probabilityPercentage as $probability => $percentage) {
            $cumulative[$probability] = end($cumulative) + $percentage;
        }
        asort($cumulative);
        
        $lowest      = floor(current($cumulative));
        $probability = rand($lowest, 100);

        foreach($cumulative as $key => $value) {
            if ($value > $probability)  {
                $rand   = array_rand(array_keys($probabilities[$key]), 1);
                $server = $probabilities[$key][$rand];
                break;
            }
        }

        if (empty($server)) {
            $rand   = array_rand(array_keys($_servers), 1);
            $server = $_servers[$rand];
        }
        
        if (count($_servers) > 1 && $currentServerId > 0 && $server->server_id == $currentServerId) {
            return self::processPickServerCriteria($criteria, $server->server_id, $deliveryObject);
        }
        
        $server->getMailer()->reset();
        
        if (empty($deliveryObject)) {
            $server->setDeliveryFor(self::DELIVERY_FOR_SYSTEM);
        } elseif ($deliveryObject instanceof Campaign) {
            $server->setDeliveryFor(self::DELIVERY_FOR_CAMPAIGN);
        } elseif ($deliveryObject instanceof Lists) {
            $server->setDeliveryFor(self::DELIVERY_FOR_LIST);
        } elseif ($deliveryObject instanceof CustomerEmailTemplate) {
            $server->setDeliveryFor(self::DELIVERY_FOR_TEMPLATE_TEST);
        }
        
        return $server;
    }
    
    public function saveStatus($status = null)
    {
        if (empty($this->server_id)) {
            return false;
        }
        if ($status) {
            $this->status = $status;
        }
        return Yii::app()->getDb()->createCommand()->update($this->tableName(), array('status' => $this->status), 'server_id = :sid', array(':sid' => (int)$this->server_id));
    }
    
    // since 1.3.5.4, this is not a filter hook anymore
    public function _handleTrackingDomain(array $params)
    {
        if (empty($this->tracking_domain_id) || empty($this->trackingDomain) || empty($this->trackingDomain->name)) {
            return $params;
        }
        if (!empty($params['body']) || !empty($params['plainText'])) {
            $currentDomainName  = parse_url(Yii::app()->options->get('system.urls.frontend_absolute_url'), PHP_URL_HOST);
            $trackingDomainName = strpos($this->trackingDomain->name, 'http') !== 0 ? 'http://' . $this->trackingDomain->name : $this->trackingDomain->name;
            $trackingDomainName = parse_url($trackingDomainName, PHP_URL_HOST);
            if (!empty($currentDomainName) && !empty($trackingDomainName)) {
                $searchReplace = array(
                    'https://www.' . $currentDomainName => 'http://' . $trackingDomainName,
                    'http://www.' . $currentDomainName  => 'http://' . $trackingDomainName,
                    'https://' . $currentDomainName     => 'http://' . $trackingDomainName,
                    $currentDomainName                  => $trackingDomainName,
                );
                $searchFor   = array_keys($searchReplace);
                $replaceWith = array_values($searchReplace);
                
                $params['body']      = str_replace($searchFor, $replaceWith, $params['body']);
                $params['plainText'] = str_replace($searchFor, $replaceWith, $params['plainText']);
                if (!empty($params['headers']) && is_array($params['headers'])) {
                    foreach ($params['headers'] as $name => $value) {
                        if (strpos($value, $currentDomainName) !== false) {
                            $params['headers'][$name] = str_replace($searchFor, $replaceWith, $value);
                        }
                    }
                }
                $params['trackingDomain'] = $trackingDomainName;
                $params['currentDomain']  = $currentDomainName;
            }
        }
        return $params;
    }
    
    public function copy()
    {
        $copied = false;
        
        if ($this->isNewRecord) {
            return $copied;
        }
        
        $transaction = Yii::app()->db->beginTransaction();
        
        try {
            
            $server = clone $this;
            $server->isNewRecord  = true;
            $server->server_id    = null;
            $server->status       = self::STATUS_DISABLED;
            $server->date_added   = new CDbExpression('NOW()');
            $server->last_updated = new CDbExpression('NOW()');
            
            if (!empty($server->name)) {
                if (preg_match('/\#(\d+)$/', $server->name, $matches)) {
                    $counter = (int)$matches[1];
                    $counter++;
                    $server->name = preg_replace('/\#(\d+)$/', '#' . $counter, $server->name);
                } else {
                    $server->name .= ' #1';
                }
            }
            
            if (!$server->save(false)) {
                throw new CException($server->shortErrors->getAllAsString());
            }
            
            if (!empty($this->domainPolicies)) {
                foreach ($this->domainPolicies as $policy) {
                    $policy = clone $policy;
                    $policy->domain_id = null;
                    $policy->server_id = $server->server_id;
                    $policy->date_added   = new CDbExpression('NOW()');
                    $policy->last_updated = new CDbExpression('NOW()');
                    $policy->save(false);
                }
            }

            $transaction->commit();
            $copied = $server;
        } catch (Exception $e) {
            $transaction->rollBack(); 
        }

        return $copied;
    }
    
    public function getIsDisabled()
    {
        return $this->status == self::STATUS_DISABLED;
    }
    
    public function getIsActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }
    
    public function enable()
    {
        if (!$this->getIsDisabled()) {
            return false;
        }
        $this->status = self::STATUS_ACTIVE;
        return $this->save(false);
    }
    
    public function disable()
    {
        if (!$this->getIsActive()) {
            return false;
        }
        $this->status = self::STATUS_DISABLED;
        return $this->save(false);
    }
    
    public function getForceFromOptions()
    {
        return array(
            self::FORCE_FROM_NEVER => ucfirst(Yii::t('servers', self::FORCE_FROM_NEVER)),
            self::FORCE_FROM_ALWAYS => ucfirst(Yii::t('servers', self::FORCE_FROM_ALWAYS)),
            self::FORCE_FROM_WHEN_NO_SIGNING_DOMAIN => ucfirst(Yii::t('servers', self::FORCE_FROM_WHEN_NO_SIGNING_DOMAIN)),
        );    
    }
    
    public function getForceReplyToOptions()
    {
        return array(
            self::FORCE_REPLY_TO_NEVER  => ucfirst(Yii::t('servers', self::FORCE_REPLY_TO_NEVER)),
            self::FORCE_REPLY_TO_ALWAYS => ucfirst(Yii::t('servers', self::FORCE_REPLY_TO_ALWAYS)),
        );    
    }
    
    public function getUseForOptions()
    {
        return array(
            self::USE_FOR_ALL           => ucfirst(Yii::t('servers', self::USE_FOR_ALL)),
            self::USE_FOR_CAMPAIGNS     => ucfirst(Yii::t('servers', self::USE_FOR_CAMPAIGNS)),
            self::USE_FOR_TRANSACTIONAL => ucfirst(Yii::t('servers', self::USE_FOR_TRANSACTIONAL . ' emails')),
        );
    }
    
    public function getUseFor($for)
    {
        return in_array($this->use_for, array(self::USE_FOR_ALL, $for));
    }
    
    public function getUseForCampaigns()
    {
        return $this->getUseFor(self::USE_FOR_CAMPAIGNS);
    }
    
    public function getUseForTransactional()
    {
        return $this->getUseFor(self::USE_FOR_TRANSACTIONAL);
    }
    
    public function getQueueName()
    {
        return self::DEFAULT_QUEUE_NAME;
    }
    
    public function getUseQueue()
    {
        return $this->use_queue == self::TEXT_YES && $this->getCanUseQueue();
    }
    
    public function enableLogUsage()
    {
        $this->_logUsage = true;
        return $this;
    }
    
    public function disableLogUsage()
    {
        $this->_logUsage = false;
        return $this;
    }
    
    public function getCanUseQueue()
    {
        static $canUse;
        if ($canUse !== null) {
            return $canUse;
        }
        
        if (!MW_COMPOSER_SUPPORT) {
            return $canUse = false;
        }
        
        $canUse = true;
        // we should do better here in the future
        if (Yii::app()->options->get('system.queue.redis_queue.enabled', 'no') !== self::TEXT_YES) {
            return $canUse = false;
        }
        try {
            Yii::app()->queue->size(self::DEFAULT_QUEUE_NAME);
        } catch (Exception $e) {
            $canUse = false;
        }
        return $canUse;
    }
    
    public function getSigningEnabled()
    {
        return $this->signing_enabled == self::TEXT_YES && in_array($this->type, $this->getSigningSupportedTypes());
    }
    
    public function getTrackingEnabled()
    {
        return !empty($this->tracking_domain_id) && !empty($this->trackingDomain) && !empty($this->trackingDomain->name);
    }
    
    public function getImportExportAllowedAttributes()
    {
        $allowedAttributes = array(
            'type',
            'name',
            'hostname',
            'username',
            'password',
            'port',
            'protocol',
            'timeout',
            'from_email',
            'from_name',
            'reply_to_email',
            'hourly_quota'
        );
        return (array)Yii::app()->hooks->applyFilters('delivery_servers_get_import_export_allowed_attributes', $allowedAttributes);
    }
    
    public function getCampaignQueueEmailsChunkSize()
    {
        static $chunkSize;
        if ($chunkSize !== null) {
            return (int)$chunkSize;
        }
        return $chunkSize = (int)Yii::app()->hooks->applyFilters('delivery_servers_get_campaign_queue_emails_chunk_size', 100);    
    }
    
    public function pushEmailInCampaignQueue(array $email)
    {
        if (!isset($email['server_id'], $email['server_type'], $email['campaign_id'], $email['params'])) {
            return false;
        }
        $key = sha1($email['server_id'] . $email['server_type'] . $email['campaign_id']);    
        if (!isset($this->_campaignQueueEmails[$key])) {
            $this->_campaignQueueEmails[$key] = array();
        }
        $this->_campaignQueueEmails[$key][] = $email;
        return true;
    }
    
    public function __destruct()
    {
        if (!empty($this->_campaignQueueEmails) && $this->getUseQueue()) {
            foreach ($this->_campaignQueueEmails as $key => $emails) {
                $_emails = array_chunk($emails, (int)$this->getCampaignQueueEmailsChunkSize());
                foreach ($_emails as $emailsChunk) {
                    Yii::app()->queue->enqueue($this->getQueueName(), 'SendEmailsFromQueue', array('emails' => $emailsChunk));
                }
            }
        }
    }
}
