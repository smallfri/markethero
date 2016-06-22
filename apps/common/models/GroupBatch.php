<?php defined('MW_PATH')||exit('No direct script access allowed');
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
 * This is the model class for table "group_email_batches".
 *
 * The followings are the available columns in table 'group_email_batches':
 * @property integer $group_batch_id
 * @property integer $group_email_id
 * @property integer $emails_sent
 * @property string $status
 * @property string $date_added
 *
 */
class GroupBatch extends ActiveRecord
{

    public $group_email_id;

    public $status;

    public $group_batch_id;

    public $date_added;

    public $emails_sent;

    public $date_started;

    public $date_finished;

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

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {

        return '{{group_email_batches}}';
    }

    public function rules()
    {

        return array(
            array('group_email_id, status, date_added, group_batch_id', 'required', 'on' => 'insert'),

            array('group_email_id', 'status', 'date_added', 'group_batch_uid', 'group_batch_id', 'safe'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {

        $labels = array(
            'group_email_id' => Yii::t('groups', 'Email Id'),
            'group_batch_id' => Yii::t('groups', 'Batch ID'),
            'date_added' => Yii::t('groups', 'Date'),
        );

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    public function save($runValidation = true, $attributes = null)
    {

        return parent::save($runValidation, $attributes);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return User the static model class
     */
    public static function model($className = __CLASS__)
    {

        return parent::model($className);
    }

    public function getByKey($batchId)
    {

        $criteria = new CDbCriteria();
        $criteria->condition = 'group_batch_id ='.$batchId;

        return self::model()->find($criteria);
    }

    public function saveStatus($status = null)
    {

        echo $this->group_batch_id;
        if (empty($this->group_batch_id))
        {
            return false;
        }
        if ($status)
        {
            $this->status = $status;
        }
        $attributes = array('status' => $this->status);
        if ($this->status==self::STATUS_SENT)
        {
            $this->date_finished = $attributes['date_finished'] = new CDbExpression('NOW()');
        }
        else
        {
            $this->date_started = $attributes['date_started'] = new CDbExpression('NOW()');
        }
        return Yii::app()
            ->getDb()
            ->createCommand()
            ->update($this->tableName(), $attributes, 'group_batch_id = :gbid',
                array(':gbid' => $this->group_batch_id));
    }

    public function saveNumberSent($batch, $index)
    {

        Group::model()->updateByPk(
            $batch->group_batch_id,
            [
                'emails_sent' => $index
            ]);
    }

}
