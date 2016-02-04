<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/28/16
 * Time: 9:01 AM
 */

namespace App\Http\Controllers;

use App\Country;
use App\Customer;
use App\Lists;
use App\ListsCompany;
use App\ListsDefaults;
use App\ListsCustomerNotification;
use App\Zone;
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

        $Lists = new Lists();
        $Lists->name = $data['name'];
        $Lists->list_uid = uniqid();
        $Lists->customer_id = $data['customer_id'];
        $Lists->description = $data['description'];
        $Lists->save();

        $ListsDefaults = new ListsDefaults();

        $ListsDefaults->from_name = $data['from_name']; // required
        $ListsDefaults->from_email = $data['from_email']; // required
        $ListsDefaults->reply_to = $data['reply_to']; // required
        $ListsDefaults->subject = $data['subject'];
        $ListsDefaults->list_id = $Lists->list_id;
        $ListsDefaults->save();

        $ListsCustomerNotifications = new ListsCustomerNotification();

        $ListsCustomerNotifications->subscribe = $data['subscribe']; // yes|no
        $ListsCustomerNotifications->unsubscribe = $data['unsubscribe']; // yes|no
        $ListsCustomerNotifications->subscribe_to = $data['subscribe_to'];
        $ListsCustomerNotifications->unsubscribe_to = $data['unsubscribe_to'];
        $ListsCustomerNotifications->list_id = $Lists->list_id;
        $ListsCustomerNotifications->save();

        $ListsCompany = new ListsCompany();

        $Country_id = Country::where('code', '=', $data['country'])->get();

        $Zone = Zone::where('country_id', '=', $Country_id[0]->country_id)->get();

        $ListsCompany->name = isset($data['company_name'])?$data['company_name']:''; // required
        $ListsCompany->country_id = isset($Country_id[0]->country_id)?$Country_id[0]->country_id:''; // required
        $ListsCompany->address_1 = isset($data['address_1'])?$data['address_1']:''; // required
        $ListsCompany->address_2 = isset($data['address_2'])?$data['address_2']:'';
        $ListsCompany->zone_name
            = isset($Zone[0]->zone_name)?$Zone[0]->zone_name:''; // when country doesn't have required zone.
        $ListsCompany->city = isset($data['city'])?$data['city']:'';
        $ListsCompany->zip_code = isset($data['zip_code'])?$data['zip_code']:'';
        $ListsCompany->list_id = $Lists->list_id;
        $ListsCompany->save();


        $List = Lists::where('list_id', '=', $Lists->list_id)->get();

        return $this->respond(['list_uid' => $List[0]->list_uid, 'list_id' => $Lists->list_id]);
    }

    public function index($customer_id, $page, $perPage)
    {

        $url = URL::to('/');

        $Lists = Lists::where('customer_id', '=', $customer_id)->skip($page*$perPage)->take($perPage)->get();

        $previousUrl = $url.'/list/customer/'.$customer_id.'/page/'.$page.'/per_page/'.$perPage;

        $page++;

        $nextUrl = $url.'/list/customer/'.$customer_id.'/page/'.$page.'/per_page/'.$perPage;

        return $this->respond(['lists' => $Lists, 'next' => $nextUrl, 'previous' => $previousUrl]);

    }

    public function save($list_uid)
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $Lists = Lists::where('list_uid', '=', $list_uid)->get();

        $Lists = Lists::find($Lists[0]->list_id);

        $Lists->name = $data['name'];
        $Lists->description = $data['description'];
        $Lists->save();

        $ListsDefaults = ListsDefaults::find($Lists->list_id);

        $ListsDefaults->from_name = $data['from_name']; // required
        $ListsDefaults->from_email = $data['from_email']; // required
        $ListsDefaults->reply_to = $data['reply_to']; // required
        $ListsDefaults->subject = $data['subject'];
        $ListsDefaults->save();

        $ListsCustomerNotifications = ListsCustomerNotification::find($Lists->list_id);

        $ListsCustomerNotifications->subscribe = $data['subscribe']; // yes|no
        $ListsCustomerNotifications->unsubscribe = $data['unsubscribe']; // yes|no
        $ListsCustomerNotifications->subscribe_to = $data['subscribe_to'];
        $ListsCustomerNotifications->unsubscribe_to = $data['unsubscribe_to'];
        $ListsCustomerNotifications->save();

        $ListsCompany = ListsCompany::find($Lists->list_id);

        $Country_id = Country::where('code', '=', $data['country'])->get();

        $Zone = Zone::where('country_id', '=', $Country_id[0]->country_id)->get();

        $ListsCompany->name = isset($data['company_name'])?$data['company_name']:''; // required
        $ListsCompany->country_id = isset($Country_id[0]->country_id)?$Country_id[0]->country_id:''; // required
        $ListsCompany->address_1 = isset($data['address_1'])?$data['address_1']:''; // required
        $ListsCompany->address_2 = isset($data['address_2'])?$data['address_2']:'';
        $ListsCompany->zone_name
            = isset($Zone[0]->zone_name)?$Zone[0]->zone_name:''; // when country doesn't have required zone.
        $ListsCompany->city = isset($data['city'])?$data['city']:'';
        $ListsCompany->zip_code = isset($data['zip_code'])?$data['zip_code']:'';
        $ListsCompany->save();

        return $this->respond(['success' => 'list updated.']);
    }

    public function destroy($list_uid)
    {

        $Lists = Lists::where('list_uid', '=', $list_uid)->get();

        if(!empty($Lists[0]))
        {
            $Lists = Lists::find($Lists[0]->list_id);
            $list_id = $Lists->list_id;
            $Lists->forceDelete();


            $ListsDefaults = ListsDefaults::find($list_id);
            if(!empty($ListsDefaults))
            {
                $ListsDefaults->forceDelete();

            }

            $ListsCustomerNotifications = ListsCustomerNotification::find($list_id);
            if(!empty($ListsCustomerNotifications))
            {
                $ListsCustomerNotifications->forceDelete();

            }

            $ListsCompany = ListsCompany::find($list_id);
            if(!empty($ListsCompany))
            {
                $ListsCompany->forceDelete();

            }
            return $this->respond(['success' => 'list '.$list_uid.' deleted.']);
        }

        return $this->respondWithError('list '.$list_uid.' not found.');

    }

    public function show($email)
    {

        $Customer = Customer::where('email', '=', $email)->get();

        if(empty($Customer[0]))
        {
            return $this->respondWithError('Customer not found');
        }

        $customer_id = $Customer[0]->customer_id;

        $Lists = Lists::where('customer_id', '=', $customer_id)->get();

        return $this->respond(['lists' => $Lists]);

    }

}

//curl -u russell@smallfri.com:KjV9g2JcyFGAHng -i -X POST -H "Content-Type:application/json" http://m-staging.markethero.io/mhapi/v1/customer -d '{"customer": {"first_name":"sample name","last_name":"sample last name","email":"email@domain.com","confirm_email":"email@domain.com","confirm_password":"password","fake_password":"password","group_id":1}}'