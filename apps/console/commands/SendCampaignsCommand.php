<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * SendCampaignsCommand
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 * 
 */
 
class SendCampaignsCommand extends CConsoleCommand 
{
    // current campaign
    protected $_campaign;
    
    // flag 
    protected $_restoreStates = true;
    
    // flag
    protected $_improperShutDown = false;

    // global command arguments
    
    // what type of campaigns this command is sending
    public $campaigns_type;
    
    // how many campaigns to process at once
    public $campaigns_limit = 0;
    
    // from where to start
    public $campaigns_offset = 0;
    
    // whether this should be verbose and output to console
    public $verbose = 0;
    
    public function init()
    {
        parent::init();
        
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
        
        // if more than 1 hour then something is def. wrong?
        ini_set('max_execution_time', 3600);
        set_time_limit(3600);
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

        // called as a callback from register_shutdown_function
        // must pass only if improper shutdown in this case
        if ($event === null && !$this->_improperShutDown) {
            return;
        }

        if (!empty($this->_campaign) && $this->_campaign instanceof Campaign) {
            if ($this->_campaign->isProcessing) {
                $this->_campaign->saveStatus(Campaign::STATUS_SENDING);
            }
        }
    }
    
    public function actionIndex()
    {
        // added in 1.3.4.7
        Yii::app()->hooks->doAction('console_command_send_campaigns_before_process', $this);
        
        $result = $this->process();
        
        // added in 1.3.4.7
        Yii::app()->hooks->doAction('console_command_send_campaigns_after_process', $this);
        
        return $result;
    }
    
    protected function process() 
    {
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Starting the send-campaigns command...\n";
        }

        $options  = Yii::app()->options;
        $statuses = array(Campaign::STATUS_SENDING, Campaign::STATUS_PENDING_SENDING);
        $types    = array(Campaign::TYPE_REGULAR, Campaign::TYPE_AUTORESPONDER);
        $limit    = (int)$options->get('system.cron.send_campaigns.campaigns_at_once', 10);
        
        if ($this->campaigns_type !== null && !in_array($this->campaigns_type, $types)) {
            $this->campaigns_type = null;
        }
        
        if ((int)$this->campaigns_limit > 0) {
            $limit = (int)$this->campaigns_limit;
        }
        
        $criteria = new CDbCriteria();
        $criteria->select = 't.campaign_id';
        $criteria->addInCondition('t.status', $statuses);
        $criteria->addCondition('t.send_at <= NOW()');
        if (!empty($this->campaigns_type)) {
            $criteria->addCondition('t.type = :type');
            $criteria->params[':type'] = $this->campaigns_type;
        }
        $criteria->order  = 't.campaign_id ASC';
        $criteria->limit  = $limit;
        $criteria->offset = (int)$this->campaigns_offset;
        
        // offer a chance to alter this criteria.
        $criteria = Yii::app()->hooks->applyFilters('console_send_campaigns_command_find_campaigns_criteria', $criteria, $this);
        
        // and find all campaigns matching the criteria
        $campaigns = Campaign::model()->findAll($criteria);
        
        if (empty($campaigns)) {
            if ($this->verbose) {
                echo "[".date("Y-m-d H:i:s")."] No campaign found for processing!\n";
            }
            return 0;
        }
        
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Found " . count($campaigns) . " campaigns for processing, starting...\n";
        }
        
        $campaignIds = array();
        foreach ($campaigns as $campaign) {
            $campaignIds[] = $campaign->campaign_id;
        }
        
        if ($memoryLimit = $options->get('system.cron.send_campaigns.memory_limit')) {
            ini_set('memory_limit', $memoryLimit);
        }
        
        foreach ($campaignIds as $campaignId) {
            $this->_campaign = Campaign::model()->findByPk((int)$campaignId);

            if (empty($this->_campaign) || !in_array($this->_campaign->status, $statuses)) {
                $this->_campaign = null;
                continue;
            }

            $this->_campaign->attachBehavior('sender', array(
                'class'             => 'console.components.behaviors.CampaignSenderBehavior',
                'campaigns_type'    => $this->campaigns_type,
                'campaigns_limit'   => (int)$this->campaigns_limit,
                'campaigns_offset'  => (int)$this->campaigns_offset,
                'verbose'           => (int)$this->verbose,
            ));
            
            if ($this->verbose) {
                $timeStart = microtime(true);
                echo "[".date("Y-m-d H:i:s")."] Starting processing the campaign...\n";
            }
        
            $this->_campaign->sender->sendCampaign();
            
            if ($this->verbose) {
                echo "[".date("Y-m-d H:i:s")."] Finished processing the campaign, took " . round(microtime(true) - $timeStart, 3) . " seconds!\n";
            }
        }
        
        $this->_campaign = null;
        
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Finished the send-campaigns command!\n";
        }
        
        return 0;
    }
}