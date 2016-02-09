<?php

namespace App\Http\Controllers;

use App\BlacklistModel;
use App\Http\Requests;
use App\Spam;
use Zend\Http\Response;

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
            return $this->respondWithError('No Blacklists found.');
        }

        return $this->respond(['blacklist' => $Blacklist->toArray()]);

    }

    public function show($email)
    {

        $Blacklist = BlacklistModel::where('email', '=', $email)->get();

        if(empty($Blacklist[0]))
        {
           return $this->respondWithError('No Blacklist found.');
        }

        return $this->respond(['blacklist' => $Blacklist->toArray()]);
    }



}
