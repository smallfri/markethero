<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * This is the model class for table "{{group_email_abuse_report}}".
 *
 * The followings are the available columns in table '{{group_email_abuse_report}}':
 * @property integer $report_id
 * @property integer $customer_id
 * @property string $reason
 * @property string $log
 * @property string $date_added
 * @property string $last_updated
 *
 */

class GroupAbuseReport extends ActiveRecord
{


    /**
     * @return string the associated database table name
     */
    public function tableName()
    {

        return '{{group_email_abuse_report}}';
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
            'report_id' => Yii::t('group', 'Email'),
            'customer_id' => Yii::t('group', 'Email'),
            'reason' => Yii::t('group', 'Customer ID'),
            'log' => Yii::t('group', 'Send at'),

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
