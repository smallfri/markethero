<?php

namespace App\Http\Controllers;

use App\BlacklistModel;
use App\SubscriberModel;
use App\Http\Requests;
use App\Spam;
use Zend\Http\Response;
use App\Logger;

class BlacklistController extends ApiController
{

    /**
     * @var
     */

    function __construct()
    {
        $this->middleware('auth.basic');
    }


    /**
     * @return mixed
     */
    public function index()
    {

        $Blacklist = BlacklistModel::all();

        if(empty($Blacklist[0]))
        {
            Logger::addError('(BlackList) No BlackList Found '.print_r($Blacklist,true),'(BlackList) No BlackList Found');

            return $this->respondWithError('No Blacklists found.');
        }

        Logger::addProgress('(BlackList) GET '.print_r($Blacklist[0],true),'(BlackList) GET');

        return $this->respond(['blacklist' => $Blacklist->toArray()]);

    }

    public function show($email)
    {

        $Blacklist = BlacklistModel::where('email', '=', $email)->get();

        if(empty($Blacklist[0]))
        {
            Logger::addError('(BlackList) No BlackList Found '.print_r($Blacklist,true),'(BlackList) No BlackList Found');

           return $this->respondWithError('No Blacklist found.');
        }

        Logger::addProgress('(BlackList) Show '.print_r($Blacklist,true),'(BlackList) Show');

        return $this->respond(['blacklist' => $Blacklist->toArray()]);
    }

    public function store($email)
    {

        $Subscriber = SubscriberModel::where('email', '=', $email)->get();

        if (empty($Subscriber[0]))
        {
            Logger::addError('(BlackList) No Subscriber Found '.print_r($email, true),
                '(BlackList) No Subscriber Found');

            return $this->respondWithError('No Subscriber found.');
        }

        $Subscriber = $Subscriber[0];

        $Exist = BlacklistModel::where('email', '=', $email)->get();

        if (!empty($Exist[0]))
        {
            Logger::addError('(BlackList) Subscriber is already blaclisted '.print_r($email, true),
                '(BlackList) Subscriber is already blaclisted');

            return $this->respondWithError('Subscriber is already blaclisted.');
        }

        $Blacklist = new BlacklistModel();

        $Blacklist->subscriber_id = $Subscriber['subscriber_id'];
        $Blacklist->email = $email;
        $Blacklist->Save();

        $Subscriber->status = 'blacklisted';
        $Subscriber->Save();

        Logger::addProgress('(BlackList) Added '.print_r($Blacklist, true), '(BlackList) Added');

        return $this->respond('Subscriber has been GLOBALLY blacklisted.');

    }



}