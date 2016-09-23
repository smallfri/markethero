<?php

/**
 * Frontend application bootstrap file
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

// define the type of application we are creating.
define('MW_APP_NAME', 'frontend');

// and start an instance of it.
require_once(dirname(__FILE__) . '/apps/init.php');


/*
$config['version'] = '1.0.0';
/*

===========================================================================
v 1.0.0                                                         09/20/2016
 - Initial Release to Production

===========================================================================
v 1.0.1                                                         09/23/2016
 - ADDED impressionwise integration so that it can be toggled on and off.