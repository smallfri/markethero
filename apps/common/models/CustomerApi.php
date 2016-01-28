<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * User
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
 
/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property integer $user_id
 * @property string $user_uid
 * @property integer $group_id
 * @property integer $language_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $timezone
 * @property string $avatar
 * @property string $removable
 * @property string $status
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property Language $language
 * @property UserGroup $group
 * @property UserAutoLoginToken[] $autoLoginTokens
 * @property PricePlanOrderNote[] $pricePlanOrderNotes
 */
class CustomerApi extends ActiveRecord
{
    public $customer_id;
    public $public;
    public $private;

    
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{customer_api_key}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        $avatarMimes = null;
        if (CommonHelper::functionExists('finfo_open')) {
            $avatarMimes = Yii::app()->extensionMimes->get(array('png', 'jpg', 'gif'))->toArray();
        }
        
        $rules = array(
            // when new user is created .
            array('customer_id, public, private', 'required', 'on' => 'insert'),
            // when a user is updated
            array('customer_id, public, private', 'required', 'on' => 'update'),
            //
            array('private, public', 'length', 'min' => 2, 'max' => 255),
            array('customer_id', 'numerical', 'integerOnly' => true),


        );
        
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
//        $relations = array(
//            'language'              => array(self::BELONGS_TO, 'Language', 'language_id'),
//            'group'                 => array(self::BELONGS_TO, 'UserGroup', 'group_id'),
//            'autoLoginTokens'       => array(self::HAS_MANY, 'UserAutoLoginToken', 'user_id'),
//            'pricePlanOrderNotes'   => array(self::HAS_MANY, 'PricePlanOrderNote', 'user_id'),
//        );
//
//        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = array(
            'customer_id'       => Yii::t('users', 'Customer ID'),
            'public'   => Yii::t('users', 'Public'),
            'private'      => Yii::t('users', 'Private'),

        );
        
        return CMap::mergeArray($labels, parent::attributeLabels());
    }
    
    /**
    * Retrieves a list of models based on the current search/filter conditions.
    * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
    */
//    public function search()
//    {
//        $criteria=new CDbCriteria;
//
//        $criteria->compare('first_name', $this->first_name, true);
//        $criteria->compare('last_name', $this->last_name, true);
//        $criteria->compare('email', $this->email, true);
//        $criteria->compare('status', $this->status);
//        $criteria->compare('group_id', $this->group_id);
//
//        return new CActiveDataProvider(get_class($this), array(
//            'criteria'      => $criteria,
//            'pagination'    => array(
//                'pageSize'  => $this->paginationOptions->getPageSize(),
//                'pageVar'   => 'page',
//            ),
//            'sort'  => array(
//                'defaultOrder'  => array(
//                    'user_id'   => CSort::SORT_DESC,
//                ),
//            ),
//        ));
//    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return User the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    protected function afterValidate()
    {
        parent::afterValidate();
        $this->handleUploadedAvatar();
    }
    
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }
        
        if ($this->isNewRecord) {
            $this->user_uid = $this->generateUid();
        }
        
        if (!empty($this->fake_password)) {
            $this->password = Yii::app()->passwordHasher->hash($this->fake_password);
        }
        
        if ($this->removable === self::TEXT_NO) {
            $this->status = self::STATUS_ACTIVE;
            $this->group_id = null;
        }
        
        return true;
    }
    
    protected function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        
        return $this->removable === self::TEXT_YES;
    }
    
    public function getFullName()
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name.' '.$this->last_name;
        }
    }
    
    public function getStatusesArray()
    {
        return array(
            self::STATUS_ACTIVE     => Yii::t('app', 'Active'),
            self::STATUS_INACTIVE   => Yii::t('app', 'Inactive'),
        );
    }
    
    public function getTimeZonesArray()
    {
        return DateTimeHelper::getTimeZones();
    }
    
    public function findByUid($user_uid)
    {
        return $this->findByAttributes(array(
            'user_uid' => $user_uid,
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

    public function getUid()
    {
        return $this->user_uid;
    }
    
    public function getGravatarUrl($size = 50)
    {
        $gravatar = sprintf('//www.gravatar.com/avatar/%s?s=%d', md5(strtolower(trim($this->email))), (int)$size);
        return Yii::app()->hooks->applyFilters('user_get_gravatar_url', $gravatar, $this, $size);
    }
    
    public function getAvatarUrl($width = 50, $height = 50, $forceSize = false)
    {
        if (empty($this->avatar)) {
            return $this->getGravatarUrl($width);
        }
        return ImageHelper::resize($this->avatar, $width, $height, $forceSize);
    }
    
    public function hasRouteAccess($route)
    {
        if (empty($this->group_id)) {
            return true;
        }
        return $this->group->hasRouteAccess($route);
    }
    
    protected function handleUploadedAvatar()
    {
        if ($this->hasErrors()) {
            return;
        }
        
        if (!($avatar = CUploadedFile::getInstance($this, 'new_avatar'))) {
            return;
        }
        
        $storagePath = Yii::getPathOfAlias('root.frontend.assets.files.avatars');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!@mkdir($storagePath, 0777, true)) {
                $this->addError('new_avatar', Yii::t('users', 'The avatars storage directory({path}) does not exists and cannot be created!', array(
                    '{path}' => $storagePath,
                )));
                return;
            }
        }
        
        $newAvatarName = uniqid(rand(0, time())) . '-' . $avatar->getName();
        if (!$avatar->saveAs($storagePath . '/' . $newAvatarName)) {
            $this->addError('new_avatar', Yii::t('users', 'Cannot move the avatar into the correct storage folder!'));
            return;
        }
        
        $this->avatar = '/frontend/assets/files/avatars/' . $newAvatarName;
    }
}
