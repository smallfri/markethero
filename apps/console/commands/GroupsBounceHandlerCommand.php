<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * BounceHandlerCommand
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

class GroupsBounceHandlerCommand extends CConsoleCommand
{
    // lock name
    protected $_lockName;

    // flag
    protected $_restoreStates = true;

    // flag
    protected $_improperShutDown = false;

    // current server
    protected $_server;

    public function init()
    {
        parent::init();

        // set the lock name
        $this->_lockName = md5(__FILE__);

        // this will catch exit signals and restore states
        if (CommonHelper::functionExists('pcntl_signal')) {
            declare(ticks = 1);
            pcntl_signal(SIGINT,  array($this, '_handleExternalSignal'));
            pcntl_signal(SIGTERM, array($this, '_handleExternalSignal'));
            pcntl_signal(SIGHUP,  array($this, '_handleExternalSignal'));
        }

        register_shutdown_function(array($this, '_restoreStates'));
        Yii::app()->attachEventHandler('onError', array($this, '_restoreStates'));
        Yii::app()->attachEventHandler('onException', array($this, '_restoreStates'));
    }

    public function _handleExternalSignal($signalNumber)
    {
        // this will trigger all the handlers attached via register_shutdown_function
        $this->_improperShutDown = true;
        exit;
    }

    public function _restoreStates($event = null)
    {
        if (!$this->_restoreStates) {
            return;
        }
        $this->_restoreStates = false;

        // remove the lock
        Yii::app()->mutex->release($this->_lockName);

        // called as a callback from register_shutdown_function
        // must pass only if improper shutdown in this case
        if ($event === null && !$this->_improperShutDown) {
            return;
        }

        if (!empty($this->_server) && $this->_server instanceof BounceServer) {
            if ($this->_server->status == BounceServer::STATUS_CRON_RUNNING) {
                $this->_server->status = BounceServer::STATUS_ACTIVE;
                $this->_server->saveStatus();
            }
        }
    }

    public function actionIndex()
    {
        // because some cli are not compiled same way with the web module.
        if (!CommonHelper::functionExists('imap_open')) {
            Yii::log(Yii::t('servers', 'The PHP CLI binary is missing the IMAP extension!'), CLogger::LEVEL_ERROR);
            return 1;
        }

        if (!Yii::app()->mutex->acquire($this->_lockName, 5)) {
            return 0;
        }

        Yii::import('common.vendors.BounceHandler.*');
        $options = Yii::app()->options;

        if ($memoryLimit =  $options->get('system.cron.process_bounce_servers.memory_limit')) {
            ini_set('memory_limit', $memoryLimit);
        }

        // added in 1.3.4.7
        Yii::app()->hooks->doAction('console_command_bounce_handler_before_process', $this);

        $this->process(0, (int)$options->get('system.cron.process_bounce_servers.servers_at_once', 10));

        // added in 1.3.4.7
        Yii::app()->hooks->doAction('console_command_bounce_handler_after_process', $this);

        Yii::app()->mutex->release($this->_lockName);

        return 0;
    }

