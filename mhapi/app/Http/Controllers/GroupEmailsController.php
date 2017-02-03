<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Logger;
use App\Models\BroadcastEmailModel;
use App\Models\Customer;
use App\Models\GroupEmailComplianceLevelsModel;
use App\Models\GroupEmailModel;
use App\Models\PauseGroupEmailModel;

class GroupEmailsController extends ApiController
{

    private $use_queues;

    private $use_compliance;

    public $helpers;

    function __construct()
    {

        $this->helpers = new Helpers();
        $this->use_queues = true;
        $this->use_compliance = false;
        $this->middleware('auth.basic');
    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        Logger::addProgress('(BroadcastEmail) Create '.print_r($data, true),
            '(BroadcastEmail) Create');

        Logger::addProgress('(BroadcastEmail) Server Info '.print_r($_SERVER, true),
            '(BroadcastEmail) Server Info');

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
            'group_id',
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
            Logger::addProgress('(GroupEmail) Missing Fields '.print_r($missing_fields, true),
                '(GroupEmail) Missing Fields');
            return $this->respondWithError($missing_fields);
        }

        $Customer = Customer::find($data['customer_id']);

        if (empty($Customer))
        {
            return $this->respondWithError('Customer id does not exist.');
        }

        $hash = md5(256, trim($data['group_id']). trim($data['to_email']) . trim($data['body']) . trim($data['subject']));

        $emailExist = GroupEmailModel::where('hash', '=', $hash)
            ->get();

        if (!$emailExist->isEmpty())
        {
            return $this->respond(['email_uid' => $emailExist[0]['emailUID']]);
        }

        /*
         * Check for this email in blacklist for this customer id and exit if found.
         */
        if ($this->helpers->isBlacklisted($data['to_email'], $data['customer_id']))
        {
            return $this->respondWithError('This email was found on the blacklist and was not emailed');
        }

        $compliance = false;

        /*
         * compliance handler
         */
//        if ($this->use_compliance)
//        {
//            //check options
//            $options = $this->helpers->getOptions();
//
//
//            // count number of emails that have already been sent for this group
//            $countSent = $this->helpers->countSent($data['group_id']);
//
//            // get the compliance status id
//            $threshold = $this->helpers->checkComplianceStatus($data['group_id']);
//
//            // get the compliance %
//            $sendPercent = GroupEmailComplianceLevelsModel::find($threshold);
//
//            // get the leads count
//            $Group = GroupEmailGroupsModel::find($data['group_id']);
//
//            // determine the % of the group we should send immediatly
//            $sendAmount = $sendPercent['threshold']*$Group['leads_count'];
//
//            // if the number sent is greater than the compliance limit, set use queues to false
//            if ($countSent>$options->compliance_limit&&$countSent>$sendAmount)
//            {
//                $compliance = true;
//
//                $this->helpers->updateGroupStatus($data['group_id'], GroupEmailGroupsModel::STATUS_IN_REVIEW);
//
//            }
//        }

        $emailUid = uniqid('', true);
        /*
         * if send_at is less than now, we are going to queue the emails, otherwise we will insert into db and mark
         * as pending-sending.
         */

        $Pause = PauseGroupEmailModel::where('customer_id', '=', $data['customer_id'])
            ->orWhere('group_email_id', '=', $data['group_id'])
            ->get();

        $pause = false;
        if (!empty($Pause[0]))
        {
            if ($Pause[0]->pause_customer==1||$Pause[0]->group_email_id==$data['group_id'])
            {
                $pause = true;
            }
        }
        $Email = new BroadcastEmailModel();
        $Email->emailUID = $emailUid;
        $Email->toName = $data['to_name'];
        $Email->toEmail = $data['to_email'];
        $Email->formName = $data['from_name'];
        $Email->fromEmail = $data['from_email'];
        $Email->replyToName = $data['reply_to_name'];
        $Email->replyToEmail = $data['reply_to_email'];
        $Email->subject = $data['subject'];
        $Email->body = $data['body'];
        $Email->plainText = $data['plain_text'];
        $Email->customerID = $data['customer_id'];
        $Email->groupID = $data['group_id'];
        if ($compliance)
        {
            $Email->status = BroadcastEmailModel::STATUS_IN_REVIEW;
        }
        elseif ($pause==true)
        {
            $Email->status = BroadcastEmailModel::STATUS_PAUSED;
        }
        else
        {
            $Email->status = BroadcastEmailModel::STATUS_PENDING_SENDING;
        }

        $Email->dateAdded = $Email->lastUpdated = new \DateTime();
        $Email->max_retries = 5;
        $Email->save();

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