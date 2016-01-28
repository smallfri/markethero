<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * RecaptchaExtCommon
 * 
 * @package MailWizz EMA
 * @subpackage Recaptcha
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */
 
class RecaptchaExtCommon extends FormModel
{
    public $enabled = 'no';

    public $enabled_for_list_forms = 'no';

    public $site_key;

    public $secret_key;

    public function rules()
    {
        $rules = array(
            array('site_key, secret_key', 'safe'),
            array('enabled, enabled_for_list_forms', 'in', 'range' => array_keys($this->getYesNoOptions())),
        );
        return CMap::mergeArray($rules, parent::rules());    
    }
    
    public function attributeLabels()
    {
        $labels = array(
            'enabled'                => Yii::t('app', 'Enabled'),
            'enabled_for_list_forms' => Yii::t('ext_recaptcha', 'Enabled for list forms'),
            'site_key'               => Yii::t('ext_recaptcha', 'Site key'),
            'secret_key'             => Yii::t('ext_recaptcha', 'Secret key'),
        );
        return CMap::mergeArray($labels, parent::attributeLabels());    
    }
    
    public function attributePlaceholders()
    {
        $placeholders = array(
            'site_key'   => '6LegYwsTBBBCCPdpjWct69ScnOMG9ZRv2vy8Xbbj',
            'secret_key' => '6LegYwsTBBBCCxQmCT54Q_0bIwZH94ogQwNQCpE',
        );
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }
    
    public function attributeHelpTexts()
    {
        $texts = array(
            'enabled'                => Yii::t('app', 'Whether the feature is enabled'),
            'enabled_for_list_forms' => Yii::t('ext_recaptcha', 'Whether the feature is enabled for list forms'),
            'site_key'               => Yii::t('ext_recaptcha', 'The site key for recaptcha service'),
            'secret_key'             => Yii::t('ext_recaptcha', 'The secret key for recaptcha service'),
        );
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
    
    public function save()
    {
        $extension  = $this->getExtensionInstance();
        $attributes = array('enabled', 'enabled_for_list_forms', 'site_key', 'secret_key');
        foreach ($attributes as $name) {
            $extension->setOption($name, $this->$name);
        }
        return $this;
    }
    
    public function populate() 
    {
        $extension  = $this->getExtensionInstance();
        $attributes = array('enabled', 'enabled_for_list_forms', 'site_key', 'secret_key');
        foreach ($attributes as $name) {
            $this->$name = $extension->getOption($name, $this->$name);
        }
        return $this;
    }
    
    public function getExtensionInstance()
    {
        return Yii::app()->extensionsManager->getExtensionInstance('recaptcha');
    }
}
