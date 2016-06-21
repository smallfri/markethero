<?php defined('MW_PATH')||exit('No direct script access allowed');

/**
 * Console application main configuration file
 *
 * This file should not be altered in any way!
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

return array(
    'basePath' => Yii::getPathOfAlias('console'),

    'preload' => array(
        'consoleSystemInit'
    ),

    'import' => array(
        'console.components.*',
        'console.components.db.*',
        'console.components.db.ar.*',
    ),

    'commandMap' => array(
        'migrate' => array(
            'class' => 'system.cli.commands.MigrateCommand',
            'migrationPath' => 'console.migrations',
            'migrationTable' => '{{migration}}',
            'connectionID' => 'db',
        ),
        'hello' => array(
            'class' => 'console.commands.HelloCommand'
        ),
        'send-campaigns' => array(
            'class' => 'console.commands.SendCampaignsCommand'
        ),
        'bounce-handler' => array(
            'class' => 'console.commands.BounceHandlerCommand'
        ),
        'group-bounce-handler' => array(
            'class' => 'console.commands.GroupsBounceHandlerCommand'
        ),
        'process-delivery-and-bounce-log' => array(
            'class' => 'console.commands.ProcessDeliveryAndBounceLogCommand'
        ),
        // this command is deprecated since 1.3.3.1 in favor of daily command
        'process-subscribers' => array(
            'class' => 'console.commands.ProcessSubscribersCommand'
        ),
        'option' => array(
            'class' => 'console.commands.OptionCommand'
        ),
        'feedback-loop-handler' => array(
            'class' => 'console.commands.FeedbackLoopHandlerCommand'
        ),
        'send-transactional-emails' => array(
            'class' => 'console.commands.SendTransactionalEmailsCommand'
        ),
        'send-group-emails' => array(
            'class' => 'console.commands.NewSendGroupsCommand'
        ),
        'send-unsents-group-emails' => array(
            'class' => 'console.commands.ProcessUnsetGroupEmailsCommand'
        ),
        'daily' => array(
            'class' => 'console.commands.DailyCommand'
        ),
        'update' => array(
            'class' => 'console.commands.UpdateCommand'
        ),
        'archive-campaigns-delivery-logs' => array(
            'class' => 'console.commands.ArchiveCampaignsDeliveryLogsCommand'
        ),
        'queue' => array(
            'class' => 'console.commands.RedisQueueCommand'
        ),
        'list-import' => array(
            'class' => 'console.commands.ListImportCommand'
        ),
        'list-export' => array(
            'class' => 'console.commands.ListExportCommand'
        ),
        'mailerq-handler-daemon' => array(
            'class' => 'console.commands.MailerqHandlerDaemon'
        ),
        'compliance' => array(
            'class' => 'console.commands.GroupsComplianceHandlerCommand'
        ),
        'compliance-average' => array(
            'class' => 'console.commands.GroupsComplianceScoreCommand'
        ),
        'create-batches' => array(
            'class' => 'console.commands.BatchGroupsCommand'
        ),
        'send-batches' => array(
                    'class' => 'console.commands.SendBatchesCommand'
                ),

    ),

    'components' => array(
        'consoleSystemInit' => array(
            'class' => 'console.components.init.ConsoleSystemInit',
        ),
    ),
);
