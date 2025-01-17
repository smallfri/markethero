<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * OptionCdn
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5.4
 */
 
class OptionCdn extends OptionBase
{
    // settings category
    protected $_categoryName = 'system.cdn';
    
    public $enabled = 'no';
    
    public $subdomain;
    
    public $use_for_email_assets = 'no';
    
    public function rules()
    {
        $rules = array(
            array('subdomain', '_validateSubdomain'),
            array('enabled, use_for_email_assets', 'in', 'range' => array_keys($this->getYesNoOptions())),
        );
        return CMap::mergeArray($rules, parent::rules());    
    }
    
    public function attributeLabels()
    {
        $labels = array(
            'enabled'              => Yii::t('settings', 'Enabled'),
            'subdomain'            => Yii::t('settings', 'Sub domain'),
            'use_for_email_assets' => Yii::t('settings', 'Use for email assets'),
        );
        
        return CMap::mergeArray($labels, parent::attributeLabels());    
    }
    
    public function attributePlaceholders()
    {
        $placeholders = array(
            'subdomain' => 'd160eil82t111i.cloudfront.net',
        );
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }
    
    public function attributeHelpTexts()
    {
        $texts = array(
            'enabled'              => Yii::t('settings', 'Whether the feature is enabled.'),
            'subdomain'            => Yii::t('settings', 'The CDN sub domain where the assets will be published and loaded from.'),
            'use_for_email_assets' => Yii::t('settings', 'Whether to publish the email assets, such as images, over the CDN.'),
        );
        
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
    
    public function _validateSubdomain($attribute, $params)
    {
        $validator = new CUrlValidator();
        $validator->allowEmpty = true;
        if (!empty($this->$attribute) && !$validator->validateValue(sprintf('http://%s/', $this->$attribute))) {
            $this->addError('subdomain', Yii::t('settings', 'Subdomain is not valid!'));
        }
    }
}
