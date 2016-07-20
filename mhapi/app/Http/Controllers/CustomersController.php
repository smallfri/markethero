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
class CustomersController extends ApiController
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
    public function index()
    {

        $Customers = Customer::all();

        if(empty($Customers))
        {
            return $this->respondWithError('No Customers found.');
        }

        return $this->respond(['customers' => $Customers]);

    }
}
