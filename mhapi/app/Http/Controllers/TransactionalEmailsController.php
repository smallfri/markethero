<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Models\Customer;
use App\Models\DeliveryServerModel;
use App\Models\TransactionalEmailLogModel;
use App\Models\TransactionalEmailModel;
use App\Jobs\SendTransactionalEmail;

class TransactionalEmailsController extends ApiController
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

        if (empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

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
            return $this->respondWithError($missing_fields);
        }

        $Customer = Customer::find($data['customer_id']);

        if (empty($Customer))
        {
            return $this->respondWithError('Customer id does not exist.');
        }

        $hash = md5(256, trim($data['to_email']).trim($data['body']).trim($data['subject']));

        $emailExist = TransactionalEmailModel::where('hash', '=', $hash)
            ->get();

        if (!$emailExist->isEmpty())
        {
            return false;
        }

        $emailUID = uniqid('', true);

        $Email = new TransactionalEmailModel();
        $Email->emailUID = $emailUID;
        $Email->mhEmailID = $data->id;
        $Email->customerID = $data['customer_id'];
        $Email->toName = $data['to_name'];
        $Email->toEmail = $data['to_email'];
        $Email->formName = $data['from_name'];
        $Email->fromEmail = $data['from_email'];
        $Email->replyToName = $data['reply_to_name'];
        $Email->replyToEmail = $data['reply_to_email'];
        $Email->subject = $data['subject'];
        $Email->body = $data['body'];
        $Email->plainText = $data['plain_text'];
        $Email->dateAdded = $Email->lastUpdated = new \DateTime();
        $Email->status = 'queued';
        $Email->save();

        $this->Email = $Email;

        $job = (new SendTransactionalEmail($Email))->onConnection('redis')
            ->onQueue('redis-transactional-queue');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

        if ($Email->email_id>0)
        {
            return $this->respond(['email_uid' => $emailUID]);
        }
        else
        {
            return $this->respondWithError('Email Not Created');
        }

    }
}