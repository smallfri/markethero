<?php defined('MW_PATH') || exit('No direct script access allowed');
/**
 * GroupBounceLog
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
 
/**
 * This is the model class for table "group_email_bounce_log".
 *
 * The followings are the available columns in table 'group_email_bounce_log':
 * @property string $log_id
 * @property integer $group_email_id
 * @property integer $customer_id
 * @property string $message
 * @property string $email_address
 * @property string $bounce_type
 * @property string $processed
 * @property string $date_added

 *
 */
class GroupBounceLog extends ActiveRecord
{
    const BOUNCE_SOFT = 'soft';
    
    const BOUNCE_HARD = 'hard';

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{group_email_bounce_log}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        $rules = array(
            array('bounce_type', 'safe', 'on' => 'customer-search'),
            array('group_email_id, customer_id, message, processed, bounce_type', 'safe', 'on' => 'search'),
        );

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = array(
            'log_id'        => Yii::t('groups', 'Log'),
            'group_email_id'   => Yii::t('groups', 'Group ID'),
            'message'       => Yii::t('groups', 'Message'),
            'processed'     => Yii::t('groups', 'Processed'),
            'bounce_type'   => Yii::t('groups', 'Bounce type'),
            
            // search
            'customer_id'   => Yii::t('groups', 'Customer'),
            'email_address'   => Yii::t('groups', 'Email Address'),

        );
        
