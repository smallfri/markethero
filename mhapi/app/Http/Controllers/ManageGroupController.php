<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Logger;
use App\GroupEmailGroupsModel;
use App\GroupEmailComplianceModel;
use Faker\Provider\zh_TW\DateTime;

class ManageGroupController extends ApiController
{

    function __construct()
    {


    }

    public function approve($group_email_id)
    {
        $results = GroupEmailComplianceModel::where('group_email_id', $group_email_id)->update(['compliance_status'=>'approved']);

        if($results)
        {

        }

        return redirect('groups');

    }

}