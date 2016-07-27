<?php

namespace App\Http\Controllers;

use App\EmailOne\Transformers\CustomerTransformer;

use App\Models\Customer;
use App\Http\Requests;
use Zend\Http\Response;
use Illuminate\Http\Request;


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

    public function edit($customerId, Request $request)
    {

        $Customer = Customer::find($customerId);

        if(isset($_POST))
        {
            $Customer->first_name = $request->input('first_name');
            $Customer->last_name = $request->input('last_name');
            $Customer->email = $request->input('email');
            $Customer->status = $request->input('status');
            $Customer->save();
        }
        $Customer = Customer::find($customerId);

        $data = ['customer'=>$Customer];

        return view('dashboard.customers.edit', $data);

    }

}
