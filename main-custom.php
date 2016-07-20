<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * Custom application main configuration file
 * 
 * This file can be used to overload config/components/etc
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2014 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.1
 */

Define('ENV','PROD');

if (ENV == 'DEV')
{
    return array(

            // application components
            'components' => array(
                'db' => array(
                    'connectionString'  => 'mysql:host=localhost;dbname=emailnet_main1',
                    'username'          => 'smallfri',
                    'password'          => 'jack1999',
                    'tablePrefix'       => 'mw_',
                ),
            ),
        );
}
else
{
    return array(

        // application components
        'components' => array(
            'db' => array(
                'connectionString'  => 'mysql:host=staging.db.markethero.io;dbname=market_hero',
                'username'          => 'mh-mailer',
                'password'          => 'xp5+htZk',
                'tablePrefix'       => 'mw_',
            ),
        ),
    );
}
