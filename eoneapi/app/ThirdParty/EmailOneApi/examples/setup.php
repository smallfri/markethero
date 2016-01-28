<?php
/**
 * This file contains an example of base setup for the EmailOneApi PHP-SDK.
 *
 *
 *
 *
 */
 
//exit('COMMENT ME TO TEST THE EXAMPLES!');

// require the autoloader class
require_once '../Autoloader.php';

// register the autoloader.
EmailOneApi_Autoloader::register();

// if using a framework that already uses an autoloading mechanism, like Yii for example, 
// you can register the autoloader like:
// Yii::registerAutoloader(array('EmailOneApi_Autoloader', 'autoloader'), true);

/**
 * Notes: 
 * If SSL present on the webhost, the api can be accessed via SSL as well (https://...).
 * A self signed SSL certificate will work just fine.
 * If the EmailOne powered website doesn't use clean urls,
 * make sure your apiUrl has the index.php part of url included, i.e: 
 * http://www.emailone-powered-website.tld/api/index.php
 * 
 * Configuration components:
 * The api for the EmailOne EMA is designed to return proper etags when GET requests are made.
 * We can use this to cache the request response in order to decrease loading time therefore improving performance.
 * In this case, we will need to use a cache component that will store the responses and a file cache will do it just fine.
 * Please see EmailOneApi/Cache for a list of available cache components and their usage.
 */

// configuration object
$config = new EmailOneApi_Config(array(
    'apiUrl'        => 'http://deva.emailone.net/api',
    'publicKey'     => '68134d5312eebc9965d87978b80c6d710ea8248d',
    'privateKey'    => '82f32116e089075d14c5cb73a2f656f90675b02c',
    
    // components
    'components' => array(
        'cache' => array(
            'class'     => 'EmailOneApi_Cache_File',
            'filesPath' => dirname(__FILE__) . '/../EmailOneApi/Cache/data/cache', // make sure it is writable by webserver
        )
    ),
));

// now inject the configuration and we are ready to make api calls
EmailOneApi_Base::setConfig($config);

// start UTC
date_default_timezone_set('UTC');