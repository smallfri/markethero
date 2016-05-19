<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * TransactionalEmailCompliance
 *
 */

/**
 * This is the model class for table "{{group_email_compliance}}".
 *
 * The followings are the available columns in table '{{group_email_compliance}}':
 *
 *
 * @property integer group_email_id
 * @property integer compliance_level_type_id
 * @property integer last_processed_id
 * @property integer compliance_approval_user_id
 * @property string date_added
 * @property string last_updated
 * @property integer offset
 * @property string compliance_status
 *
 */
class GroupEmailCompliance extends ActiveRecord
{

    public $sendDirectly = false;

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{group_email_compliance}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		$rules = array(
			array('group_email_id, compliance_level_type_id, date_added, last_updated', 'required'),
            array('date_added, last_updated', 'date', 'format' => 'yyyy-mm-dd hh:mm:ss'),

			// The following rule is used by search().
			array('group_email_id, compliance_level_type_id, date_added, last_updated, compliance_status, offset, compliance_approval_user_id, last_processed_id', 'safe', 'on'=>'search'),
		);
        return CMap::mergeArray($rules, parent::rules());
	}


	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		$labels = array(
			'group_email_id'       => Yii::t('group_email_compliance', 'Group ID'),
			'compliance_level_type_id'           => Yii::t('group_email_compliance', 'Compliance Level'),
			'last_processed_id'                  => Yii::t('group_email_compliance', 'Last Processed ID'),
			'compliance_approval_user_id'        => Yii::t('group_email_compliance', 'Approval User ID'),
			'date_added'                         => Yii::t('group_email_compliance', 'Date Added'),
			'last_updated'                       => Yii::t('group_email_compliance', 'Last Updated'),
			'offset'                             => Yii::t('group_email_compliance', 'Offset'),
			'compliance_status'                  => Yii::t('group_email_compliance', 'Compliance Status'),
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
//
//    protected function beforeValidate()
//    {
//        if (empty($this->send_at)) {
//            $this->send_at = date('Y-m-d H:i:s');
//        }
//        return parent::beforeValidate();
//    }
//
//    protected function beforeSave()
//    {
//        if (empty($this->plain_text) && !empty($this->body)) {
//            $this->plain_text = CampaignHelper::htmlToText($this->body);
//        }
//        if (empty($this->email_uid)) {
//            $this->email_uid = $this->generateUid();
//        }
//        if (EmailBlacklist::isBlacklisted($this->to_email)) {
//            $this->addError('to_email', Yii::t('transactional_emails', 'This email address is blacklisted!'));
//            return false;
//        }
//        return parent::beforeSave();
//    }

    // override parent implementation
    public function save($runValidation = true, $attributes = null)
    {
        return parent::save($runValidation, $attributes);
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

		$criteria->compare('t.group_email_id', $this->group_email_id, true);
		$criteria->compare('t.compliance_level_type_id', $this->compliance_level_type_id, true);
		$criteria->compare('t.date_added', $this->date_added, true);
		$criteria->compare('t.last_updated', $this->last_updated, true);
		$criteria->compare('t.compliance_status', $this->compliance_status, true);
		$criteria->compare('t.offset', $this->offset, true);
		$criteria->compare('t.compliance_approval_user_id', $this->compliance_approval_user_id, true);
		$criteria->compare('t.last_processed_id', $this->last_processed_id);

        $criteria->order = 't.group_email_id DESC';
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

    public function findEmails($params)
    {

        $criteria = new CDbCriteria();
        $criteria->condition = 'group_email_id = 1';
        $criteria->condition = 'status = "pending-sending"';
//        $criteria->limit = 3;

        return self::model()->find($criteria);
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
