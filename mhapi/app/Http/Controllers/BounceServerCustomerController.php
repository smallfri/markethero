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

        $this->middleware('auth.basic');

    }

//    public function show($id)
//    {
//
//        $BounceServer = BounceServer::find($id);
//
//        return $this->respond(['bounce_server' => $BounceServer->toArray()]);
//
//    }
//
//    public function index()
//    {
//
//        $BounceServer = BounceServer::all();
//
//        return $this->respond(['bounce_servers' => $BounceServer->toArray()]);

//    }

//    public function store()
//    {
//
//        $PasswordsController = new PasswordsController();
//
//        $BounceServer = new BounceServer();
//        $BounceServer->customer_id = $_POST['customer_id'];
//        $BounceServer->hostname = $_POST['hostname'];
//        $BounceServer->username = $_POST['username'];
//        $BounceServer->password = $PasswordsController->makePassword($_POST['password']);
//        $BounceServer->email = $_POST['email'];
//        $BounceServer->service = $_POST['service'];
//        $BounceServer->port = $_POST['port'];
//        $BounceServer->protocol = $_POST['protocol'];
//        $BounceServer->validate_ssl = $_POST['validate_ssl'];
//        $BounceServer->locked = $_POST['locked'];
//        $BounceServer->disable_authenticator = $_POST['disable_authenticator'];
//        $BounceServer->search_charset = $_POST['search_charset'];
//        $BounceServer->delete_all_messages = $_POST['delete_all_messages'];
//        $BounceServer->save();
//
//        return $this->respond(['bounce_server_id' => $BounceServer->server_id]);
//    }

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
