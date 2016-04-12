<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 2/5/16
 * Time: 8:56 AM
 */

namespace App\Http\Controllers;

use App\Logger;

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

        Logger::addProgress('(Transaction Email) Create '.print_r($data,true),'(Transaction Email) Create');

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
            'send_at',
            'customer_id'
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
            Logger::addError('(Transaction Email) Missing Fields '.print_r($missing_fields,true),'(Transaction Email) Missing Fields');

            return $this->respondWithError($missing_fields);
        }

        // CREATE A NEW EMAIL
        $response = $this->endpoint->create(array(
            'to_name' => $data['to_name'],
            'to_email' => $data['to_email'],
            'from_name' => $data['from_name'],
            'from_email' => $data['from_email'],
            'reply_to_name'=> $data['reply_to_name'],
            'reply_to_email' => $data['reply_to_email'],
            'subject'=> $data['subject'],
            'body'=> $data['body'],
            'plain_text'=> $data['plain_text'],
            'send_at'=> $data['send_at'],
            'customer_id'=> $data['customer_id']
        ));

        Logger::addProgress('(Transaction Email) Response '.print_r($response,true),'(Transaction Email) Response');


        if($response->body['status']=='success')
        {
            Logger::addProgress('(Transaction Email) Response Success '.print_r($response,true),'(Transaction Email) Response Success');

            return $this->respond(['email_uid' => $response->body['email_uid']]);

        }

        return $this->respondWithError('Email was not created.');

    }

    public function destroy($email_uid)
    {
        // delete email
        $response = $this->endpoint->delete($email_uid);

        if($response->body['status']=='success')
        {
            Logger::addProgress('(Transaction Email) Delete '.print_r($response,true),'(Transaction Email) Delete');

            return $this->respond(['email_uid' => 'Deleted '.$email_uid.'.']);

        }

        Logger::addError('(Transaction Email) Email Not Deleted '.print_r($response,true),'(Transaction Email) Email Not Deleted');

        return $this->respondWithError('Email was not deleted.');
    }

}