<?php

namespace App\Http\Controllers;

use App\BlacklistModel;
use App\CampaignAbuseModel;
use App\GroupEmailAbuseModel;
use App\SubscriberModel;
use App\Http\Requests;
use App\Spam;
use Zend\Http\Response;
use App\Logger;

class GroupEmailAbuseController extends ApiController
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
    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $expected_input = [
            'customer_id',
            'reason',
            'log',
        ];

        $missing_fields = array();

        foreach ($expected_input AS $input)
        {
            if (!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if (!empty($missing_fields))
        {
            return $this->respondWithError($missing_fields);
        }

        $Abuse = new GroupEmailAbuseModel();

        $Abuse->customer_id = $data['customer_id'];
        $Abuse->reason = $data['reason'];
        $Abuse->log = $data['log'];
        $Abuse->Save();

        if($Abuse->report_id > 0)
        {
            Logger::addProgress('(Abuse) Abuse reported '.print_r($Abuse, true), '(Abuse) Abuse Reported');
            return $this->respond('Abuse reported.');
        }
        else
        {
            Logger::addError('(Abuse) ERROR '.print_r($Abuse, true),
                            '(Abuse) ERROR');

            return $this->respondWithError('There was an error with this abuse report.');

        }



    }

}