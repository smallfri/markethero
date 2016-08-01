<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Models\TransactionalEmailModel;

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

        if(empty($data))
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
            'send_at'
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
            return $this->respondWithError($missing_fields);
        }

        $email_uid = uniqid();
        $transactional = new TransactionalEmailModel();
        $transactional->email_uid = $email_uid;
        $transactional->to_name = $data['to_name'];
        $transactional->to_email = $data['to_email'];
        $transactional->from_name = $data['from_name'];
        $transactional->from_email = $data['from_email'];
        $transactional->reply_to_name  = $data['reply_to_name'];
        $transactional->reply_to_email  = $data['reply_to_email'];
        $transactional->subject  = $data['subject'];
        $transactional->body  = $data['body'];
        $transactional->plain_text  = $data['plain_text'];
        $transactional->send_at  = $data['send_at'];
        $transactional->customer_id  = $data['customer_id'];
        $transactional->save();


        if($transactional->email_id>0)
        {
            return $this->respond(['email_uid' =>$email_uid]);

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