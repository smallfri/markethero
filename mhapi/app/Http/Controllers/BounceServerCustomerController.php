<?php

namespace App\Http\Controllers;

use App\Transformers\BounceServerTransformer;
use App\Http\Controllers\PasswordsController;
use App\BounceServer;
use App\User;
use App\Http\Requests;
use Zend\Http\Response;

class BounceServerCustomerController extends ApiController
{

    protected $bounceServerTransformer;

    function __construct(BounceServerTransformer $bounceServerTransformer)
    {

        $this->BounceServerTransformer = $bounceServerTransformer;

        //$this->middleware('auth.basic');

    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {

        $BounceServer = BounceServer::where('customer_id', '=', $id)->get();

        if(empty($BounceServer))
        {
            return $this->respondWithError('Bounce Server not found.');
        }

        return $this->respond(['bounce_server' => $BounceServer]);
    }

}
