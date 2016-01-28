<?php defined('MW_PATH') || exit('No direct script access allowed');

class BlueSimpleTheme extends ThemeInit
{
    public $name = 'Blue Simple';
    
    public $author = 'Serban George Cristian';
    
    public $website = 'http://www.mailwizz.com';
    
    public $email = 'cristian.serban@webinhabit.com';
    
    public $description = 'A blue theme (simple) for customers area';

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
} 