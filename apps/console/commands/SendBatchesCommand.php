<?php

defined('MW_PATH') || exit('No direct script access allowed');

class SendBatchesCommand extends CConsoleCommand
{
    // current batches
    protected $_batches;
    
    // flag 
    protected $_restoreStates = true;
    
    // flag
    protected $_improperShutDown = false;

    // global command arguments
    
    // what type of batches this command is sending
    public $batches_type;
    
    // how many batches to process at once
    public $batches_limit = 0;
    
    // from where to start
    public $batches_offset = 0;
    
    // whether this should be verbose and output to console
    public $verbose = 0;

    public $error_level = 0;

    public function init()
    {
        parent::init();

        ini_set('max_execution_time', 3600);
        set_time_limit(3600);
    }



    public function actionIndex()
    {
        if ($this->verbose) {
                    echo "[".date("Y-m-d H:i:s")."] Starting the send-batch command...\n";
                }


        $result = $this->process();

        return $result;
    }

    protected function process()
    {
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Starting the send-batch command...\n";
        }

        $limit    = 100;

        if ((int)$this->batches_limit > 0) {
            $limit = (int)$this->batches_limit;
        }

         $batches = GroupBatch::model()->findAll([
             'condition' => 'status = "pending-sending"',
         ]);
        
        if (empty($batches)) {
            if ($this->verbose) {
                echo "[".date("Y-m-d H:i:s")."] No batches found for processing!\n";
            }
            return 0;
        }
        
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Found " . count($batches) . " batches for processing, starting...\n";
        }
        
        $batchIds = array();

        foreach ($batches as $batch) {
            $batchIds[] = $batch['group_batch_id'];
        }
        
        foreach ($batchIds as $batchId) {
            $this->_batches = GroupBatch::model()->getByKey((int)$batchId);
           
            $this->_batches->attachBehavior('sender', array(
                'class'             => 'console.components.behaviors.GroupBatchEmailSenderBehavior',
                'verbose'        => $this->verbose,
                'error_level'    => $this->error_level,
            ));
            
            if ($this->verbose) {
                $timeStart = microtime(true);
                echo "[".date("Y-m-d H:i:s")."] Starting processing the batches...\n";
            }
        
            $this->_batches->sender->sendbatches();
            
            if ($this->verbose) {
                echo "[".date("Y-m-d H:i:s")."] Finished processing the batches, took " . round(microtime(true) - $timeStart, 3) . " seconds!\n";
            }
        }
        
        $this->_batches = null;
        
        if ($this->verbose) {
            echo "[".date("Y-m-d H:i:s")."] Finished the send-batches command!\n";
        }
        
        return 0;
    }
}