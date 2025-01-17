<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Logger;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailComplianceModel;
use Illuminate\Support\Facades\Mail;

class GroupEmailsGroupController extends ApiController
{

    function __construct()
    {

        $this->middleware('auth.basic');

    }

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        Logger::addProgress('(Group) Create '.print_r($data, true),
            '(Group) Create');

        Logger::addProgress('(Group) Server Info '.print_r($_SERVER, true),
            '(Group) Server Info');

        if (empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        $expected_input = [
            'customer_id'
        ];

        $missing_fields = array();

        foreach ($expected_input AS $input)
        {
            if (!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if (!empty($missing_fields))
        {
            Logger::addProgress('(Group) Missing Fields '.print_r($missing_fields, true),
                '(Group) Missing Fields');

            return $this->respondWithError($missing_fields);
        }

        $list_uid = uniqid();

        $GroupEmailGroups = new GroupEmailGroupsModel();
        $GroupEmailGroups->group_email_uid = $list_uid;
        $GroupEmailGroups->customer_id = $data['customer_id'];
        if(isset($data['leads_count']))
        {
            $GroupEmailGroups->leads_count = $data['leads_count'];
        }
        $GroupEmailGroups->status = 'pending-sending';
        $GroupEmailGroups->date_added = new \DateTime();
        $GroupEmailGroups->save();

//        try{
//            $GroupEmailCompliance = new GroupEmailComplianceModel();
//                   $GroupEmailCompliance->group_email_id
//                       = $GroupEmailGroups->group_email_id;
//
//                   $GroupEmailCompliance->compliance_status = 'in-review';
//                   $GroupEmailCompliance->compliance_level_type_id = 2;
//                   $GroupEmailCompliance->date_added = new \DateTime();
//                   $GroupEmailCompliance->last_updated = new \DateTime();
//                   $GroupEmailCompliance->save();
//        }catch (\Exception $e)
//        {
//            print_r($e);
//        }



        if ($GroupEmailGroups->group_email_id<1)
        {
            return $this->respondWithError('There was an error, the group was not created.');
        }

        Logger::addProgress('(Group) Created '.print_r($GroupEmailGroups, true),
            '(Group) Created');

        //$this->sendMail($GroupEmailGroups);


        return $this->respond(['group' => $GroupEmailGroups->group_email_id]);

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

    }

    public function sendMail($group)
    {

        \SMS::send('New group created with id '.$group->group_email_id, null, function($sms) {
            $sms->to('8436552621', 'verizonwireless');
            $sms->to('4433665784', 'verizonwireless');
        });

    }

}