<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 6/29/16
 * Time: 6:39 AM
 */

namespace App\Console\Commands;


use App\Logger;
use App\Models\BlacklistModel;
//use App\Models\BounceServer;
use App\Models\DeliveryServerModel;
//use App\Models\GroupControlsModel;
use App\Models\GroupEmailComplianceLevelsModel;
use App\Models\GroupEmailComplianceModel;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailLogModel;
use App\Models\GroupEmailModel;
use App\Helpers\Helpers;
use Carbon\Carbon;
use DB;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
//use phpseclib\Crypt\AES;
use Swift_Plugins_AntiFloodPlugin;


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

        $statuses = array(GroupEmailGroupsModel::STATUS_PENDING_SENDING);

        if ($this->groups_type!==null)
        {
            $this->groups_type = null;
        }

        $groups = GroupEmailGroupsModel::whereIn('status', $statuses)->get();

        $this->stdout(sprintf('Found %s groups',count($groups)));

        if(empty($groups))
        {
            $this->stdout('Found no Groups matching the criteria');
            exit;
        }

        foreach($groups AS $group)
        {
            $maxDateAdded = $this->findMaxDateAdded($group);

            $this->stdout(sprintf('Found Max Date Added to be %s.',$maxDateAdded));

            $now = Carbon::now(-.25);

            if($maxDateAdded <= $now)
            {
                $this->stdout('Updating group status to sent.');
                $this->updateGroupStatus($group['group_email_id'], GroupEmailGroupsModel::STATUS_SENT);
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

            $emails = GroupEmailModel::where('status', '=', 'pending-sending')
                ->where('group_email_id', '=', $group['group_email_id'])
                ->count();

            return $emails;

        }

    protected function findMaxDateAdded($group)
    {

        $max = DB::select(DB::raw('SELECT MAX(date_added) as date_added FROM mw_group_email WHERE group_email_id = '.$group->group_email_id));

        return $max[0]->date_added;

    }

    protected function updateGroupStatus($id, $status)
    {

        GroupEmailGroupsModel::where('group_email_id', $id)
            ->update(['status' => $status]);

        return;
    }



}