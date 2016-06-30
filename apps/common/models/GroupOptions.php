<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * This is the model class for table "{{group_email_options}}".
 *
 * The followings are the available columns in table '{{group_email_options}}':
 * @property integer $id
 * @property integer $groups_at_once
 * @property integer $emails_at_once
 * @property integer $emails_per_minute
 * @property integer $change_server_at
 * @property string  $memory_limit
 * @property integer $compliance_abuse_range
 * @property integer $compliance_unsub_range
 * @property integer $compliance_bounce_range
 * @property integer $groups_in_parallel
 * @property integer $group_emails_in_parallel
 *
 */

class GroupOptions extends ActiveRecord
{
    public $id;
    public $groups_at_once;
    public $emails_at_once;
    public $emails_per_minute;
    public $change_server_at;
    public $memory_limit;
    public $compliance_abuse_range;
    public $compliance_unsub_range;
    public $compliance_bounce_range;
    public $groups_in_parallel;
    public $group_emails_in_parallel;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {

        return '{{group_email_options}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {

//        $rules = array(
//            array('to_email, to_name, from_name, subject, body, send_at, status', 'required'),
//            array('to_email, to_name, from_email, from_name, reply_to_email, reply_to_name', 'length', 'max' => 150),
//            array('to_email, from_email, reply_to_email', 'email'),
//            array('subject', 'length', 'max' => 255),
//            array('send_at', 'date', 'format' => 'yyyy-mm-dd hh:mm:ss'),
//
//            // The following rule is used by search().
//            array(
//                'to_email, to_name, from_email, from_name, reply_to_email, reply_to_name, subject, status, group_email_id',
//                'safe',
//                'on' => 'search'
//            ),
//        );
//        return CMap::mergeArray($rules, parent::rules());
    }


    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {

        $labels = array(
            'id' => Yii::t('group', 'Email'),
            'groups_at_once' => Yii::t('group', 'Groups at once'),
            'emails_at_once' => Yii::t('group', 'Emails at once'),
            'change_server_at' => Yii::t('group', 'Change server at'),
            'compliance_abuse_range' => Yii::t('group', 'Compliance Abuse Range'),
            'compliance_unsub_range' => Yii::t('group', 'Compliance Unsub Range'),
            'compliance_bounce_range' => Yii::t('group', 'Compliance Bounce Range'),
            'date_added' => Yii::t('group', 'Date'),

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
     * @return GroupEmail the static model class
     */
    public static function model($className = __CLASS__)
    {

        return parent::model($className);
    }



}
