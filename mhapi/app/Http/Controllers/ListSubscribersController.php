<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 9:01 AM
 */

namespace App\Http\Controllers;


use App\ListsSubscriber;

class ListSubscribersController extends ApiController
{

    public $endpoint;

    function __construct()
    {

        $this->endpoint = new \EmailOneApi_Endpoint_ListSubscribers();

        $this->middleware('auth.basic');

    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $response = $this->endpoint->create($data['list_uid'], array(
            'EMAIL' => isset($data['email'])?$data['email']:null,
            'FNAME' => isset($data['firstname'])?$data['firstname']:null,
            'LNAME' => isset($data['lastname'])?$data['lastname']:null,
        ));
        $response = $response->body;

//        print_r($response);exit;

//        if($response->body['status']=='error')
//        {
//            $msg = $response->body['error']['general'];
//            return $this->respondWithError($msg);
//        }

        return $this->respond(['message' => 'subscriber added.']);
    }

    public function unsubscribe()
    {

        $data = json_decode(file_get_contents('php://input'), true);


        // UNSUBSCRIBE existing subscriber, no email is sent, unsubscribe is silent
        $response = $this->endpoint->unsubscribeByEmail($data['list_uid'], $data['email']);

        if($response->body['status']=='error')
        {
            $msg = $response->body['error']['general'];
            return $this->respondWithError($msg);
        }

        return $this->respond(['message' => 'unsubscribed']);
    }

    public function show($email)
    {

        // SEARCH BY EMAIL
       $ListSubscriber = ListsSubscriber::where('email','=',$email)->get();

        if(empty($ListSubscriber[0]))
        {
            return $this->respondWithError('Subscriber not found.');
        }

        return $this->respond(['subscriber_uid' => $ListSubscriber[0]->subscriber_uid]);
    }



}