        return CMap::mergeArray($labels, parent::attributeLabels());
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
    public function customerSearch()
    {
        $criteria=new CDbCriteria;
        $criteria->compare('group_email_id', (int)$this->group_email_id);
        $criteria->compare('bounce_type', $this->bounce_type);
        
        return new CActiveDataProvider(get_class($this), array(
            'criteria'      => $criteria,
            'pagination'    => array(
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ),
            'sort'  => array(
                'defaultOrder'  => array(
                    'log_id'    => CSort::SORT_DESC,
                ),
            ),
        ));
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
//    public function search()
//    {
//        $criteria=new CDbCriteria;
//        $criteria->select = 't.message, t.processed, t.bounce_type, t.date_added';
//        $criteria->with = array(
//            'campaign' => array(
//                'select'   => 'campaign.name, campaign.list_id, campaign.segment_id',
//                'joinType' => 'INNER JOIN',
//                'together' => true,
//                'with'     => array(
//                    'list' => array(
//                        'select'    => 'list.name',
//                        'joinType'  => 'INNER JOIN',
//                        'together'  => true,
//                    ),
//                    'customer' => array(
//                        'select'    => 'customer.customer_id, customer.first_name, customer.last_name',
//                        'joinType'  => 'INNER JOIN',
//                        'together'  => true,
//                    ),
//                ),
//            ),
//            'subscriber' => array(
//                'select'    => 'subscriber.email',
//                'joinType'  => 'INNER JOIN',
//                'together'  => true,
//            ),
//        );
//
//        if ($this->customer_id && is_numeric($this->customer_id)) {
//            $criteria->with['campaign']['with']['customer'] = array_merge($criteria->with['campaign']['with']['customer'], array(
//                'condition' => 'customer.customer_id = :customerId',
//                'params'    => array(':customerId' => $this->customer_id),
//            ));
//        } elseif ($this->customer_id && is_string($this->customer_id)) {
//            $criteria->with['campaign']['with']['customer'] = array_merge($criteria->with['campaign']['with']['customer'], array(
//                'condition' => 'CONCAT(customer.first_name, " ", customer.last_name) LIKE :customerName',
//                'params'    => array(':customerName' => '%'. $this->customer_id .'%'),
//            ));
//        }
//
//        if ($this->campaign_id && is_numeric($this->campaign_id)) {
//            $criteria->with['campaign'] = array_merge($criteria->with['campaign'], array(
//                'condition' => 'campaign.campaign_id = :campaignId',
//                'params'    => array(':campaignId' => $this->campaign_id),
//            ));
//        } elseif ($this->campaign_id && is_string($this->campaign_id)) {
//            $criteria->with['campaign'] = array_merge($criteria->with['campaign'], array(
//                'condition' => 'campaign.name LIKE :campaignName',
//                'params'    => array(':campaignName' => '%'. $this->campaign_id .'%'),
//            ));
//        }
//
//        if ($this->list_id && is_numeric($this->list_id)) {
//            $criteria->with['campaign']['with']['list'] = array_merge($criteria->with['campaign']['with']['list'], array(
//                'condition' => 'list.list_id = :listId',
//                'params'    => array(':listId' => $this->list_id),
//            ));
//        } elseif ($this->list_id && is_string($this->list_id)) {
//            $criteria->with['campaign']['with']['list'] = array_merge($criteria->with['campaign']['with']['list'], array(
//                'condition' => 'list.name LIKE :listName',
//                'params'    => array(':listName' => '%'. $this->list_id .'%'),
//            ));
//        }
//
//        if ($this->segment_id && is_numeric($this->segment_id)) {
//            $criteria->with['campaign']['with']['segment'] = array(
//                'condition' => 'segment.segment_id = :segmentId',
//                'params'    => array(':segmentId' => $this->segment_id),
//            );
//        } elseif ($this->segment_id && is_string($this->segment_id)) {
//            $criteria->with['campaign']['with']['segment'] = array(
//                'condition' => 'segment.name LIKE :segmentId',
//                'params'    => array(':segmentId' => '%'. $this->segment_id .'%'),
//            );
//        }
//
//        if ($this->subscriber_id && is_numeric($this->subscriber_id)) {
//            $criteria->with['subscriber'] = array_merge($criteria->with['subscriber'], array(
//                'condition' => 'subscriber.subscriber_id = :subscriberId',
//                'params'    => array(':subscriberId' => $this->subscriber_id),
//            ));
//        } elseif ($this->subscriber_id && is_string($this->subscriber_id)) {
//            $criteria->with['subscriber'] = array_merge($criteria->with['subscriber'], array(
//                'condition' => 'subscriber.email LIKE :subscriberId',
//                'params'    => array(':subscriberId' => '%'. $this->subscriber_id .'%'),
//            ));
//        }
//
//        $criteria->compare('t.message', $this->message, true);
//        $criteria->compare('t.processed', $this->processed);
//        $criteria->compare('t.bounce_type', $this->bounce_type);
//
//        $criteria->order = 't.log_id DESC';
//
//        return new CActiveDataProvider(get_class($this), array(
//            'criteria'      => $criteria,
//            'pagination'    => array(
//                'pageSize'  => $this->paginationOptions->getPageSize(),
//                'pageVar'   => 'page',
//            ),
//            'sort'  => array(
//                'defaultOrder'  => array(
//                    't.log_id'    => CSort::SORT_DESC,
//                ),
//            ),
//        ));
//    }
    
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
    public function searchLight()
    {
        $criteria=new CDbCriteria;
        $criteria->order = 't.log_id DESC';
        
        return new CActiveDataProvider(get_class($this), array(
            'criteria'      => $criteria,
            'pagination'    => array(
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ),
            'sort'  => array(
                'defaultOrder'  => array(
                    't.log_id'    => CSort::SORT_DESC,
                ),
            ),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return GroupBounceLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        // since 1.3.5.8
        $duplicate = self::model()->findByAttributes(array(
            'group_email_id'   => (int)$this->group_email_id,
            'customer_id' => (int)$this->customer_id,
            'email_address' => (int)$this->email_address,
        ));
        if (!empty($duplicate)) {
            return false;
        }

        return parent::beforeSave();
    }
    
    public function getBounceTypesArray()
    {
        return array(
            self::BOUNCE_SOFT => Yii::t('groups', self::BOUNCE_SOFT),
            self::BOUNCE_HARD => Yii::t('groups', self::BOUNCE_HARD),
        );
    }
}