    protected function process($offset = 0, $limit = 10)
    {
        $servers = BounceServer::model()->findAll(array(
            'select'    => 't.server_id',
            'condition' => 't.status = :status AND t.customer_id = 1',
            'params'    => array(':status' => BounceServer::STATUS_ACTIVE),
            'limit'     => (int)$limit,
            'offset'    => (int)$offset,
        ));

        if (empty($servers)) {
            return $this;
        }

//        print_r($servers);

        $serversIds = array();
        foreach ($servers as $server) {
            $serversIds[] = $server->server_id;
        }
        unset($servers, $server);

        $options      = Yii::app()->options->resetLoaded();
        $processLimit = (int)$options->get('system.cron.process_bounce_servers.emails_at_once', 500);

        try {

            foreach ($serversIds as $serverId) {
                $this->_server = BounceServer::model()->findByPk((int)$serverId);
                if (empty($this->_server) || $this->_server->status != BounceServer::STATUS_ACTIVE) {
                    $this->_server = null;
                    continue;
                }
                $this->_server->status = BounceServer::STATUS_CRON_RUNNING;
                $this->_server->saveStatus();

                // close the db connection because it will time out!
                Yii::app()->getDb()->setActive(false);

                $headerPrefix = 'X-Mw-';
                $headerPrefixUp = strtoupper($headerPrefix);

                $bounceHandler = new BounceHandler($this->_server->getConnectionString(), $this->_server->username, $this->_server->password, array(
                    'deleteMessages'    => true,
                    'deleteAllMessages' => $this->_server->getDeleteAllMessages(),
                    'processLimit'      => $processLimit,
                    'searchCharset'     => $this->_server->getSearchCharset(),
                    'imapOpenParams'    => $this->_server->getImapOpenParams(),
                    'requiredHeaders'   => array(
                        $headerPrefix . 'Group-id',
                        $headerPrefix . 'Customer-Id'
                    ),
                ));

                $results = $bounceHandler->getResults();

                // re-open the db connection
                Yii::app()->getDb()->setActive(true);

                if (empty($results)) {
                    $this->_server = BounceServer::model()->findByPk((int)$this->_server->server_id);
                    if (empty($this->_server)) {
                        continue;
                    }
                    if ($this->_server->status == BounceServer::STATUS_CRON_RUNNING) {
                        $this->_server->status = BounceServer::STATUS_ACTIVE;
                        $this->_server->saveStatus();
                    }
                    continue;
                }
                foreach ($results as $result) {
                    foreach ($result['originalEmailHeadersArray'] as $key => $value) {
                        unset($result['originalEmailHeadersArray'][$key]);
                        $result['originalEmailHeadersArray'][strtoupper($key)] = $value;
                    }


//                    print_r($result['originalEmailHeadersArray']);

                    if (!isset(
                        $result['originalEmailHeadersArray'][$headerPrefixUp . 'GROUP-ID'],
                        $result['originalEmailHeadersArray'][$headerPrefixUp . 'CUSTOMER-ID'],
                        $result['originalEmailHeadersArray']['TO']
                        ))
                    {
                        continue;
                    }

                    $groupid    = trim($result['originalEmailHeadersArray'][$headerPrefixUp . 'GROUP-ID']);
                    $customerId = trim($result['originalEmailHeadersArray'][$headerPrefixUp . 'CUSTOMER-ID']);
                    $email = trim($result['originalEmailHeadersArray']['TO']);

//todo add logic here to check for a bounce and then blacklist the email address.

                        $bounceLog = new GroupEmailBounceLog();
                        $bounceLog->group_id       = $groupid;
                        $bounceLog->customer_id     = $customerId;
                        $bounceLog->email           = $email;
                        $bounceLog->message         = trim($result['originalEmailHeadersArray']['DIAGNOSTIC-CODE']);
                        $bounceLog->bounce_type     = $result['bounceType'] == BounceHandler::BOUNCE_HARD ? CampaignBounceLog::BOUNCE_HARD : CampaignBounceLog::BOUNCE_SOFT;
                        $bounceLog->save();

                    echo 'Saved';

                }

                $this->_server = BounceServer::model()->findByPk((int)$this->_server->server_id);
                if (empty($this->_server)) {
                    continue;
                }

                if ($this->_server->status == BounceServer::STATUS_CRON_RUNNING) {
                    $this->_server->status = BounceServer::STATUS_ACTIVE;
                    $this->_server->saveStatus();
                }

                // close the db connection, save some resources...
                Yii::app()->getDb()->setActive(false);

                // sleep
                sleep((int)$options->get('system.cron.process_bounce_servers.pause', 5));

                // open db connection
                Yii::app()->getDb()->setActive(true);
            }
        } catch (Exception $e) {
            if (!empty($this->_server)) {
                Yii::app()->getDb()->setActive(true);
                $this->_server = BounceServer::model()->findByPk((int)$this->_server->server_id);
                if (!empty($this->_server) && $this->_server->status == BounceServer::STATUS_CRON_RUNNING) {
                    $this->_server->status = BounceServer::STATUS_ACTIVE;
                    $this->_server->saveStatus();
                }
            }
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        $this->_server = null;

        return $this->process($offset + (int)$options->get('system.cron.process_bounce_servers.servers_at_once', 10), $limit);
    }
}