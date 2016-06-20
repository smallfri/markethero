<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * HelloCommand
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
 
class HelloCommand extends CConsoleCommand 
{
    public function actionIndex() 
    {
        echo 'Hello World!' . "\n";
    }
}