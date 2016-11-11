<?php

namespace App\Http\Controllers;

use App\Models\BlacklistModel;
use App\Models\SubscriberModel;
use App\Http\Requests;
use App\Models\Spam;
use App\Models\UnsubscribeModel;
use Faker\Provider\DateTime;
use Zend\Http\Response;
use App\Logger;

class UnsubscribeController extends ApiController
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

        $Unsubs = UnsubscribeModel::all();

        if (empty($Unsubs[0]))
        {
            Logger::addProgress('(Unsubscribe) No Unsubscribes Found '.print_r($Blacklist, true),
                '(Unsubscribe) No Unsubscribes Found');

            return $this->respondWithError('No Blacklists found.');
        }

        Logger::addProgress('(Unsubscribe) GET '.print_r($Unsubs[0], true), '(BlackList) GET');

        return $this->respond(['unsubscribe' => $Unsubs->toArray()]);

    }

    public function show($email)
    {

        $Unsubs = UnsubscribeModel::where('email', '=', $email)->get();

        if (empty($Unsubs[0]))
        {
            Logger::addProgress('(Unsubscribe) No Unsubscribes Found '.print_r($Unsubs, true),
                '(Unsubscribe) No Unsubscribes Found');

            return $this->respondWithError('No Unsubscribes found.');
        }

        Logger::addProgress('(UnsubscribeModel) Show '.print_r($Unsubs, true), '(Unsubscribe) Show');

        return $this->respond(['unsubscribe' => $Unsubs->toArray()]);
    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        Logger::addProgress('(UnsubscribeModel) Create '.print_r($data, true),
            '(UnsubscribeModel) Create');

        if (empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        $expected_input = [
            'customer_id',
            'email',
            'group_id',
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
            Logger::addProgress('(UnsubscribeModel) Missing Fields '.print_r($missing_fields, true),
                '(UnsubscribeModel) Missing Fields');

            return $this->respondWithError($missing_fields);
        }

        $Unsub = UnsubscribeModel::where('email', '=', $data['email'])->get()->toArray();

        if (empty($Unsub[0]))
        {
            $Unsub = new UnsubscribeModel();
        }
        else
        {
            $Unsub = UnsubscribeModel::find($Unsub[0]['id']);
        }

        $Unsub->customer_id = $data['customer_id'];
        $Unsub->group_email_id = $data['group_id'];
        $Unsub->email = $data['email'];
        $Unsub->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $Unsub->ip_address = $_SERVER['REMOTE_ADDR'];
        $Unsub->date_added = new \DateTime();

        $Unsub->Save();


        Logger::addProgress('(UnsubscribeModel) Added '.print_r($Unsub, true), '(UnsubscribeModel) Added');

        return $this->respond($data['email'].' has been unsubscribed.');

    }

}
