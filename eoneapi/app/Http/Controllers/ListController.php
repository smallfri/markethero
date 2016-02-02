<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 9:01 AM
 */

namespace App\Http\Controllers;

use App\Lists;
use Illuminate\Support\Facades\URL;

class ListController extends ApiController
{

    public $endpoint;

    function __construct()
    {

        $this->endpoint = new \EmailOneApi_Endpoint_Lists();
        $this->middleware('auth.basic');

    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $response = $this->endpoint->create(array(
            // required
            'general' => array(
                'name'          => $data['name'], // required
                'description'   => $data['description'], // required
            ),
            // required
            'defaults' => array(
                'from_name' => $data['from_name'], // required
                'from_email'=> $data['from_email'], // required
                'reply_to'  => $data['reply_to'], // required
                'subject'   => $data['subject'],
            ),
            // optional
            'notifications' => array(
                // notification when new subscriber added
                'subscribe'         => $data['subscribe'], // yes|no
                // notification when subscriber unsubscribes
                'unsubscribe'       => $data['unsubscribe'], // yes|no
                // where to send the notifications.
                'subscribe_to'      => $data['subscribe_to'],
                'unsubscribe_to'    => $data['unsubscribe_to'],
            ),
            // optional, if not set customer company data will be used
            'company' => array(
                'name'      => isset($data['company_name']) ? $data['company_name'] : '', // required
                'country'   => isset($data['country']) ? $data['country'] : '', // required
                'zone'      => isset($data['state']) ? $data['state'] : '', // required
                'address_1' => isset($data['address_1']) ? $data['address_1'] : '', // required
                'address_2' => isset($data['address_2']) ? $data['address_2'] : '',
                'zone_name' => isset($data['zone_name']) ? $data['zone_name'] : '', // when country doesn't have required zone.
                'city'      => isset($data['city']) ? $data['city'] : '',
                'zip_code'  => isset($data['zip_code']) ? $data['zip_code'] : '',
            ),
        ));


        if($response->body['status']=='error')
        {
            $msg = $response->body['error']['general'];
            return $this->respondWithError($msg);
        }

        return $this->respond(['list_uid' => $response->body['list_uid']]);
    }

    public function index($customer_id, $page, $perPage)
    {

        $url = URL::to('/');

        $Lists = Lists::where('customer_id','=', $customer_id)->skip($page*$perPage)->take($perPage)->get();

        $previousUrl = $url.'/list/customer/'.$customer_id.'/page/'.$page.'/per_page/'.$perPage;

        $page++;

        $nextUrl = $url.'/list/customer/'.$customer_id.'/page/'.$page.'/per_page/'.$perPage;

        return $this->respond(['Lists' => $Lists, 'next' => $nextUrl, 'previous' => $previousUrl]);

    }


}