<?php

namespace App\Http\Controllers;

use App\EmailOne\Transformers\CustomerTransformer;

use App\Customer;
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

    /**
     * @return mixed
     */
    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $endpoint = new \EmailOneApi_Endpoint_Customers();

        $response = $endpoint->create($data);

        if($response->body['status']=='error')
        {
            $msg = $response->body['error'];
            return $this->respondWithError($msg);
        }

        return $this->respond(['customer' => ['customer_uid' => $response->body['customer_uid']]]);
    }

    /**
     * @param $email
     * @return mixed
     */
    public function show($email)
    {

        $Customer = Customer::where('email', '=', $email)->get();

        if(empty($Customer[0]))
        {
            return $this->respondWithError('Customer not found');
        }

        return $this->respond(['customer' => $Customer]);

    }
}
