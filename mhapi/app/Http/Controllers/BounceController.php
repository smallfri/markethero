<?php

namespace App\Http\Controllers;

use App\Bounce;
use App\Http\Requests;
use Zend\Http\Response;
use App\Logger;
class BounceController extends ApiController
{


    function __construct()
    {

        //$this->middleware('auth.basic');

    }


    /**
     * @return mixed
     */
    public function index()
    {

        $Bounce = Bounce::all();

        if(empty($Bounce[0]))
        {
            Logger::addError('(Bounces) No Bounces Found '.print_r($Bounce,true),'(Bounces) No Bounces Found');

           return $this->respondWithError('No Bounces not found.');
        }

        Logger::addProgress('(Bounce) Bounces Get '.print_r($Bounce,true),'(Bounce) Bounces Get');

        return $this->respond(['bounces' => $Bounce->toArray()]);

    }

    public function show($campaign_id)
    {

        $Bounces = Bounce::where('campaign_id', '=', $campaign_id)->get();

        if(empty($Bounces[0]))
        {
            Logger::addError('(Bounces) No Bounces Found '.print_r($Bounces,true),'(Bounces) No Bounces Found');

            return $this->respondWithError('No Bounces found.');
        }

        Logger::addProgress('(Bounce) Bounces Show '.print_r($Bounces,true),'(Bounce) Bounces Show');

        return $this->respond(['bounces' => $Bounces->toArray()]);
    }



}
