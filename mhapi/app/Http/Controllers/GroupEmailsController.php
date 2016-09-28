<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Jobs\SendEmail;
use App\Logger;
use App\Models\GroupEmailGroupsModel;
use App\Models\GroupEmailModel;

class GroupEmailsController extends ApiController
{

    private $use_queues;
    public $helpers;


    function __construct()
    {
        $this->helpers = new Helpers();
        $this->use_queues = true;
        $this->middleware('auth.basic');
    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data))
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

        foreach ($expected_input AS $input)
        {
            if (!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if (!empty($missing_fields))
        {
            Logger::addProgress('(GroupEmail) Missing Fields '.print_r($missing_fields, true),
                '(GroupEmail) Missing Fields');
            return $this->respondWithError($missing_fields);
        }

        /*
         * Check for this email in blacklist for this customer id and exit if found.
         */
        if($this->helpers->isBlacklisted($data['to_email'], $data['customer_id']))
        {
            return $this->respondWithError('This email was found on the blacklist and was not emailed');
        }

        //Server is set to UTC + 10 minutes???
        $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone('Etc/UTC'));

        //Set user timezone to EST for the time being
        $date->setTimezone(new \DateTimeZone('EST'));

        //fix the 10 minute difference
        $date->sub(new \DateInterval('PT10M'));

        $now = $date->format('Y-m-d H:i:s');

        /*
         * Check options
         */

//        $options = $this->helpers->getOptions();

        /*
         * We need to check if the group is in compliance and handle accordingly
         */

//        if($this->helpers->checkComplianceStatus($data['group_id']) == true)
//        {
//
//        }

        /*
         * if send_at is less than now, we are going to queue the emails, otherwise we will insert into db and mark
         * as pending-sending.
         */
        if ($data['send_at']<$now && $this->use_queues == true)
        {
            //create class to queue
            $emailUid = uniqid();
            $EmailGroup = new \stdClass();
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
            $EmailGroup->date_added = new \DateTime();
            $EmailGroup->max_retries = 5;

            $job = new SendEmail($EmailGroup);
            $this->dispatch($job);
        }
        else
        {
            $emailUid = uniqid();
            $Email = new GroupEmailModel();
            $Email->email_uid = $emailUid;
            $Email->to_name = $data['to_name'];
            $Email->to_email = $data['to_email'];
            $Email->from_name = $data['from_name'];
            $Email->from_email = $data['from_email'];
            $Email->reply_to_name = $data['reply_to_name'];
            $Email->reply_to_email = $data['reply_to_email'];
            $Email->subject = $data['subject'];
            $Email->body = $data['body'];
            $Email->plain_text = $data['plain_text'];
            $Email->send_at = $data['send_at'];
            $Email->customer_id = $data['customer_id'];
            $Email->group_email_id = $data['group_id'];
            $Email->status = GroupEmailGroupsModel::STATUS_PENDING_SENDING;
            $Email->date_added = new \DateTime();
            $Email->max_retries = 5;
            $Email->save();
        }

        if ($emailUid)
        {
            return $this->respond(['email_uid' => $emailUid]);
        }


        return $this->respondWithError('Email was not created.');

    }

    public function destroy($email_uid)
    {

        // delete email
        $response = $this->endpoint->delete($email_uid);

        if ($response->body['status']=='success')
        {
            return $this->respond(['email_uid' => 'Deleted '.$email_uid.'.']);

        }

        return $this->respondWithError('Email was not deleted.');
    }

}