<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;

use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;
use DB;
use PDO;
use Illuminate\Console\Command;


/**
 * Class SendGroupsCommand
 * @package App\Console\Commands
 */
class UpdateGroupStatusCommand extends Command
{

    protected $_cipher;

    protected $_plainTextPassword;

    protected $signature = 'update-group-status';

    protected $description = 'Updates the status of groups.';

    protected $_group;

    // flag
    protected $_restoreStates = true;

    // flag
    protected $_improperShutDown = false;

    // global command arguments

    // what type of campaigns this command is sending
    public $groups_type;

    // how many campaigns to process at once
    public $groups_limit = 0;

    // from where to start
    public $groups_offset = 0;

    public $options;

    public $verbose = 1;

    public function init()
    {

    }

    public function handle()
    {

        $result = $this->process();

        return $result;
    }

    protected function process()
    {

        $statuses = array(GroupEmailGroupsModel::STATUS_SENT);

        if ($this->groups_type!==null)
        {
            $this->groups_type = null;
        }

        $date = new \DateTime;
               $date->modify('-1 Day');
               $formatted_date = $date->format('Y-m-d');


        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $groups = GroupEmailGroupsModel::whereIn('status', $statuses)
            ->where('date_added', '>=', $formatted_date )
            ->get();
        DB::disconnect('mysql');

        $this->stdout(sprintf('Found %s groups', count($groups)));

        if (empty($groups))
        {
            $this->stdout('Found no Groups matching the criteria');
            exit;
        }

        foreach ($groups AS $group)
        {
            $unsent = $this->findEmailsUnsent($group);

            $this->stdout('Found unsent emails '.$unsent);

            if ($unsent>0)
            {
                $this->stdout('Updating ');

                $this->updateGroupStatus($group['group_email_id'], 'pending-sending');
            }

        }
    }

    protected function stdout($message, $timer = true, $separator = "\n")
    {

        if (!$this->verbose)
        {
            return;
        }

        $out = '';
        if ($timer)
        {
            $out .= '['.date('Y-m-d H:i:s').'] - ';
        }
        $out .= $message;
        if ($separator)
        {
            $out .= $separator;
        }

        echo $out;
    }

    protected function findEmailsUnsent($group)
    {


        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $emails = GroupEmailModel::where('status', '=', 'pending-sending')
            ->where('group_email_id', '=', $group['group_email_id'])
            ->count();
        DB::disconnect('mysql');

        return $emails;

    }

    protected function findMaxDateAdded($group)
    {

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $max
            = DB::select(DB::raw('SELECT MAX(date_added) as date_added FROM mw_group_email WHERE group_email_id = '.$group->group_email_id));
        DB::disconnect('mysql');

        return $max[0]->date_added;

    }

    protected function updateGroupStatus($id, $status)
    {

        DB::reconnect('mysql');
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        GroupEmailGroupsModel::where('group_email_id', $id)
            ->update(['status' => $status]);
        DB::disconnect('mysql');

        return;
    }

}