<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * IpLocationTelizeExtModel
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */
 
class IpLocationTelizeExtModel extends FormModel
{
    
    const STATUS_ENABLED = 'enabled';
    
    const STATUS_DISABLED = 'disabled';

    public $status = 'disabled';
    
    public $sort_order = 2;
    
    public $status_on_email_open = 'disabled';
    
    public $status_on_track_url = 'disabled';
    
    public $status_on_unsubscribe = 'disabled';
    
    public function rules()
    {
        $rules = array(
            array('status, status_on_email_open, status_on_track_url, status_on_unsubscribe, sort_order', 'required'),
            array('status, status_on_email_open, status_on_track_url, status_on_unsubscribe', 'in', 'range' => array(self::STATUS_ENABLED, self::STATUS_DISABLED)),
            array('sort_order', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 999),
            array('sort_order', 'length', 'min' => 1, 'max' => 3),
        );
        
        return CMap::mergeArray($rules, parent::rules());    
    }
    
    public function save($extensionInstance)
    {
        $extensionInstance->setOption('status', $this->status);
        $extensionInstance->setOption('status_on_email_open', $this->status_on_email_open);
        $extensionInstance->setOption('status_on_track_url', $this->status_on_track_url);
        $extensionInstance->setOption('status_on_unsubscribe', $this->status_on_unsubscribe);
        $extensionInstance->setOption('sort_order', (int)$this->sort_order);
        
        return $this;
    }
    
    public function populate($extensionInstance) 
    {
        $this->status               = $extensionInstance->getOption('status', $this->status);
        $this->status_on_email_open = $extensionInstance->getOption('status_on_email_open', $this->status_on_email_open);
        $this->status_on_track_url  = $extensionInstance->getOption('status_on_track_url', $this->status_on_track_url);
        $this->status_on_unsubscribe= $extensionInstance->getOption('status_on_unsubscribe', $this->status_on_unsubscribe);
        $this->sort_order           = $extensionInstance->getOption('sort_order', (int)$this->sort_order);
        
        return $this;
    }
    
    public function attributeLabels()
    {
        $labels = array(
            'status_on_email_open'  => Yii::t('ext_ip_location_telize', 'Status on email open'),
            'status_on_track_url'   => Yii::t('ext_ip_location_telize', 'Status on track url'),
            'status_on_unsubscribe' => Yii::t('ext_ip_location_telize', 'Status on unsubscribe'),
            'sort_order'            => Yii::t('ext_ip_location_telize', 'Sort order'),
        );
        
        return CMap::mergeArray($labels, parent::attributeLabels());    
    }
    
    public function attributePlaceholders()
    {
        $placeholders = array();
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }
    
    public function attributeHelpTexts()
    {
        $texts = array(
            'status'                => Yii::t('ext_ip_location_telize', 'Whether this service is enabled and can be used'),
            'status_on_email_open'  => Yii::t('ext_ip_location_telize', 'Whether to collect ip location information when a campaign email is opened'),
            'status_on_track_url'   => Yii::t('ext_ip_location_telize', 'Whether to collect ip location information when a campaign link is clicked and tracked'),
            'status_on_unsubscribe' => Yii::t('ext_ip_location_telize', 'Whether to collect ip location information when a subscriber unsubscribes via a campaign'),
            'sort_order'            => Yii::t('ext_ip_location_telize', 'If multiple location services active, sort order decides which one queries first'),
        );
        
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    public function getStatusesDropDown()
    {
        return array(
            self::STATUS_DISABLED   => Yii::t('app', 'Disabled'),
            self::STATUS_ENABLED    => Yii::t('app', 'Enabled'),
        );
    }
    
    public function getSortOrderDropDown()
    {
        $options = array();
        for ($i = 0; $i < 100; ++$i) {
            $options[$i] = $i;
        }
        return $options;
    }
}
