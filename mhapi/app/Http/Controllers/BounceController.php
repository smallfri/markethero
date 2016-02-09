<?php

namespace App\Http\Controllers;

use App\Bounce;
use App\Http\Requests;
use Zend\Http\Response;

class BounceController extends ApiController
{


    function __construct()
    {

        $this->middleware('auth.basic');

    }


    /**
     * @return mixed
     */
    public function index()
    {

        $Bounce = Bounce::all();

        if(empty($Bounce[0]))
        {
           return $this->respondWithError('No Bounces not found.');
        }

        return $this->respond(['bounces' => $Bounce->toArray()]);

    }

    public function show($campaign_id)
    {

        $Bounces = Bounce::where('campaign_id', '=', $campaign_id)->get();

        if(empty($Bounces[0]))
        {
            return $this->respondWithError('No Bounces found.');
        }

        return $this->respond(['bounces' => $Bounces->toArray()]);
    }



}
