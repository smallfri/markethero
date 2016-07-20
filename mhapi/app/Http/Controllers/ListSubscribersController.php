<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 9:01 AM
 */

namespace App\Http\Controllers;


use App\Models\ListFieldValueModel;
use App\Models\Lists;
use App\Models\SubscriberModel;
use App\Models\Field;
use App\Models\ListsSubscriber;

class ListSubscribersController extends ApiController
{

    public $endpoint;

    function __construct()
    {

        $this->endpoint = new \EmailOneApi_Endpoint_ListSubscribers();

        $this->middleware('auth.basic');

    }

    public function index()
        {

            // SEARCH BY EMAIL
            $ListSubscriber = ListsSubscriber::all();

            if(empty($ListSubscriber[0]))
            {
                return $this->respondWithError('Subscribers not found.');
            }

            return $this->respond(['subscribers' => $ListSubscriber]);
        }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        if(empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        $expected_input = [
            'list_uid',
            'email',
            'firstname',
            'lastname'
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

        $response = $this->endpoint->create($data['list_uid'], array(
            'EMAIL' => isset($data['email'])?$data['email']:null,
            'FNAME' => isset($data['firstname'])?$data['firstname']:null,
            'LNAME' => isset($data['lastname'])?$data['lastname']:null,
        ));
        $response = $response->body;

        if($response['status']=='error' AND strpos($response['error'], 'Traversable') < 1)
        {
            $msg = $response['error'];
            return $this->respondWithError($msg);
        }

        return $this->respond(['message' => 'subscriber added.']);
    }

    public function update()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        if(empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        $expected_input = [
            'list_uid',
            'email',
            'firstname',
            'lastname'
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

        $Subscriber = SubscriberModel::where('email', '=', $data['email'])->get();
        $subscriber_uid = $Subscriber[0]->subscriber_uid;

        $response = $this->endpoint->update($data['list_uid'], $subscriber_uid, array(
            'EMAIL' => isset($data['email'])?$data['email']:null,
            'FNAME' => isset($data['firstname'])?$data['firstname']:null,
            'LNAME' => isset($data['lastname'])?$data['lastname']:null,
        ));
        $response = $response->body;

        if($response['status']=='error')
        {
            $msg = $response['error'];
            return $this->respondWithError($msg);
        }

        return $this->respond(['message' => 'subscriber updated.']);
    }

    public function unsubscribe()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        if(empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        $expected_input = [
            'list_uid',
            'email'
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

        $List = Lists::where('list_uid','=',$data['list_uid'])->get();

        $list_id = $List[0]->list_id;

        $subscriber = SubscriberModel::where('email','=',$data['email'])->where('list_id','=',$list_id)->get();

        $Subscriber = SubscriberModel::find($subscriber[0]->subscriber_id);

        $Subscriber->status = 'unsubscribed';
        $Subscriber->source = 'api';
        $Subscriber->save();

        if($Subscriber->status == 'unsubscribed')
        {
            return $this->respond(['message' => 'unsubscribed']);

        }
        return $this->respondWithError('Cannot un-subscribe this subscriber');

    }

    public function show($email)
    {

        // SEARCH BY EMAIL
        $ListSubscriber = ListsSubscriber::where('email', '=', $email)->get();

        if(empty($ListSubscriber[0]))
        {
            return $this->respondWithError('Subscriber not found.');
        }

        return $this->respond(['subscriber_uid' => $ListSubscriber[0]->subscriber_uid]);
    }

}