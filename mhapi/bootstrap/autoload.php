<?php

define('LARAVEL_START', microtime(true));

/*
 * End Debug Code
 * =====================================================================
 */
/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
 *
 * Autoload the emailoneapi sdk autoloader
 *
 */

// require the autoloader class
require __DIR__.'/../app/ThirdParty/EmailOneApi/Autoloader.php';

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
    'apiUrl'        => 'http://localhost/api',
    'publicKey'     => 'dacd2b3a46ba2a517fbc36dee781896787923b6c',
     'privateKey'    => 'f886e635780a1d7eb6695cc04e7b6aba5e0f787a',

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

/*
|--------------------------------------------------------------------------
| Include The Compiled Class File
|--------------------------------------------------------------------------
|
| To dramatically increase your application's performance, you may use a
| compiled class file which contains all of the classes commonly used
| by a request. The Artisan "optimize" is used to create this file.
|
*/

$compiledPath = __DIR__.'/cache/compiled.php';

if (file_exists($compiledPath)) {
    require $compiledPath;
}
