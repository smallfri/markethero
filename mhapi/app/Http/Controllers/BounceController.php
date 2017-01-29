<?php

namespace App\Http\Controllers;

use App\Models\Bounce;
use App\Http\Requests;
use Zend\Http\Response;
use App\Logger;
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
            Logger::addProgress('(Bounces) No Bounces Found '.print_r($Bounce,true),'(Bounces) No Bounces Found');
//            Logger::addProgress('(Bounces) Server of incoming request '.$_SERVER['HTTP_REFERER'],'(Bounces) No Bounces Found');

           return $this->respondWithError('No Bounces not found.');
        }

        Logger::addProgress('(Bounce) Bounces Get '.print_r($Bounce,true),'(Bounce) Bounces Get');
//        Logger::addProgress('(Bounces) Server of incoming request '.$_SERVER['HTTP_REFERER'],'(Bounces) No Bounces Found');


        return $this->respond(['bounces' => $Bounce->toArray()]);

    }

    public function show($group_id)
    {

        $Bounces = Bounce::where('group_id', '=', $group_id)->get();

        if(empty($Bounces[0]))
        {
            Logger::addProgress('(Bounces) No Bounces Found '.print_r($Bounces,true),'(Bounces) No Bounces Found');
//            Logger::addProgress('(Bounces) Server of incoming request '.$_SERVER['HTTP_REFERER'],'(Bounces) No Bounces Found');


            return $this->respondWithError('No Bounces found.');
        }

        Logger::addProgress('(Bounce) Bounces Show '.print_r($Bounces,true),'(Bounce) Bounces Show');
//        Logger::addProgress('(Bounces) Server of incoming request '.$_SERVER['HTTP_REFERER'],'(Bounces) No Bounces Found');


        return $this->respond(['bounces' => $Bounces->toArray()]);
    }



}
