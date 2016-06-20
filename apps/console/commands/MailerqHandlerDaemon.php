<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * MailerqHandlerDaemon
 *
 * This is a daemon NOT a regular cron job command.
 * Please make sure it is not added in cron jobs nor it runs in more instances.
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class MailerqHandlerDaemon extends CConsoleCommand
{
    public $verbose = 0;

    public function actionIndex()
    {
        if (!MW_COMPOSER_SUPPORT || !version_compare(PHP_VERSION, '5.3.1', '>=')) {
            $this->stdout('You need PHP >= 5.3.1!');
            return 1;
        }

        $funcs = array('pcntl_fork', 'pcntl_waitpid');
        foreach ($funcs as $func) {
            if (!CommonHelper::functionExists($func)) {
                $this->stdout(sprintf('You need to have the "%s" function (part of pcntl extension) enabled!', $func));
                return 1;
            }
        }

        $loadedServers = array();
        while (true) {

            $servers = DeliveryServer::model()->findAll(array(
                'select'    => 'server_id',
                'condition' => 'type = "mailerq-web-api"',
            ));

            foreach ($servers as $index => $server) {
                if (isset($loadedServers[$server->server_id])) {
                    unset($servers[$index]);
                } else {
                    $loadedServers[$server->server_id] = true;
                }
            }

            if (empty($servers)) {
                sleep(60);
                continue;
            }

            // make sure we close the database connection
            Yii::app()->getDb()->setActive(false);

            $childs = array();
            foreach ($servers as $server) {

                $this->stdout(sprintf('Forking a new process for server id %d!', $server->server_id));

                $pid = pcntl_fork();
                if($pid == -1) {
                    continue;
                }

                // Parent
                if ($pid) {
                    $childs[] = $pid;
                }

                // Child
                if (!$pid) {
                    $this->_handleServer($server->server_id);
                    exit;
                }
            }

            while (count($childs) > 0) {
                foreach ($childs as $key => $pid) {
                    $res = pcntl_waitpid($pid, $status, WNOHANG);
                    if($res == -1 || $res > 0) {
                        unset($childs[$key]);
                    }
                }
                sleep(1);
            }

            sleep(10);
        }
    }

    public function _handleServer($serverId)
    {
        // make sure we open the database connection
        Yii::app()->getDb()->setActive(true);
        $server = DeliveryServerMailerqWebApi::model()->findByPk($serverId);
        if (empty($server)) {
            return;
        }

        $this->stdout(sprintf('Processing server id %d!', $server->server_id));

        try {
            $channel = $server->getConnection()->channel();
            $channel->queue_declare('results', false, true, false, false);
            $channel->basic_consume('results', '', false, true, false, false, array($this, '_process'));
            while(count($channel->callbacks)) {
                $channel->wait();
            }
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            $this->stdout($e->getMessage());
        }
    }

    public function _process($msg)
    {
        $results = json_decode($msg->body);
        if (empty($results)) {
            return;
        }

        $campaigns = $subscribers = array();

        foreach ($results as $result) {

            $this->stdout(print_r($result, true));

            if (empty($result->results) || !isset($result->campaign_uid, $result->subscriber_uid)) {
                continue;
            }

            $res = end($result->results);
            if ($res->type != 'error' || empty($res->fatal)) {
                continue;
            }

            if (!array_key_exists($result->campaign_uid, $campaigns)) {
                $campaigns[$result->campaign_uid] = Campaign::model()->findByAttributes(array(
                    'campaign_uid' => $result->campaign_uid,
                ));
            }
            if (empty($campaigns[$result->campaign_uid])) {
                continue;
            }

            if (!array_key_exists($result->subscriber_uid, $subscribers)) {
                $subscribers[$result->subscriber_uid] = ListSubscriber::model()->findByAttributes(array(
                    'campaign_uid' => $result->subscriber_uid,
                ));
            }
            if (empty($subscribers[$result->subscriber_uid])) {
                continue;
            }

            $campaign   = $campaigns[$result->campaign_uid];
            $subscriber = $subscribers[$result->subscriber_uid];

            $bounceLog = CampaignBounceLog::model()->findByAttributes(array(
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ));

            if (!empty($bounceLog)) {
                continue;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = $campaign->campaign_id;
            $bounceLog->subscriber_id = $subscriber->subscriber_id;
            $bounceLog->message       = $res->description;
            $bounceLog->bounce_type   = CampaignBounceLog::BOUNCE_HARD;
            $bounceLog->save();

            $subscriber->addToBlacklist($bounceLog->message);
        }

        unset($campaigns, $subscribers, $results);
    }

    protected function stdout($message, $timer = true, $separator = "\n")
    {
        if (!$this->verbose) {
            return;
        }

        $out = '';
        if ($timer) {
            $out .= '[' . date('Y-m-d H:i:s') . '] - ';
        }
        $out .= $message;
        if ($separator) {
            $out .= $separator;
        }

        echo $out;
    }
}
