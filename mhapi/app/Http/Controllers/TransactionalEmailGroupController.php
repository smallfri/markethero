<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Logger;
use App\TransactionalEmailGroupModel;
use App\TransactionalEmailComplianceModel;
use Faker\Provider\zh_TW\DateTime;

class TransactionalEmailGroupController extends ApiController
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
            Logger::addError('(Transaction Email Group) Missing Fields '.print_r($missing_fields, true),
                '(Transaction Email Group) Missing Fields');

            return $this->respondWithError($missing_fields);
        }

        $list_uid = uniqid();

        $TransactionalEmailGroup = new TransactionalEmailGroupModel();
        $TransactionalEmailGroup->transactional_email_group_uid = $list_uid;
        $TransactionalEmailGroup->customer_id = $data['customer_id'];
        $TransactionalEmailGroup->save();

        $TransactionalEmailCompliance = new TransactionalEmailComplianceModel();
        $TransactionalEmailCompliance->transactional_email_group_id
            = $TransactionalEmailGroup->transactional_email_group_id;

        $TransactionalEmailCompliance->compliance_status = 'first-review';
        $TransactionalEmailCompliance->compliance_level_type_id = 2;
        $TransactionalEmailCompliance->date_added = new \DateTime();
        $TransactionalEmailCompliance->last_updated = new \DateTime();
        $TransactionalEmailCompliance->save();

        if ($TransactionalEmailGroup->transactional_email_group_id<1)
        {
            return $this->respondWithError('There was an error, the group was not created.');
        }

        Logger::addProgress('(Transaction Email Group) Created '.print_r($TransactionalEmailGroup, true),
                        '(Transaction Email Group) Created');

        return $this->respond(['transaction_email_group_id' => $TransactionalEmailGroup->transactional_email_group_id]);

    }

}