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

class GroupEmailsGroupController extends ApiController
{

    function __construct()
    {

        $this->middleware('auth.basic');

    }

    public function store()
    {


        $data = json_decode(file_get_contents('php://input'), true);

        Logger::addProgress('(Transaction Email Group) Create '.print_r($data, true),
            '(Transaction Email Group) Create');

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
            Logger::addProgress('(Transaction Email Group) Missing Fields '.print_r($missing_fields, true),
                '(Transaction Email Group) Missing Fields');

            return $this->respondWithError($missing_fields);
        }

        $list_uid = uniqid();

        $GroupEmailGroups = new GroupEmailGroupsModel();
        $GroupEmailGroups->group_email_uid = $list_uid;
        $GroupEmailGroups->customer_id = $data['customer_id'];
        $GroupEmailGroups->status = 'pending-sending';
        $GroupEmailGroups->save();

        $GroupEmailCompliance = new GroupEmailComplianceModel();
        $GroupEmailCompliance->group_email_id
            = $GroupEmailGroups->group_email_id;

        $GroupEmailCompliance->compliance_status = 'first-review';
        $GroupEmailCompliance->compliance_level_type_id = 2;
        $GroupEmailCompliance->date_added = new \DateTime();
        $GroupEmailCompliance->last_updated = new \DateTime();
        $GroupEmailCompliance->save();

        if ($GroupEmailGroups->group_email_id<1)
        {
            return $this->respondWithError('There was an error, the group was not created.');
        }

        Logger::addProgress('(Transaction Email Group) Created '.print_r($GroupEmailGroups, true),
                        '(Transaction Email Group) Created');

        return $this->respond(['group' => $GroupEmailGroups->group_email_id]);

    }

    public function approve($group_email_id)
    {
        exit($group_email_id);
    }

}