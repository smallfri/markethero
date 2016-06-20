<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * OptionCustomization
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5.4
 */
 
class OptionCustomization extends OptionBase
{
    // settings category
    protected $_categoryName = 'system.customization';
    
    public $backend_logo_text;
    
    public $customer_logo_text;
    
    public $frontend_logo_text;
    
    public $backend_skin = 'blue';
    
    public $customer_skin = 'blue';
    
    public $frontend_skin = 'blue';
    
    public function rules()
    {
        $rules = array(
            array('backend_logo_text, customer_logo_text, frontend_logo_text', 'length', 'max' => 100),
            array('backend_skin, customer_skin, frontend_skin', 'length', 'max' => 100),
        );
        return CMap::mergeArray($rules, parent::rules());    
    }
    
    public function attributeLabels()
    {
        $labels = array(
            'backend_logo_text'  => Yii::t('settings', 'Backend logo text'),
            'customer_logo_text' => Yii::t('settings', 'Customer logo text'),
            'frontend_logo_text' => Yii::t('settings', 'Frontend logo text'),
            'backend_skin'       => Yii::t('settings', 'Backend skin'),
            'customer_skin'      => Yii::t('settings', 'Customer skin'),
            'frontend_skin'      => Yii::t('settings', 'Frontend skin'),
        );
        
        return CMap::mergeArray($labels, parent::attributeLabels());    
    }
    
    public function attributePlaceholders()
    {
        $placeholders = array(
            'backend_logo_text'  => Yii::t('app', 'Backend area'),
            'customer_logo_text' => Yii::t('app', 'Customer area'),
            'frontend_logo_text' => Yii::t('app', 'Frontend area'),
        );
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }
    
    public function attributeHelpTexts()
    {
        $texts = array(
            'backend_logo_text'  => Yii::t('settings', 'The text shown in backend area as the logo. Leave empty to use the defaults.'),
            'customer_logo_text' => Yii::t('settings', 'The text shown in customer area as the logo. Leave empty to use the defaults.'),
            'frontend_logo_text' => Yii::t('settings', 'The text shown in frontend as the logo. Leave empty to use the defaults.'),
            'backend_skin'       => Yii::t('settings', 'The CSS skin to be used in backend area.'),
            'customer_skin'      => Yii::t('settings', 'The CSS skin to be used in customer area.'),
            'frontend_skin'      => Yii::t('settings', 'The CSS skin to be used in frontend area.'),
        );
        
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
    
    public function getAppSkins($appName)
    {
        $skins = array('');
        $paths = array('root.assets.css', 'root.'.$appName.'.assets.css');
        foreach ($paths as $path) {
            foreach ((array)glob(Yii::getPathOfAlias($path) . '/skin-*.css') as $file) {
                $fileName = basename($file, '.css');
                if (strpos($fileName, 'skin-') === 0) {
                    $skins[] = $fileName;
                }
            }    
        }
        $_skins = array_unique($skins);
        $skins  = array();
        foreach ($_skins as $skin) {
            $skinName = str_replace('skin-', '', $skin);
            $skinName = preg_replace('/[^a-z0-9]/i', ' ', $skinName);
            $skinName = ucwords($skinName);
            $skins[$skin] = $skinName;
        }
        return $skins;
    }
}
