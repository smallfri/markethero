<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * EmailBlacklist
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
 
/**
 * This is the model class for table "email_blacklist".
 *
 * The followings are the available columns in table 'email_blacklist':
 * @property integer $email_id
 * @property integer $subscriber_id
 * @property string $email
 * @property string $reason
 * @property string $date_added
 * @property string $last_updated
 */
class EmailBlacklist extends ActiveRecord
{
    public $file;
    
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{email_blacklist}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        $mimes   = null;
        $options = Yii::app()->options;
        if ($options->get('system.importer.check_mime_type', 'yes') == 'yes' && CommonHelper::functionExists('finfo_open')) {
            $mimes = Yii::app()->extensionMimes->get('csv')->toArray();
        }
        
        $rules = array(
            array('email', 'required', 'on' => 'insert, update'),
            array('email', 'length', 'max' => 150),
            array('email', 'email'),
            array('email', 'unique'),
            
            array('reason', 'safe'),
            array('email', 'safe', 'on' => 'search'),
            
            array('email, reason', 'unsafe', 'on' => 'import'),
            array('file', 'required', 'on' => 'import'),
            array('file', 'file', 'types' => array('csv'), 'mimeTypes' => $mimes, 'maxSize' => 512000000, 'allowEmpty' => true),
        );

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        $relations = array();
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = array(
            'email_id'      => Yii::t('email_blacklist', 'Email'),
            'subscriber_id' => Yii::t('email_blacklist', 'Subscriber'),
            'email'         => Yii::t('email_blacklist', 'Email'),
            'reason'        => Yii::t('email_blacklist', 'Reason'),
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
    public function search()
    {
        $criteria = new CDbCriteria;
        $criteria->compare('email', $this->email, true);
        $criteria->compare('reason', $this->reason, true);
        
        return new CActiveDataProvider(get_class($this), array(
            'criteria'      => $criteria,
            'pagination'    => array(
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ),
            'sort'=>array(
                'defaultOrder'  => array(
                    'email_id'  => CSort::SORT_DESC,
                ),
            ),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return EmailBlacklist the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        // since 1.3.5.8
        $duplicate = self::model()->findByAttributes(array('email' => $this->email));
        if (!empty($duplicate)) {
            return false;
        }

        return parent::beforeSave();
    }
    
    protected function afterSave()
    {
        // since 1.3.5
        if (!empty($this->email) && strpos($this->email, '*') === false) {
            try {
                $criteria = new CDbCriteria();
                $criteria->addCondition('email = :e');
                $criteria->params[':e'] = $this->email;
                ListSubscriber::model()->updateAll(array('status' => ListSubscriber::STATUS_BLACKLISTED), $criteria);   
            } catch (Exception $e) {
                
            }
        }
        parent::afterSave();
    }
    
    public function delete()
    {
        // when taken out of blacklist remove all the log records
        // NOTE: when a subscriber is deleted the column subscriber_id gets nulled so that we keep 
        // the blacklist email for future additions.
        if (!empty($this->subscriber_id)) {
            try {
                $attributes = array('subscriber_id' => (int)$this->subscriber_id);
                CampaignDeliveryLog::model()->deleteAllByAttributes($attributes);
                CampaignDeliveryLogArchive::model()->deleteAllByAttributes($attributes);
                CampaignBounceLog::model()->deleteAllByAttributes($attributes);
            } catch (Exception $e) {
                
            }
        }
        
        return parent::delete();
    }
    
    
    public static function addToBlacklist($subscriber, $reason = null)
    {
        $email = $subscriber_id = null;
        
        if (is_object($subscriber) && $subscriber instanceof ListSubscriber && !empty($subscriber->subscriber_id)) {
            $subscriber_id = $subscriber->subscriber_id;
            $email         = $subscriber->email;
        } elseif (is_string($subscriber) && filter_var($subscriber, FILTER_VALIDATE_EMAIL)) {
            $email = $subscriber;
        } else {
            return false;
        }
        
        $exists = self::model()->findByAttributes(array('email' => $email));
        if (!empty($exists)) {
            return true;
        }

        $saved = false;
        try {
            $model = new self();
            $model->email         = $email;
            $model->subscriber_id = $subscriber_id;
            $model->reason        = $reason;
            $saved = $model->save();
        } catch (Exception $e) {}

        return $saved;
    }
    
    public static function isBlacklisted($email, $subscriber = null)
    {
        if (Yii::app()->options->get('system.email_blacklist.local_check', 'yes') == 'no') {
            return false;
        }
        
        // since 1.3.5.4
        static $regularExpressions;
        if ($regularExpressions === null) {
            $regularExpressions = explode("\n", Yii::app()->options->get('system.email_blacklist.regular_expressions'));
            $regularExpressions = (array)Yii::app()->hooks->applyFilters('email_blacklist_regular_expressions', $regularExpressions);
            $regularExpressions = array_unique(array_map('trim', $regularExpressions));
            foreach ($regularExpressions as $index => $expr) {
                if (empty($expr)) {
                    unset($regularExpressions[$index]);
                }
            }
        }
        if (!empty($regularExpressions) && is_array($regularExpressions)) {
            foreach ($regularExpressions as $regex) {
                if (preg_match($regex, $email)) {
                    return true;
                }
            }
        }
        // end 1.3.5.4 additions
        
        $emailParts = explode('@', $email);
        
        if (count($emailParts) != 2) {
            return false;
        }
        
        list($name, $domain) = $emailParts;
        
        /**
         * AR was switched to Query Builder in this use case for performance reasons!
         */
        $command = Yii::app()->getDb()->createCommand();
        $command->select('email_id')->from('{{email_blacklist}}')->where('email = :email', array(':email' => $email));
        if ($name != '*') {
            $command->orWhere('email = :em', array(':em' => '*@'.$domain));
        }
        $blacklisted = $command->queryRow();

        if (!empty($blacklisted)) {
            return true;
        }
        
        $hooks = Yii::app()->hooks;
        
        // return false or the reason for why blacklisted
        $blacklisted = $hooks->applyFilters('email_blacklist_is_email_blacklisted', false, $email, $subscriber);
        
        if ($blacklisted !== false) {
            self::addToBlacklist($email, null, (string)$blacklisted);
        }
        
        return $blacklisted;
    }
    
    public static function removeByEmail($email)
    {
        return self::model()->deleteAllByAttributes(array('email' => $email));
    }
}
