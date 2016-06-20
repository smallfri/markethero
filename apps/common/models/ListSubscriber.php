<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * ListSubscriber
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_subscriber".
 *
 * The followings are the available columns in table 'list_subscriber':
 * @property integer $subscriber_id
 * @property integer $list_id
 * @property string $unique_id
 * @property string $email
 * @property string $source
 * @property string $status
 * @property string $ip_address
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property CampaignBounceLog[] $bounceLogs
 * @property CampaignDeliveryLog[] $deliveryLogs
 * @property CampaignDeliveryLogArchive[] $deliveryLogsArchive
 * @property CampaignForwardFriend[] $forwardFriends
 * @property CampaignTrackOpen[] $trackOpens
 * @property CampaignTrackUnsubscribe[] $trackUnsubscribes
 * @property CampaignTrackUrl[] $trackUrls
 * @property EmailBlacklist $emailBlacklist
 * @property ListFieldValue[] $fieldValues
 * @property Lists $list
 */
class ListSubscriber extends ActiveRecord
{
    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_UNCONFIRMED = 'unconfirmed';

    const STATUS_UNSUBSCRIBED = 'unsubscribed';

    const STATUS_BLACKLISTED = 'blacklisted';

    const SOURCE_WEB = 'web';

    const SOURCE_API = 'api';

    const SOURCE_IMPORT = 'import';

    const BULK_SUBSCRIBE = 'subscribe';

    const BULK_UNSUBSCRIBE = 'unsubscribe';

    const BULK_DELETE = 'delete';

    const BULK_BLACKLIST = 'blacklist';

    // when select count(x) as counter
    public $counter = 0;

