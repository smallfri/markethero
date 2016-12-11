<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * This is the model class for table "{{group_email_groups}}".
 *
 * The followings are the available columns in table '{{group_email_groups}}':
 * @property string $group_email_id
 * @property string $group_email_uid
 * @property integer $customer_id
 * @property string $send_at
 * @property string $status
 * @property string $finished_at
 * @property integer $emails_sent
 *   The followings are the available model relations:
 * @property Customer $customer
 * @property Customer $compliance
 * @property GroupEmailLog[] $logs
 *
 */

class Group extends ActiveRecord
{

    const STATUS_SENT = 'sent';

    const STATUS_UNSENT = 'unsent';

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_SENDING = 'pending-sending';

    const STATUS_SENDING = 'sending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_PAUSED = 'paused';

    const STATUS_PENDING_DELETE = 'pending-delete';

    const STATUS_IN_COMPLIANCE = 'in-compliance';

    const STATUS_APPROVED = 'approved';

    const STATUS_IN_REVIEW = 'in-review';

    const STATUS_MANUAL_REVIEW = 'manual-review';

    const TYPE_REGULAR = 'regular';

    const TYPE_AUTORESPONDER = 'autoresponder';

    const BULK_ACTION_PAUSE_UNPAUSE = 'pause-unpause';

    const BULK_ACTION_MARK_SENT = 'mark-sent';

    const EMAILS_SENT = 0;

    public $sendDirectly = false;

    public $group_email_id;

    public $group_email_uid;

    public $customer_id;

    public $status;

    public $emails_sent;

    public $send_at;

    public $finshed_at;

