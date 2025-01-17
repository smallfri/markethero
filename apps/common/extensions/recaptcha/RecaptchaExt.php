<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * RecaptchaExt
 * 
 * @package MailWizz EMA
 * @subpackage recaptcha
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */
 
class RecaptchaExt extends ExtensionInit 
{
    // name of the extension as shown in the backend panel
    public $name = 'Recaptcha';
    
    // description of the extension as shown in backend panel
    public $description = 'Protect the public forms using Google\'s Recaptcha';
    
    // current version of this extension
    public $version = '1.0';
    
    // minimum app version
    public $minAppVersion = '1.3.5.8';
    
    // the author name
    public $author = 'Cristian Serban';
    
    // author website
    public $website = 'http://www.mailwizz.com/';
    
    // contact email address
    public $email = 'cristian.serban@mailwizz.com';
    
    // in which apps this extension is allowed to run
    public $allowedApps = array('backend', 'frontend');

    // can this extension be deleted? this only applies to core extensions.
    protected $_canBeDeleted = false;
    
    // can this extension be disabled? this only applies to core extensions.
    protected $_canBeDisabled = true;
    
    // run the extension
    public function run()
    {
        Yii::import('ext-recaptcha.common.models.*');

        if ($this->isAppName('backend')) {
            // add the url rules
            Yii::app()->urlManager->addRules(array(
                array('ext_recaptcha_settings/index', 'pattern' => 'extensions/recaptcha/settings'),
                array('ext_recaptcha_settings/<action>', 'pattern' => 'extensions/recaptcha/settings/*'),
            ));
            
            // add the controller
            Yii::app()->controllerMap['ext_recaptcha_settings'] = array(
                'class'     => 'ext-recaptcha.backend.controllers.Ext_recaptcha_settingsController',
                'extension' => $this,
            );
        }

        // keep these globally for easier access from the callback.
        Yii::app()->params['extensions.recaptcha.data.enabled']                = $this->getOption('enabled') == 'yes';
        Yii::app()->params['extensions.recaptcha.data.enabled_for_list_forms'] = $this->getOption('enabled_for_list_forms') == 'yes';
        Yii::app()->params['extensions.recaptcha.data.site_key']               = $this->getOption('site_key');
        Yii::app()->params['extensions.recaptcha.data.secret_key']             = $this->getOption('secret_key');
        
        if ($this->getOption('enabled') != 'yes' || strlen($this->getOption('site_key')) < 20 || strlen($this->getOption('secret_key')) < 20) {
            return;
        }

        if (Yii::app()->params['extensions.recaptcha.data.enabled_for_list_forms']) {
            Yii::app()->hooks->addAction('frontend_list_subscribe_at_transaction_start', array($this, '_listFormCheckSubmission'));
            Yii::app()->hooks->addFilter('frontend_list_subscribe_before_transform_list_fields', array($this, '_listFormAppendHtml'));

            Yii::app()->hooks->addAction('frontend_list_update_profile_at_transaction_start', array($this, '_listFormCheckSubmission'));
            Yii::app()->hooks->addFilter('frontend_list_update_profile_before_transform_list_fields', array($this, '_listFormAppendHtml'));
        }
    }

    // Add the landing page for this extension (settings/general info/etc)
    public function getPageUrl()
    {
        return Yii::app()->createUrl('ext_recaptcha_settings/index');
    }

    // callback to respond to the action hook: frontend_list_subscribe_at_transaction_start
    // this is inside a try/catch block so we have to throw an exception on failure.
    public function _listFormCheckSubmission()
    {
        $request  = Yii::app()->request;
        $response = AppInitHelper::simpleCurlPost('https://www.google.com/recaptcha/api/siteverify', array(
            'secret'   => Yii::app()->params['extensions.recaptcha.data.secret_key'],
            'response' => $request->getPost('g-recaptcha-response'),
            'remoteip' => $request->getUserHostAddress(),
        ));
        $response = CJSON::decode($response['message']);
        if (empty($response['success'])) {
            throw new Exception(Yii::t("lists", "Invalid captcha response!"));
        }
    }

    // callback to respond to the filter hook: frontend_list_subscribe_before_transform_list_fields
    public function _listFormAppendHtml($content)
    {
        $controller = Yii::app()->getController();
        $controller->getData('pageScripts')->add(array('src' => 'https://www.google.com/recaptcha/api.js'));
        
        $append  = sprintf('<div class="g-recaptcha pull-right" data-sitekey="%s"></div>', Yii::app()->params['extensions.recaptcha.data.site_key']);
        $append .= '<div class="clearfix"><!-- --></div>';

        return preg_replace('/\[LIST_FIELDS\]/', "[LIST_FIELDS]\n" . $append, $content, 1, $count);
    }
}