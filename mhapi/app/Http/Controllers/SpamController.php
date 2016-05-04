<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Spam;
use Zend\Http\Response;

class SpamController extends ApiController
{

    /**
     * @var
     */

    function __construct()
    {
        //$this->middleware('auth.basic');
    }


    /**
     * @return mixed
     */
    public function index()
    {

        $Spam = Spam::all();

        if(empty($Spam[0]))
        {
            return $this->respond('No Spam not found.');
        }

        return $this->respond(['spam' => $Spam->toArray()]);

    }

    public function show($campaign_id)
    {

        $Spam = Spam::where('campaign_id', '=', $campaign_id)->get();

        if(empty($Spam[0]))
        {
           return $this->respond('No Spam found.');
        }

        return $this->respond(['spam' => $Spam->toArray()]);
    }



}
