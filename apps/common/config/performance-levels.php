<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * Performance levels definition
 * 
 * DO NOT CHANGE THIS FILE IN ANY WAY, REALLY, DON'T!
 *  
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5.5
 */
    
$performanceLevels = array(
    'MW_PERF_LVL_DISABLE_DS_LOG_USAGE'                   => 2, // disable delivery server log usage
    'MW_PERF_LVL_DISABLE_CUSTOMER_QUOTA_CHECK'           => 4, // disable customer quota check
    'MW_PERF_LVL_DISABLE_DS_QUOTA_CHECK'                 => 8, // disable delivery server quota check
    'MW_PERF_LVL_DISABLE_DS_CAN_SEND_TO_DOMAIN_OF_CHECK' => 16, // disable checking if can send to domain of the email address
    'MW_PERF_LVL_DISABLE_SUBSCRIBER_BLACKLIST_CHECK'     => 32, // disable checking emails against blacklist
);

foreach ($performanceLevels as $constName => $constValue) {
    defined($constName) or define($constName, $constValue);
}

unset($performanceLevels, $constName, $constValue);