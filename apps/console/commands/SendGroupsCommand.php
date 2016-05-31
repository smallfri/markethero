<?php

defined('MW_PATH') || exit('No direct script access allowed');

class SendGroupsCommand extends CConsoleCommand
{
    // current groups
    protected $_groups;
    
    // flag 
    protected $_restoreStates = true;
    
    // flag
    protected $_improperShutDown = false;

    // global command arguments
    
    // what type of groups this command is sending
    public $groups_type;
    
    // how many groups to process at once
    public $groups_limit = 0;
    
    // from where to start
    public $groups_offset = 0;
    
    // whether this should be verbose and output to console
    public $verbose = 0;

    public $error_level = 0;

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

        if (!empty($this->_groups) && $this->_groups instanceof GroupEmail) {
            if ($this->_groups->isProcessing) {
                $this->_groups->saveStatus(GroupEmail::STATUS_SENDING);
            }
        }
    }

    public function actionIndex()
    {
        // added in 1.3.4.7
        Yii::app()->hooks->doAction('console_command_send_groups_before_process', $this);

        $result = $this->process();

        // added in 1.3.4.7
        Yii::app()->hooks->doAction('console_command_send_groups_after_process', $this);

        return $result;
    }

    protected function process()
    {
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Starting the send-groups command...\n";
        }

        $options  = Yii::app()->options;
        $statuses = array('sending', 'pending-sending');
        $types    = array('regular', 'autoresponder');
        $limit    = 100;
        
        if ($this->groups_type !== null && !in_array($this->groups_type, $types)) {
            $this->groups_type = null;
        }
        
        if ((int)$this->groups_limit > 0) {
            $limit = (int)$this->groups_limit;
        }

//        $groups = GroupEmail::model()->findAll(array(
//            'condition' => '`status` = "pending-sending" AND `send_at` < NOW() AND `retries` < `max_retries`',
//            'order' => 'email_id ASC',
//            'limit' => $limit
//        ));


        $groups = Yii::app()->db->createCommand()
                   ->select('geg.group_email_id, cl.threshold, gec.*')
                   ->from('mw_group_email_groups geg')
                   ->join('mw_group_email_compliance gec',
                       'gec.group_email_id=geg.group_email_id')
                   ->join('mw_compliance_levels cl', 'cl.id = gec.compliance_level_type_id')
                   ->where('gec.compliance_status != "sent" AND geg.status = "pending-sending" OR geg.status = "processing"')
                   ->group('geg.group_email_id')
                   ->limit($limit)
                   ->queryAll();
        
        if (empty($groups)) {
            if ($this->verbose) {
                echo "[".date("Y-m-d H:i:s")."] No groups found for processing!\n";
            }
            return 0;
        }
        
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Found " . count($groups) . " groups for processing, starting...\n";
        }
        
        $groupsIds = array();

        foreach ($groups as $group) {
            $groupsIds[] = $group['group_email_id'];
        }

//        if ($memoryLimit = $options->get('system.cron.send_groups.memory_limit')) {
//            ini_set('memory_limit', $memoryLimit);
//        }
        foreach ($groupsIds as $groupsId) {
            $this->_groups = Group::model()->getByKey((int)$groupsId);
//            $this->_groups = GroupEmail::model()->findAll(array(
//                           'condition' => '`status` = "pending-sending" AND `send_at` < NOW() AND `retries` < `max_retries`',
//                           'order' => 'email_id ASC',
//                           'limit' => $limit
//                       ));
//
//            print_r($this->_groups);

//            if (empty($this->_groups) || !in_array($this->_groups->status, $statuses)) {
//                $this->_groups = null;
//                continue;
//            }

            $this->_groups->attachBehavior('sender', array(
                'class'             => 'console.components.behaviors.GroupEmailSenderBehavior',
                'groups_type'    => $this->groups_type,
                'groups_limit'   => (int)$this->groups_limit,
                'groups_offset'  => (int)$this->groups_offset,
                'verbose'        => $this->verbose,
                'error_level'    => $this->error_level,
            ));
            
            if ($this->verbose) {
                $timeStart = microtime(true);
                echo "[".date("Y-m-d H:i:s")."] Starting processing the groups...\n";
            }
        
            $this->_groups->sender->sendgroups();
            
            if ($this->verbose) {
                echo "[".date("Y-m-d H:i:s")."] Finished processing the groups, took " . round(microtime(true) - $timeStart, 3) . " seconds!\n";
            }
        }
        
        $this->_groups = null;
        
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Finished the send-groups command!\n";
        }
        
        return 0;
    }
}