<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * TransactionalEmail
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

/**
 * This is the model class for table "{{transactional_email}}".
 *
 * The followings are the available columns in table '{{transactional_email}}':
 * @property string $email_id
 * @property string $email_uid
 * @property integer $customer_id
 * @property string $to_email
 * @property string $to_name
 * @property string $from_email
 * @property string $from_name
 * @property string $reply_to_email
 * @property string $reply_to_name
 * @property string $subject
 * @property string $body
 * @property string $plain_text
 * @property integer $priority
 * @property integer $retries
 * @property integer $max_retries
 * @property string $send_at
 * @property string $status
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 * @property TransactionalEmailLog[] $logs
 */
class TransactionalEmail extends ActiveRecord
{
    const STATUS_SENT    = 'sent';
    const STATUS_UNSENT  = 'unsent';

    public $sendDirectly = false;

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{transactional_email}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		$rules = array(
			array('to_email, to_name, from_name, subject, body, send_at', 'required'),
			array('to_email, to_name, from_email, from_name, reply_to_email, reply_to_name', 'length', 'max' => 150),
            array('to_email, from_email, reply_to_email', 'email', 'validateIDN' => true),
			array('subject', 'length', 'max' => 255),
            array('send_at', 'date', 'format' => 'yyyy-mm-dd hh:mm:ss'),

			// The following rule is used by search().
			array('to_email, to_name, from_email, from_name, reply_to_email, reply_to_name, subject, status', 'safe', 'on'=>'search'),
		);
        return CMap::mergeArray($rules, parent::rules());
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$relations = array(
			'customer' => array(self::BELONGS_TO, 'Customer', 'customer_id'),
			'logs'     => array(self::HAS_MANY, 'TransactionalEmailLog', 'email_id'),
		);
        return CMap::mergeArray($relations, parent::relations());
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		$labels = array(
			'email_id'       => Yii::t('transactional_emails', 'Email'),
			'customer_id'    => Yii::t('transactional_emails', 'Customer'),
			'to_email'       => Yii::t('transactional_emails', 'To email'),
			'to_name'        => Yii::t('transactional_emails', 'To name'),
			'from_email'     => Yii::t('transactional_emails', 'From email'),
			'from_name'      => Yii::t('transactional_emails', 'From name'),
			'reply_to_email' => Yii::t('transactional_emails', 'Reply to email'),
			'reply_to_name'  => Yii::t('transactional_emails', 'Reply to name'),
			'subject'        => Yii::t('transactional_emails', 'Subject'),
			'body'           => Yii::t('transactional_emails', 'Body'),
			'plain_text'     => Yii::t('transactional_emails', 'Plain text'),
			'priority'       => Yii::t('transactional_emails', 'Priority'),
			'retries'        => Yii::t('transactional_emails', 'Retries'),
			'max_retries'    => Yii::t('transactional_emails', 'Max retries'),
			'send_at'        => Yii::t('transactional_emails', 'Send at'),
		);
        return CMap::mergeArray($labels, parent::attributeLabels());
	}

    protected function afterConstruct()
    {
        if ($this->send_at == '0000-00-00 00:00:00') {
            $this->send_at = null;
        }
        parent::afterConstruct();
    }

    protected function afterFind()
    {
        if ($this->send_at == '0000-00-00 00:00:00') {
            $this->send_at = null;
        }
        parent::afterFind();
    }

    protected function beforeValidate()
    {
        if (empty($this->send_at)) {
            $this->send_at = date('Y-m-d H:i:s');
        }
        return parent::beforeValidate();
    }

    protected function beforeSave()
    {
        if (empty($this->plain_text) && !empty($this->body)) {
            $this->plain_text = CampaignHelper::htmlToText($this->body);
        }
        if (empty($this->email_uid)) {
            $this->email_uid = $this->generateUid();
        }
        if (EmailBlacklist::isBlacklisted($this->to_email, null, (!empty($this->customer_id) && !empty($this->customer) ? $this->customer : null))) {
            $this->addError('to_email', Yii::t('transactional_emails', 'This email address is blacklisted!'));
            return false;
        }
        return parent::beforeSave();
    }

    // override parent implementation
    public function save($runValidation = true, $attributes = null)
    {
        if ($this->sendDirectly) {
            return $this->send();
        }
        return parent::save($runValidation, $attributes);
    }

    public function send()
    {
        static $servers     = array();
        $this->sendDirectly = false;
        $serverParams       = array(
            'customerCheckQuota' => false,
            'serverCheckQuota'   => false,
            'useFor'             => array(DeliveryServer::USE_FOR_ALL)
        );

        $cid = (int)$this->customer_id;
        if (!array_key_exists($cid, $servers)) {
            $servers[$cid] = DeliveryServer::pickServer(0, $this, $serverParams);
        }

        if (empty($servers[$cid])) {
            return false;
        }

        $server = $servers[$cid];

        if (!$server->canSendToDomainOf($this->to_email)) {
            return false;
        }

        if (EmailBlacklist::isBlacklisted($this->to_email, null, (!empty($this->customer_id) && !empty($this->customer) ? $this->customer : null))) {
            $this->delete();
            return false;
        }

        if ($server->getIsOverQuota()) {
            $currentServerId = $server->server_id;
            if (!($servers[$cid] = DeliveryServer::pickServer($currentServerId, $this, $serverParams))) {
                unset($servers[$cid]);
                return false;
            }
            $server = $servers[$cid];
        }

//        if (!empty($this->customer_id) && $this->customer->getIsOverQuota()) {
//            print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
//
//            return false;
//        }

        $emailParams = array(
            'fromName'      => $this->from_name,
            'to'            => array($this->to_email => $this->to_name),
            'subject'       => $this->subject,
            'body'          => $this->body,
            'plainText'     => $this->plain_text,
        );

        if (!empty($this->from_email)) {
            $emailParams['from'] = array($this->from_email => $this->from_name);
        }

        if (!empty($this->reply_to_name) && !empty($this->reply_to_email)) {
            $emailParams['replyTo'] = array($this->reply_to_email => $this->reply_to_name);
        }

        $sent = $server->setDeliveryFor(DeliveryServer::USE_FOR_ALL)->setDeliveryObject($this)->sendEmail($emailParams);
        if ($sent) {
            $this->status = TransactionalEmail::STATUS_SENT;
        } else {
            $this->retries++;
        }

        $this->save(false);

        $log = new TransactionalEmailLog();
        $log->email_id = $this->email_id;
        $log->message  = $server->getMailer()->getLog();
        $log->save(false);

        return (bool)$sent;
    }

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('t.to_email', $this->to_email, true);
		$criteria->compare('t.to_name', $this->to_name, true);
		$criteria->compare('t.from_email', $this->from_email, true);
		$criteria->compare('t.from_name', $this->from_name, true);
		$criteria->compare('t.reply_to_email', $this->reply_to_email, true);
		$criteria->compare('t.reply_to_name', $this->reply_to_name, true);
		$criteria->compare('t.subject', $this->subject, true);
		$criteria->compare('t.status', $this->status);

        $criteria->order = 't.email_id DESC';

		return new CActiveDataProvider(get_class($this), array(
            'criteria'   => $criteria,
            'pagination' => array(
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ),
            'sort'=>array(
                'defaultOrder' => array(
                    't.email_id'  => CSort::SORT_DESC,
                ),
            ),
        ));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return TransactionalEmail the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    public function findByUid($email_uid)
    {
        return $this->findByAttributes(array(
            'email_uid' => $email_uid,
        ));
    }

    public function generateUid()
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    public function getUid()
    {
        return $this->email_uid;
    }

    public function getSendAt()
    {
        return $this->dateTimeFormatter->formatLocalizedDateTime($this->send_at);
    }

    public function getStatusesList()
    {
        return array(
            self::STATUS_SENT   => Yii::t('transactional_emails', ucfirst(self::STATUS_SENT)),
            self::STATUS_UNSENT => Yii::t('transactional_emails', ucfirst(self::STATUS_UNSENT)),
        );
    }
}

