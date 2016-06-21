<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * This is the model class for table "{{group_email_unsubscribe}}".
 *
 * The followings are the available columns in table '{{group_email_unsubscribe}}':
 * @property integer $id
 * @property integer $group_email_id
 * @property integer $customer_id
 * @property string $ip_address
 * @property string $user_agent
 * @property string $note
 * @property string $date_added
 *
 */

class GroupUnsubscribeReport extends ActiveRecord
{


    /**
     * @return string the associated database table name
     */
    public function tableName()
    {

        return '{{group_email_unsubscribe}}';
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
            'customer_id' => Yii::t('group', 'Customer Id'),
            'ip_address' => Yii::t('group', 'IP'),
            'user_agent' => Yii::t('group', 'User Agent'),
            'note' => Yii::t('group', 'Note'),
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
