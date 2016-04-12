<?php

namespace App\Http\Controllers;

use App\BlacklistModel;
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



}
