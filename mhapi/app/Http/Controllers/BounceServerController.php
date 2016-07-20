<?php

namespace App\Http\Controllers;

use App\Transformers\BounceServerTransformer;
use App\Models\BounceServer;
use App\Http\Requests;
use Zend\Http\Response;

class BounceServerController extends ApiController
{

    /**
     * @var
     */
    protected $bounceServerTransformer;

    /**
     * BounceServerController constructor.
     * @param BounceServerTransformer $bounceServerTransformer
     */
    function __construct(BounceServerTransformer $bounceServerTransformer)
    {

        $this->BounceServerTransformer = $bounceServerTransformer;

        $this->middleware('auth.basic');

    }

    /**
     *  UNDOCUMENTED ENDPOINT
     ** @param $id
     * @return mixed
     */
    public function show($id)
    {

        $BounceServer = BounceServer::find($id);

        if(empty($BounceServer))
        {
            $this->respondWithError('Bounce Server not found.');
        }

        return $this->respond(['bounce_server' => $BounceServer->toArray()]);

    }

    /**
     * @return mixed
     */
    public function index()
    {

        $BounceServer = BounceServer::all();

        if(empty($BounceServer))
        {
            $this->respondWithError('No Bounce Servers not found.');
        }

        return $this->respond(['bounce_servers' => $BounceServer->toArray()]);

    }

    /**
     * @return mixed
     */
    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        if(empty($data))
        {
            return $this->respondWithError('No data found, please check your POST data and try again');
        }

        $expected_input = [
            'customer_id',
            'hostname',
            'username',
            'password',
            'email',
            'service',
            'port',
            'protocol',
            'validate_ssl',
            'locked',
            'disable_authenticator',
            'search_charset',
            'delete_all_messages'
        ];

        $missing_fields = array();

        foreach($expected_input AS $input)
        {
            if(!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if(!empty($missing_fields))
        {
            return $this->respondWithError($missing_fields);
        }

        $PasswordsController = new PasswordsController();

        $BounceServer = new BounceServer();
        $BounceServer->customer_id = $data['customer_id'];
        $BounceServer->hostname = $data['hostname'];
        $BounceServer->username = $data['username'];
        $BounceServer->password = $PasswordsController->makePassword($data['password']);
        $BounceServer->email = $data['email'];
        $BounceServer->service = $data['service'];
        $BounceServer->port = $data['port'];
        $BounceServer->protocol = $data['protocol'];
        $BounceServer->validate_ssl = $data['validate_ssl'];
        $BounceServer->locked = $data['locked'];
        $BounceServer->disable_authenticator = $data['disable_authenticator'];
        $BounceServer->search_charset = $data['search_charset'];
        $BounceServer->delete_all_messages = $data['delete_all_messages'];
        $BounceServer->save();

        if($BounceServer->server_id<1)
        {
            return $this->respondWithError('There was an error, the bounce server was not created.');
        }

        return $this->respond(['bounce_server_id' => $BounceServer->server_id]);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function update($id)
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $expected_input = [
            'customer_id',
            'hostname',
            'username',
            'password',
            'email',
            'service',
            'port',
            'protocol',
            'validate_ssl',
            'locked',
            'disable_authenticator',
            'search_charset',
            'delete_all_messages'
        ];

        $missing_fields = array();

        foreach($expected_input AS $input)
        {
            if(!isset($data[$input]))
            {
                $missing_fields[$input] = 'Input field not found.';
            }

        }

        if(!empty($missing_fields))
        {
            return $this->respondWithError($missing_fields);
        }

        $PasswordsController = new PasswordsController();

        $BounceServer = BounceServer::find($id);
        $BounceServer->customer_id = $data['customer_id'];
        $BounceServer->hostname = $data['hostname'];
        $BounceServer->username = $data['username'];
        $BounceServer->password = $PasswordsController->makePassword($data['password']);
        $BounceServer->email = $data['email'];
        $BounceServer->service = $data['service'];
        $BounceServer->port = $data['port'];
        $BounceServer->protocol = $data['protocol'];
        $BounceServer->validate_ssl = $data['validate_ssl'];
        $BounceServer->locked = $data['locked'];
        $BounceServer->disable_authenticator = $data['disable_authenticator'];
        $BounceServer->search_charset = $data['search_charset'];
        $BounceServer->delete_all_messages = $data['delete_all_messages'];
        $BounceServer->save();

        if($BounceServer->server_id<1)
        {
            return $this->respondWithError('There was an error, the bounce server was not updated.');
        }

        return $this->respond(['bounce_server_id' => $BounceServer->server_id]);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function destroy($id)
    {

        $BounceServer = BounceServer::find($id);

        if(empty($BounceServer))
        {
            return $this->respondWithError('Bounce server id not found');
        }

        $BounceServer->forceDelete();

        return $this->respond(['bounce_server_id' => $id]);

    }

}