    // for search in multilists
    public $listIds = array();

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{list_subscriber}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        $rules = array(
            array('status', 'in', 'range' => array_keys($this->getStatusesList())),
            array('list_id, email, source, ip_address, status', 'safe', 'on' => 'search'),
        );
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        $relations = array(
            'bounceLogs'            => array(self::HAS_MANY, 'CampaignBounceLog', 'subscriber_id'),
            'deliveryLogs'          => array(self::HAS_MANY, 'CampaignDeliveryLog', 'subscriber_id'),
            'deliveryLogsArchive'   => array(self::HAS_MANY, 'CampaignDeliveryLogArchive', 'subscriber_id'),
            'forwardFriends'        => array(self::HAS_MANY, 'CampaignForwardFriend', 'subscriber_id'),
            'trackOpens'            => array(self::HAS_MANY, 'CampaignTrackOpen', 'subscriber_id'),
            'trackUnsubscribes'     => array(self::HAS_MANY, 'CampaignTrackUnsubscribe', 'subscriber_id'),
            'trackUrls'             => array(self::HAS_MANY, 'CampaignTrackUrl', 'subscriber_id'),
            'emailBlacklist'        => array(self::HAS_ONE, 'EmailBlacklist', 'subscriber_id'),
            'fieldValues'           => array(self::HAS_MANY, 'ListFieldValue', 'subscriber_id'),
            'list'                  => array(self::BELONGS_TO, 'Lists', 'list_id'),
        );

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = array(
            'subscriber_id' => Yii::t('list_subscribers', 'Subscriber'),
            'list_id'       => Yii::t('list_subscribers', 'List'),
            'unique_id'     => Yii::t('list_subscribers', 'Unique id'),
            'email'         => Yii::t('list_subscribers', 'Email'),
            'source'        => Yii::t('list_subscribers', 'Source'),
            'ip_address'    => Yii::t('list_subscribers', 'Ip Address'),
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

        if (!empty($this->list_id)) {
            $criteria->compare('t.list_id', (int)$this->list_id);
        } elseif (!empty($this->listIds)) {
            $criteria->addInCondition('t.list_id', array_map('intval', $this->listIds));
        }

        $criteria->compare('t.email', $this->email, true);
        $criteria->compare('t.source', $this->source);
        $criteria->compare('t.ip_address', $this->ip_address, true);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.subscriber_id DESC';

        return new CActiveDataProvider(get_class($this), array(
            'criteria'      => $criteria,
            'pagination'    => array(
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ),
            'sort'  => array(
                'defaultOrder'  => array(
                    't.subscriber_id'   => CSort::SORT_DESC,
                ),
            ),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSubscriber the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        if (empty($this->subscriber_uid)) {
            $this->subscriber_uid = $this->generateUid();
        }

        return parent::beforeSave();
    }

    public function findByUid($subscriber_uid)
    {
        return $this->findByAttributes(array(
            'subscriber_uid' => $subscriber_uid,
        ));
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

    public function getIsBlacklisted()
    {
        // since 1.3.5.5
        if (MW_PERF_LVL && MW_PERF_LVL & MW_PERF_LVL_DISABLE_SUBSCRIBER_BLACKLIST_CHECK) {
            return false;
        }

        // check since 1.3.4.7
        if ($this->status == self::STATUS_BLACKLISTED) {
            return true;
        }

        $blacklisted = EmailBlacklist::isBlacklisted($this->email, $this);

        // since 1.3.4.7
        if ($blacklisted && $this->status != self::STATUS_BLACKLISTED) {
            $criteria = new CDbCriteria();
            $criteria->compare('subscriber_id', (int)$this->subscriber_id);
            ListSubscriber::model()->updateAll(array('status' => self::STATUS_BLACKLISTED, 'last_updated' => new CDbExpression('NOW()')), $criteria);
            $this->status = self::STATUS_BLACKLISTED;
        }

        return $blacklisted;
    }

    public function addToBlacklist($reason = null)
    {
        if ($added = EmailBlacklist::addToBlacklist($this, $reason)) {
            $this->status = self::STATUS_BLACKLISTED;
        }
        return $added;
    }

    public function removeFromBlacklistByEmail()
    {
        return EmailBlacklist::removeByEmail($this->email);
    }

    public function getCanBeConfirmed()
    {
        return !in_array($this->status, array(self::STATUS_CONFIRMED, self::STATUS_BLACKLISTED));
    }

    public function getCanBeUnsubscribed()
    {
        return !in_array($this->status, array(self::STATUS_BLACKLISTED));
    }

    public function getCanBeDeleted()
    {
        return $this->getRemovable();
    }

    public function getRemovable()
    {
        $removable = true;
        if (!empty($this->list_id) && !empty($this->list) && !empty($this->list->customer_id) && !empty($this->list->customer)) {
            $removable = $this->list->customer->getGroupOption('lists.can_delete_own_subscribers', 'yes') == 'yes';
        }
        return $removable;
    }

    public function getUid()
    {
        return $this->subscriber_uid;
    }

    public function getStatusesList()
    {
        return array(
            self::STATUS_CONFIRMED      => Yii::t('list_subscribers', ucfirst(self::STATUS_CONFIRMED)),
            self::STATUS_UNCONFIRMED    => Yii::t('list_subscribers', ucfirst(self::STATUS_UNCONFIRMED)),
            self::STATUS_UNSUBSCRIBED   => Yii::t('list_subscribers', ucfirst(self::STATUS_UNSUBSCRIBED)),
        );
    }

    public function getFilterStatusesList()
    {
        return array_merge($this->getStatusesList(), array(
            self::STATUS_BLACKLISTED => Yii::t('list_subscribers', ucfirst(self::STATUS_BLACKLISTED)),
        ));
    }

    public function getBulkActionsList()
    {
        $list = array(
            self::BULK_SUBSCRIBE    => Yii::t('list_subscribers', ucfirst(self::BULK_SUBSCRIBE)),
            self::BULK_UNSUBSCRIBE  => Yii::t('list_subscribers', ucfirst(self::BULK_UNSUBSCRIBE)),
            self::BULK_DELETE       => Yii::t('list_subscribers', ucfirst(self::BULK_DELETE)),
        );

        if (!$this->getCanBeDeleted()) {
            unset($list[self::BULK_DELETE]);
        }

        return $list;
    }

    public function getSourcesList()
    {
        return array(
            self::SOURCE_API    => Yii::t('list_subscribers', ucfirst(self::SOURCE_API)),
            self::SOURCE_IMPORT => Yii::t('list_subscribers', ucfirst(self::SOURCE_IMPORT)),
            self::SOURCE_WEB    => Yii::t('list_subscribers', ucfirst(self::SOURCE_WEB)),
        );
    }

    public function copyToList($listId, $doTransaction = true)
    {

        $listId = (int)$listId;
        if (empty($listId) || $listId == $this->list_id) {
            return false;
        }

        static $targetLists      = array();
        static $cacheFieldModels = array();

        if (isset($targetLists[$listId]) || array_key_exists($listId, $targetLists)) {
            $targetList = $targetLists[$listId];
        } else {
            $targetList = $targetLists[$listId] = Lists::model()->findByPk($listId);
        }

        if (empty($targetList)) {
            return false;
        }

        $subscriber = self::model()->findByAttributes(array(
            'list_id' => $targetList->list_id,
            'email'   => $this->email
        ));

        // already there
        if (!empty($subscriber)) {
            return $subscriber;
        }

        $subscriber = clone $this;
        $subscriber->isNewRecord    = true;
        $subscriber->subscriber_id  = null;
        $subscriber->list_id        = $targetList->list_id;
        $subscriber->date_added     = new CDbExpression('NOW()');
        $subscriber->last_updated   = new CDbExpression('NOW()');
        $subscriber->subscriber_uid = $this->generateUid();
        $subscriber->addRelatedRecord('list', $targetList, false);

        if ($doTransaction) {
            $transaction = Yii::app()->getDb()->beginTransaction();
        }

        try {

            if (!$subscriber->save()) {
                throw new Exception(CHtml::errorSummary($subscriber));
            }


            $cacheListsKey = $this->list_id . '|' . $targetList->list_id;
            if (!isset($cacheFieldModels[$cacheListsKey])) {
                // the custom fields for source list
                $sourceFields = ListField::model()->findAllByAttributes(array(
                    'list_id' => $this->list_id,
                ));

                // the custom fields for target list
                $targetFields = ListField::model()->findAllByAttributes(array(
                    'list_id' => $targetList->list_id,
                ));

                // get only the same fields
                $_fieldModels = array();
                foreach ($sourceFields as $srcIndex => $sourceField) {
                    foreach ($targetFields as $trgIndex => $targetField) {
                        if ($sourceField->tag == $targetField->tag && $sourceField->type_id == $targetField->type_id) {
                            $_fieldModels[] = array($sourceField, $targetField);
                            unset($sourceFields[$srcIndex], $targetFields[$trgIndex]);
                            break;
                        }
                    }
                }
                $cacheFieldModels[$cacheListsKey] = $_fieldModels;
                unset($sourceFields, $targetFields, $_fieldModels);
            }
            $fieldModels = $cacheFieldModels[$cacheListsKey];

            if (empty($fieldModels)) {
                throw new Exception('No field models found, something went wrong!');
            }

            foreach ($fieldModels as $index => $models) {
                list($source, $target) = $models;
                $sourceValues = ListFieldValue::model()->findAllByAttributes(array(
                    'subscriber_id' => $this->subscriber_id,
                    'field_id'      => $source->field_id,
                ));
                foreach ($sourceValues as $sourceValue) {
                    $sourceValue = clone $sourceValue;
                    $sourceValue->value_id      = null;
                    $sourceValue->field_id      = $target->field_id;
                    $sourceValue->subscriber_id = $subscriber->subscriber_id;
                    $sourceValue->isNewRecord   = true;
                    $sourceValue->date_added    = new CDbExpression('NOW()');
                    $sourceValue->last_updated  = new CDbExpression('NOW()');
                    if (!$sourceValue->save()) {
                        throw new Exception(CHtml::errorSummary($sourceValue));
                    }
                }
                unset($models, $source, $target, $sourceValues, $sourceValue);
            }
            unset($fieldModels);

            if ($doTransaction) {
                $transaction->commit();
            }
        } catch (Exception $e) {
            if ($doTransaction) {
                $transaction->rollBack();
            } elseif (!empty($subscriber->subscriber_id)) {
                $subscriber->delete();
            }
            $subscriber = false;
        }

        return $subscriber;
    }

    // since 1.3.5 - this should be expanded in future
    public function takeListSubscriberAction($actionName)
    {
        if ($this->isNewRecord || empty($this->list_id)) {
            return $this;
        }

        if ($actionName == ListSubscriberAction::ACTION_SUBSCRIBE && $this->status != self::STATUS_CONFIRMED) {
            return $this;
        }

        if ($actionName == ListSubscriberAction::ACTION_UNSUBSCRIBE && $this->status == self::STATUS_CONFIRMED) {
            return $this;
        }

        $allowedActions = array_keys(ListSubscriberAction::model()->getActions());
        if (!in_array($actionName, $allowedActions)) {
            return $this;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'target_list_id';
        $criteria->compare('source_list_id', (int)$this->list_id);
        $criteria->compare('source_action', $actionName);

        $_lists = ListSubscriberAction::model()->findAll($criteria);
        if (empty($_lists)) {
            return $this;
        }

        $lists = array();
        foreach ($_lists as $list) {
            $lists[] = $list->target_list_id;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('email', $this->email);
        $criteria->addInCondition('list_id', $lists);
        $criteria->addInCondition('status', array(self::STATUS_CONFIRMED));

        self::model()->updateAll(array('status' => self::STATUS_UNSUBSCRIBED), $criteria);

        return $this;
    }

    public function getAllCustomFieldsWithValues()
    {
        static $fields = array();
        if (empty($this->subscriber_id)) {
            return array();
        }
        if (isset($fields[$this->subscriber_id])) {
            return $fields[$this->subscriber_id];
        }
        $fields[$this->subscriber_id] = array();

        $criteria = new CDbCriteria();
        $criteria->select = 'field_id, tag';
        $criteria->compare('list_id', $this->list_id);
        $_fields = ListField::model()->findAll($criteria);

        foreach ($_fields as $field) {
            $value = null;
            $criteria = new CDbCriteria();
            $criteria->select = 'value';
            $criteria->compare('field_id', (int)$field->field_id);
            $criteria->compare('subscriber_id', (int)$this->subscriber_id);
            $valueModels = ListFieldValue::model()->findAll($criteria);

            if (!empty($valueModels)) {
                $value = array();
                foreach($valueModels as $valueModel) {
                    $value[] = $valueModel->value;
                }
                $value = implode(', ', $value);
            }
            $fields[$this->subscriber_id][$field->tag] = CHtml::encode($value);
        }

        return $fields[$this->subscriber_id];
    }

    public function getCustomFieldValue($field)
    {
        $field  = strtoupper(str_replace(array('[', ']'), '', $field));
        $fields = $this->getAllCustomFieldsWithValues();
        $value  = isset($fields[$field]) || array_key_exists($fields, $tags) ? $fields[$field] : null;
        unset($fields);
        return $value;
    }

    public function hasOpenedCampaign(Campaign $campaign)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', (int)$campaign->campaign_id);
        $criteria->compare('subscriber_id', (int)$this->subscriber_id);
        return CampaignTrackOpen::model()->count($criteria) > 0;
    }
}
