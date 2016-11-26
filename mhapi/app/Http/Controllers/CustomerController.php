<?php

namespace App\Http\Controllers;

use App\EmailOne\Transformers\CustomerTransformer;

use App\Models\Customer;
use App\Http\Requests;
use Zend\Http\Response;

/**
 * Class CustomerController
 * @package App\Http\Controllers
 */
class CustomerController extends ApiController
{

    /**
     * CustomerController constructor.
     * @param CustomerTransformer $customerTransformer
     */
    function __construct(CustomerTransformer $customerTransformer)
    {

        $this->customerTransformer = $customerTransformer;

        $this->middleware('auth.basic');

    }

    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $expected_input = [
            'confirm_email',
            'confirm_password',
            'email',
            'fake_password',
            'first_name',
            'group_id',
            'last_name',
        ];

        $missing_fields = array();

        foreach ($expected_input AS $input)
        {
            if (!isset($data['customer'][$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if (!empty($missing_fields))
        {
            return $this->respondWithError($missing_fields);
        }

        $uid = uniqid();

        $customer= Customer::where('email', '=', $data['customer']['email'])->get();
//dd($customer);
        if(!empty($customer[0]))
        {
            return $this->respond(['customer' => ['customer_uid' => $customer[0]['customer_uid']]]);
        }


        $customer = new Customer();
        $customer->customer_uid = $uid;
        $customer->first_name = $data['customer']['first_name'];
        $customer->email = $data['customer']['email'];
        $customer->password = bcrypt($data['customer']['confirm_password']);
        $customer->date_added = new \DateTime();
        $customer->status = 'active';
        $customer->save();

        if($customer)
        {
            return $this->respond(['customer' => ['customer_uid' => $uid]]);
        }
        return $this->respondWithError('Customer not created.');


    }

    /**
     * @return mixed
     */
//    public function store2()
//    {
//
//        $data = json_decode(file_get_contents('php://input'), true);
//
//        $endpoint = new \EmailOneApi_Endpoint_Customers();
//
//        $expected_input = [
//            'confirm_email',
//            'confirm_password',
//            'email',
//            'fake_password',
//            'first_name',
//            'group_id',
//            'last_name',
//        ];
//
//        $missing_fields = array();
//
//        foreach ($expected_input AS $input)
//        {
//            if (!isset($data['customer'][$input]))
//            {
//                $missing_fields[$input] = 'Input field not found.';
//            }
//
//        }
//
//        if (!empty($missing_fields))
//        {
//            return $this->respondWithError($missing_fields);
//        }
//
//        $response = $endpoint->create($data);
//
//        if ($response->body['status']=='error')
//        {
//            $msg = $response->body['error'];
//            return $this->respondWithError($msg);
//        }
//
//        return $this->respond(['customer' => ['customer_uid' => $response->body['customer_uid']]]);
//    }

    /**
     * @param $email
     * @return mixed
     */
    public function show($email)
    {
        $Customer = Customer::where('email', '=', $email)->get();

        if (empty($Customer[0]))
        {
            return $this->respondWithError('Customer not found');
        }

        return $this->respond(['customer' => $Customer]);

    }
}
