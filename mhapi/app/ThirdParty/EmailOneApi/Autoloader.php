<?php
/**
 * This file contains the autoloader class for the EmailOneApi PHP-SDK.
 *
 *
 *
 *
 */
 
 
/**
 * The EmailOneApi Autoloader class.
 * 
 * From within a Yii Application, you would load this as:
 * 
 * <pre>
 * require_once(Yii::getPathOfAlias('application.vendors.EmailOneApi.Autoloader').'.php');
 * Yii::registerAutoloader(array('EmailOneApi_Autoloader', 'autoloader'), true);
 * </pre>
 * 
 * Alternatively you can:
 * <pre>
 * require_once('Path/To/EmailOneApi/Autoloader.php');
 * EmailOneApi_Autoloader::register();
 * </pre>
 * 
 *
 * @package EmailOneApi
 * @since 1.0
 */
class EmailOneApi_Autoloader
{
    /**
     * The registrable autoloader
     * 
     * @param string $class
     */
    public static function autoloader($class)
    {
        if (strpos($class, 'EmailOneApi') === 0) {
            $className = str_replace('_', '/', $class);
            $className = substr($className, 12);
            
            if (is_file($classFile = dirname(__FILE__) . '/'. $className.'.php')) {
                require_once($classFile);
            }
        }
    }
    
    /**
     * Registers the EmailOneApi_Autoloader::autoloader()
     */
    public static function register()
    {
        spl_autoload_register(array('EmailOneApi_Autoloader', 'autoloader'));
    }
}