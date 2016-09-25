<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Jobs\SendEmail;
use App\Logger;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;

class GroupEmailsController extends ApiController
{
    private $endpoint;

    function __construct()
    {

        $this->endpoint = new \EmailOneApi_Endpoint_TransactionalEmails();
        $this->middleware('auth.basic');

    }

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if(empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        Logger::addProgress('(GroupEmail) Create '.print_r($data, true),
                   '(GroupEmail) Create');

        Logger::addProgress('(GroupEmail) Server Info '.print_r($_SERVER, true),
                   '(GroupEmail) Server Info');

        $expected_input = [
            'to_name',
            'to_email',
            'from_name',
            'from_email',
            'reply_to_name',
            'reply_to_email',
            'subject',
            'body',
            'plain_text',
            'send_at',
            'group_id'
        ];

        $missing_fields = array();

        foreach($expected_input AS $input)
        {
            if(!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if(!empty($missing_fields))
        {
            Logger::addProgress('(GroupEmail) Missing Fields '.print_r($missing_fields, true),
                            '(GroupEmail) Missing Fields');
            return $this->respondWithError($missing_fields);
        }
        
        $emailUid = uniqid();
        $EmailGroup = new GroupEmailModel();
        $EmailGroup->email_uid = $emailUid;
        $EmailGroup->to_name = $data['to_name'];
        $EmailGroup->to_email = $data['to_email'];
        $EmailGroup->from_name = $data['from_name'];
        $EmailGroup->from_email = $data['from_email'];
        $EmailGroup->reply_to_name = $data['reply_to_name'];
        $EmailGroup->reply_to_email = $data['reply_to_email'];
        $EmailGroup->subject = $data['subject'];
        $EmailGroup->body = $data['body'];
        $EmailGroup->plain_text = $data['plain_text'];
        $EmailGroup->send_at = $data['send_at'];
        $EmailGroup->customer_id = $data['customer_id'];
        $EmailGroup->group_email_id = $data['group_id'];
        $EmailGroup->status = GroupEmailGroupsModel::STATUS_QUEUED;
        $EmailGroup->date_added = new \Datetime;
        $EmailGroup->max_retries =5;
        $EmailGroup->save();

        $job =  new SendEmail($EmailGroup);
        $this->dispatch($job);

        if($EmailGroup->email_id > 0)
        {

            Logger::addProgress('(GroupEmail) Created Email ID '.print_r($emailUid, true),
                '(GroupEmail) Created Email ID');
            return $this->respond(['email_uid' => $emailUid]);

        }

        return $this->respondWithError('Email was not created.');

    }

    public function destroy($email_uid)
    {
        // delete email
        $response = $this->endpoint->delete($email_uid);

        if($response->body['status']=='success')
        {
            return $this->respond(['email_uid' => 'Deleted '.$email_uid.'.']);

        }

        return $this->respondWithError('Email was not deleted.');
    }

}