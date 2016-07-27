<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailComplianceModel;
use App\Models\GroupEmailModel;

class ManageGroupController extends ApiController
{

    function __construct()
    {

    }

    public function approve($group_email_id)
    {

        $GroupEmailGroups = GroupEmailGroupsModel::find($group_email_id);
        $GroupEmailGroups->status = 'pending-sending';
        $GroupEmailGroups->save();

        $GroupEmailCompliance = GroupEmailComplianceModel::find($group_email_id);
        $GroupEmailCompliance->compliance_status = 'approved';
        $GroupEmailCompliance->last_updated = new \DateTime();
        $GroupEmailCompliance->save();

        return redirect('groups');

    }

    public function pause($group_email_id)
    {

        $GroupEmailGroups = GroupEmailGroupsModel::find($group_email_id);
        $GroupEmailGroups->status = 'paused';
        $GroupEmailGroups->save();

        $GroupEmailCompliance = GroupEmailComplianceModel::find($group_email_id);
        $GroupEmailCompliance->compliance_status = 'paused';
        $GroupEmailCompliance->last_updated = new \DateTime();
        $GroupEmailCompliance->save();

        return redirect('groups');

    }

    public function resume($group_email_id)
    {

        $GroupEmailGroups = GroupEmailGroupsModel::find($group_email_id);
        $GroupEmailGroups->status = 'pending-sending';
        $GroupEmailGroups->save();

        $GroupEmailCompliance = GroupEmailComplianceModel::find($group_email_id);
        $GroupEmailCompliance->compliance_status = 'approved';
        $GroupEmailCompliance->last_updated = new \DateTime();
        $GroupEmailCompliance->save();

        return redirect('groups');

    }

    public function setAsSent($group_email_id)
    {

        $GroupEmailGroups = GroupEmailGroupsModel::find($group_email_id);
        $GroupEmailGroups->status = 'sent';
        $GroupEmailGroups->save();

        $GroupEmailCompliance = GroupEmailComplianceModel::find($group_email_id);
        $GroupEmailCompliance->compliance_status = 'sent';
        $GroupEmailCompliance->last_updated = new \DateTime();
        $GroupEmailCompliance->save();

        GroupEmailModel::where('group_email_id', '=', $group_email_id)->update(['status' => 'sent']);

        return redirect('groups');

    }

}