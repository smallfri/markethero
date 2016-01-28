<?php defined('MW_PATH') || exit('No direct script access allowed');

class BlueAdvancedTheme extends ThemeInit
{
    public $name = 'Blue Advanced';
    
    public $author = 'Serban George Cristian';
    
    public $website = 'http://www.mailwizz.com';
    
    public $email = 'cristian.serban@webinhabit.com';
    
    public $description = 'A blue theme(advanced) for customers area';

    public $version = '1.0';
    
    /**
     * The run method is called right at system init, therefore it can be used to register
     * assets, controllers, actions, hooks, etc.
     * 
     * The extensions are called before the theme, therefore extensions can hook in themes easily.
     */
    public function run()
    {
        // register the theme assets, scripts and styles
        Yii::app()->hooks->addFilter('register_scripts', array($this, '_registerScripts'));
        Yii::app()->hooks->addFilter('register_styles', array($this, '_registerStyles'));

        // add a test hook in campaigns overview area
        Yii::app()->hooks->addAction('customer_campaigns_overview_after_tracking_stats', array($this, '_sayHello'));
        
        // moreover, we can add a new controller with our custom functionality.
        // this controller is accessible at "/customer/index.php/bluetheme"
        Yii::app()->controllerMap['bluetheme'] = array(
            'class' => 'theme-blue.controllers.BlueThemeTestController',
        );
        
        // in the above example, you can use Yii::app()->urlManager->addRules() to change the url controller route.
        // also, you can add rules to override the default app controllers. 
        // for example to override the index action of the dashboard controller:
        /*
        Yii::app()->urlManager->addRules(array(
            array('controller_that_overrides_dashboard/index', 'pattern' => 'dashboard/index'),
        ), true);
        */
        
        // or a simple action to an existing controller, say dashboard
        // this action is accessible at "/customer/index.php/dashboard/bluetheme"
        Yii::app()->hooks->addFilter('customer_controller_dashboard_actions', array($this, '_addCustomAction'));
    }
    
    public function _registerScripts(CList $scripts)
    {
        $scripts->add(array('src' => AssetsUrl::themeJs('blue.js')));
        return $scripts;
    }
    
    public function _registerStyles(CList $styles)
    {
        $styles->add(array('src' => AssetsUrl::themeCss('blue.css')));
        return $styles;
    }
    
    public function _sayHello($collectionData)
    {
        $controller = $collectionData->controller;
        $campaign   = $controller->data->campaign;
        echo 'Hi, i am called in the theme file and you are the campaign called "' . CHtml::encode($campaign->name) . '"';
    }
    
    public function _addCustomAction($actions)
    {
        $actions->add('bluetheme', array(
            'class' => 'theme-blue.actions.BlueThemeTestAction',
        ));
        
        return $actions;
    }
} 