<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * CampaignBounceLog
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
 
/**
 * This is the model class for table "group_email_bounce_log".
 *
 * The followings are the available columns in table 'campaign_bounce_log':
 * @property integer $log_id
 * @property integer $customer_id
 * @property string $group_id
 * @property string $message
 * @property string $email
 * @property string $bounce_type
 * @property string $processed
 * @property string $date_added
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property ListSubscriber $subscriber
 */
class GroupEmailBounceLog extends ActiveRecord
{
    const BOUNCE_SOFT = 'soft';
    
    const BOUNCE_HARD = 'hard';
    
    public $customer_id;

    public $group_id;

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
            array('customer_id, campaign_id, list_id, segment_id, subscriber_id, message, processed, bounce_type', 'safe', 'on' => 'search'),
        );

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        $relations = array(
            'campaign' => array(self::BELONGS_TO, 'Campaign', 'campaign_id'),
            'subscriber' => array(self::BELONGS_TO, 'ListSubscriber', 'subscriber_id'),
        );
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = array(
            'log_id'        => Yii::t('campaigns', 'Log'),
            'campaign_id'   => Yii::t('campaigns', 'Campaign'),
            'subscriber_id' => Yii::t('campaigns', 'Subscriber'),
            'message'       => Yii::t('campaigns', 'Message'),
            'processed'     => Yii::t('campaigns', 'Processed'),
            'bounce_type'   => Yii::t('campaigns', 'Bounce type'),
            
            // search
            'customer_id'   => Yii::t('campaigns', 'Customer'),
            'list_id'       => Yii::t('campaigns', 'List'),
            'segment_id'    => Yii::t('campaigns', 'Segment'),
        );
        
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    // override parent implementation
    public function save($runValidation = true, $attributes = null)
    {
        return parent::save($runValidation, $attributes);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignBounceLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

//    protected function beforeSave()
//    {
//        // since 1.3.5.8
//        $duplicate = self::model()->findByAttributes(array(
//            'campaign_id'   => (int)$this->campaign_id,
//            'subscriber_id' => (int)$this->subscriber_id,
//        ));
//        if (!empty($duplicate)) {
//            return false;
//        }
//
//        return parent::beforeSave();
//    }
    
    public function getBounceTypesArray()
    {
        return array(
            self::BOUNCE_SOFT => Yii::t('campaigns', self::BOUNCE_SOFT),
            self::BOUNCE_HARD => Yii::t('campaigns', self::BOUNCE_HARD),
        );
    }
}
