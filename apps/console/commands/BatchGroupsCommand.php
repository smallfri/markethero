<?php

defined('MW_PATH')||exit('No direct script access allowed');

class BatchGroupsCommand extends CConsoleCommand
{

    public $verbose = 1;

    public function init()
    {

        parent::init();
        ini_set('max_execution_time', 3600);
        set_time_limit(3600);
    }

    public function actionIndex()
    {

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Starting the create-batches command...\n";
        }
        $result = $this->process();

        return $result;
    }

    protected function process()
    {

        if ($this->verbose)
        {
            echo "[".date("Y-m-d H:i:s")."] Creating chunks...\n";
        }

        $groups = $this->findGroupsToBatch();

        foreach ($groups AS $group)
        {
            $emails = $this->findGroupEmailsToBatch($group['group_email_id']);

            $chunks = array_chunk($emails, 200);
            foreach ($chunks as $chunk_of_rows)
            {

                $batch = new GroupBatch();
                $batch->group_email_id = $group['group_email_id'];
                $batch->status = 'pending-sending';
                $batch->date_added = new \DateTime;
                $batch->save(false);

                $criteria = new CDbCriteria;
                $criteria->condition = 'group_email_id= '.$group['group_email_id'].' AND group_batch_id IS NULL';
                $criteria->limit = 200;
                GroupEmail::model()
                    ->updateAll(['status' => 'pending-sending', 'group_batch_id' => $batch->primaryKey], $criteria
                    );

            }

        }

        return 0;
    }

    /**
     * @param $group
     * @return static[]
     */
    protected function findGroupEmailsToBatch($group_email_id)
    {

        $criteria = new CDbCriteria();
        $criteria->select = '*';
        $criteria->condition
            = '`status` = "pending-sending" AND `send_at` < NOW() AND log.email_id IS NULL AND group_email_id='.$group_email_id;
        $criteria->join = 'LEFT JOIN mw_group_email_log AS log ON log.email_id = t.email_id';
        $emails = GroupEmail::model()->findAll($criteria);
        return $emails;
    }

    protected function findGroupsToBatch()
    {

        $groups = Yii::app()->db->createCommand()
            ->select('geg.group_email_id, cl.threshold, gec.*')
            ->from('mw_group_email_groups geg')
            ->join('mw_group_email_compliance gec',
                'gec.group_email_id=geg.group_email_id')
            ->join('mw_compliance_levels cl', 'cl.compliance_level_id = gec.compliance_level_id')
            ->where('gec.compliance_status != "sent" AND geg.status = "pending-sending" OR geg.status = "processing"')
            ->group('geg.group_email_id')
            ->limit(25)
            ->queryAll();

        return $groups;
    }
}