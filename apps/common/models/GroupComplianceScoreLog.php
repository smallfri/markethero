<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * This is the model class for table "{{group_email_compliance_score}}".
 *
 * The followings are the available columns in table '{{group_email_compliance_score}}':
 * @property integer $id
 * @property integer $bounce_report
 * @property integer $abuse_report
 * @property integer $unsubscribe_report
 * @property integer $score
 * @property string $result
 * @property string $date_added
 * @property string $last_updated
 *
 */

class GroupComplianceScoreLog extends ActiveRecord
{

    public $id;

    public $bounce_report;

    public $abuse_report;

    public $unsubscribe_report;

    public $score;

    public $result;

    public $date_added;

    public $last_updated;

    const RESULT_APPROVED = 'approved';

    const RESULT_REJECTED = 'rejected';

    const RESULT_MANUAL_REVIEW = 'manual-review';


    /**
     * @return string the associated database table name
     */
    public function tableName()
    {

        return '{{group_email_compliance_score}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {

        $rules = array(
            array('bounce_report', 'numerical')

        );
        return CMap::mergeArray($rules, parent::rules());
    }


    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {

        $labels = array(
            'id' => Yii::t('group', 'Email'),
            'bounce_report' => Yii::t('group', 'Bounce Report'),
            'abuse_report' => Yii::t('group', 'Abusee Report'),
            'unscubscribe_report' => Yii::t('group', 'Unsub Report'),
            'score' => Yii::t('group', 'Score'),
            'result' => Yii::t('group', 'Result'),
            'date_added' => Yii::t('group', 'Date'),
            'last_updated' => Yii::t('group', 'Date'),

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