    public $date_added;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {

        return '{{group_email_groups}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {

        $rules = array(
            array('to_email, to_name, from_name, subject, body, send_at, status', 'required'),
            array('to_email, to_name, from_email, from_name, reply_to_email, reply_to_name', 'length', 'max' => 150),
            array('to_email, from_email, reply_to_email', 'email'),
            array('subject', 'length', 'max' => 255),
            array('send_at', 'date', 'format' => 'yyyy-mm-dd hh:mm:ss'),

            // The following rule is used by search().
            array(
                'to_email, to_name, from_email, from_name, reply_to_email, reply_to_name, subject, status, group_email_id',
                'safe',
                'on' => 'search'
            ),
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
            'logs' => array(self::HAS_MANY, 'GroupEmailLog', 'email_id'),
            'compliance' => array(self::HAS_MANY, 'GroupEmailCompliance', 'group_email_id'),
        );
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {

        $labels = array(
            'group_email_id' => Yii::t('group', 'Email'),
            'group_email_uid' => Yii::t('group', 'Email'),
            'customer_id' => Yii::t('group', 'Customer ID'),
            'send_at' => Yii::t('group', 'Send at'),
            'emails_sent' => Yii::t('group', 'Emails Sent'),
            'status' => Yii::t('group', 'status'),

        );
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

//    protected function afterConstruct()
//    {
//        if ($this->send_at == '0000-00-00 00:00:00') {
//            $this->send_at = null;
//        }
//        parent::afterConstruct();
//    }
//
//    protected function afterFind()
//    {
//        if ($this->send_at == '0000-00-00 00:00:00') {
//            $this->send_at = null;
//        }
//        parent::afterFind();
//    }

    protected function beforeValidate()
    {

        if (empty($this->send_at))
        {
            $this->send_at = date('Y-m-d H:i:s');
        }
        return parent::beforeValidate();
    }

//    protected function beforeSave()
//    {
//
//        if (empty($this->plain_text)&&!empty($this->body))
//        {
//            $this->plain_text = CampaignHelper::htmlToText($this->body);
//        }
////        if (empty($this->email_uid))
////        {
////            $this->email_uid = $this->generateUid();
////        }
//        if (EmailBlacklist::isBlacklisted($this->to_email))
//        {
//            $this->addError('to_email', Yii::t('group_emails', 'This email address is blacklisted!'));
//            return false;
//        }
//        return parent::beforeSave();
//    }

    // override parent implementation
    public function save($runValidation = true, $attributes = null)
    {

        if ($this->sendDirectly)
        {
            return $this->send();
        }
        return parent::save($runValidation, $attributes);
    }

    public function send()
    {

        static $servers = array();
        $this->sendDirectly = false;
        $serverParams = array(
            'customerCheckQuota' => false,
            'serverCheckQuota' => false,
            'useFor' => array(DeliveryServer::USE_FOR_TRANSACTIONAL)
        );

        $cid = (int)$this->customer_id;
        if (!array_key_exists($cid, $servers))
        {
            $servers[$cid] = DeliveryServer::pickServer(0, $this, $serverParams);
        }

        if (empty($servers[$cid]))
        {
            return false;
        }

        $server = $servers[$cid];

        if (!$server->canSendToDomainOf($this->to_email))
        {
            return false;
        }

        if (EmailBlacklist::isBlacklisted($this->to_email))
        {
            $this->delete();
            return false;
        }

        if ($server->getIsOverQuota())
        {
            $currentServerId = $server->server_id;
            if (!($servers[$cid] = DeliveryServer::pickServer($currentServerId, $this, $serverParams)))
            {
                unset($servers[$cid]);
                return false;
            }
            $server = $servers[$cid];
        }

        if (!empty($this->customer_id)&&$this->customer->getIsOverQuota())
        {
            return false;
        }

        $emailParams = array(
            'fromName' => $this->from_name,
            'to' => array($this->to_email => $this->to_name),
            'subject' => $this->subject,
            'body' => $this->body,
            'plainText' => $this->plain_text,
        );

        if (!empty($this->from_email))
        {
            $emailParams['from'] = array($this->from_email => $this->from_name);
        }

        if (!empty($this->reply_to_name)&&!empty($this->reply_to_email))
        {
            $emailParams['replyTo'] = array($this->reply_to_email => $this->reply_to_name);
        }

        $sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_TRANSACTIONAL)
            ->setDeliveryObject($this)
            ->sendEmail($emailParams);
        if ($sent)
        {
            $this->status = GroupEmail::STATUS_SENT;
        }
        else
        {
            $this->retries++;
        }

        $this->save(false);

        $log = new GroupEmailLog();
        $log->email_id = $this->email_id;
        $log->message = $server->getMailer()->getLog();
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

        $criteria = new CDbCriteria;

        $criteria->compare('t.to_email', $this->to_email, true);
        $criteria->compare('t.to_name', $this->to_name, true);
        $criteria->compare('t.from_email', $this->from_email, true);
        $criteria->compare('t.from_name', $this->from_name, true);
        $criteria->compare('t.reply_to_email', $this->reply_to_email, true);
        $criteria->compare('t.reply_to_name', $this->reply_to_name, true);
        $criteria->compare('t.subject', $this->subject, true);
        $criteria->compare('t.status', $this->status);
        $criteria->compare('t.group_email_id', $this->group_email_id);

        $criteria->order = 't.email_id DESC';

        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar' => 'page',
            ),
            'sort' => array(
                'defaultOrder' => array(
                    't.email_id' => CSort::SORT_DESC,
                ),
            ),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return GroupEmail the static model class
     */
    public static function model($className = __CLASS__)
    {

        return parent::model($className);
    }

//    public function findByUid($email_uid)
//    {
//
//        return $this->findByAttributes(array(
//            'email_uid' => $email_uid,
//        ));
//    }

    public function getByKey($groupId)
    {

        $criteria = new CDbCriteria();
        $criteria->condition = 'group_email_id ='.$groupId;
//        $criteria->limit = 3;

        return self::model()->find($criteria);
    }

//    public function generateUid()
//    {
//
//        $unique = StringHelper::uniqid();
//        $exists = $this->findByUid($unique);
//
//        if (!empty($exists))
//        {
//            return $this->generateUid();
//        }
//
//        return $unique;
//    }

//    public function getUid()
//    {
//
//        return $this->email_uid;
//    }

    public function getSendAt()
    {

        return $this->dateTimeFormatter->formatLocalizedDateTime($this->send_at);
    }

    public function getStatusesList()
    {

        return array(
            self::STATUS_SENT => Yii::t('group_emails', ucfirst(self::STATUS_SENT)),
            self::STATUS_UNSENT => Yii::t('group_emails', ucfirst(self::STATUS_UNSENT)),
        );
    }

    public function getIsProcessing()
    {

        return $this->status==self::STATUS_PROCESSING;
    }

    public function getIsBlacklisted()
    {

        // since 1.3.5.5
        if (MW_PERF_LVL&&MW_PERF_LVL&MW_PERF_LVL_DISABLE_SUBSCRIBER_BLACKLIST_CHECK)
        {
            return false;
        }

        // check since 1.3.4.7
        if ($this->status==self::STATUS_BLACKLISTED)
        {
            return true;
        }

        $blacklisted = EmailBlacklist::isBlacklisted($this->email, $this);

//        print_r(__CLASS__.'->'.__FUNCTION__.'['.__LINE__.']');
        // since 1.3.4.7
        if ($blacklisted&&$this->status!=self::STATUS_BLACKLISTED)
        {
            $criteria = new CDbCriteria();
            $criteria->compare('email_id', (int)$this->subscriber_id);
            ListSubscriber::model()->updateAll(array('status' => self::STATUS_BLACKLISTED), $criteria);
            $this->status = self::STATUS_BLACKLISTED;
        }

        return $blacklisted;
    }

    public function saveStatus($status = null)
    {
        if (empty($this->group_email_id)) {
            return false;
        }
        if ($status) {
            $this->status = $status;
        }
        $attributes = array('status' => $this->status);
        if ($this->status == self::STATUS_SENT) {
            $this->finished_at = $attributes['finished_at'] = new CDbExpression('NOW()');
        }
        return Yii::app()->getDb()->createCommand()->update($this->tableName(), $attributes, 'group_email_id = :geid', array(':geid' => (int)$this->group_email_id));
    }


    // since 1.3.5
        public function getIsActive()
        {

            $customer = Yii::app()->db->createCommand()
                        ->select('status')
                        ->from('mw_customer_new')
                        ->where('customer_id=:id', array(':id' => $this->customer_id))
                        ->queryRow();

            return $customer['status'];
        }

    public function saveNumberSent($group, $index)
    {

        Group::model()->updateByPk(
            $group->group_email_id,
            [
                'emails_sent' => $index
            ]);
    }

}
