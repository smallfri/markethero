<?php

namespace App\Http\Controllers;

use App\EmailOne\Transformers\CustomerTransformer;
use App\SMTPServer;
use App\Http\Requests;
use Zend\Http\Response;

class DeliverServerController extends ApiController
{

    /**
     * SMTPController constructor.
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
    public function all()
    {

        $SMTPServer = SMTPServer::all();

        return $this->respond(['delivery_servers' => $SMTPServer->toArray()]);

    }

    /**
     * @return mixed
     */
    public function store()
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $PasswordsController = new PasswordsController();

        $Server = new SMTPServer();
        $Server->customer_id = $data['customer_id'];
        $Server->bounce_server_id = $data['bounce_server_id'];
        $Server->tracking_domain_id = $data['tracking_domain_id'];
        $Server->type = $data['server_type'];
        $Server->name = $data['name'];
        $Server->hostname = $data['hostname'];
        $Server->username = $data['username'];
        $Server->password = $PasswordsController->makePassword($data['password']);
        $Server->port = $data['port'];
        $Server->protocol = $data['protocol'];
        $Server->from_email = $data['from_email'];
        $Server->from_name = $data['from_name'];
        $Server->reply_to_email = $data['reply_to_email'];
        $Server->probability = $data['probability'];
        $Server->hourly_quota = $data['hourly_quota'];
        $Server->meta_data = $data['meta_data'];
        $Server->confirmation_key = $data['confirmation_key'];
        $Server->locked = $data['locked'];
        $Server->use_for = $data['use_for'];
        $Server->use_queue = $data['use_queue'];
        $Server->signing_enabled = $data['signing_enabled'];
        $Server->force_from = $data['force_from'];
        $Server->force_reply_to = $data['force_reply_to'];
        $Server->status = $data['status'];
        $Server->save();

        return $this->respond(['delivery_server_id' => $Server->server_id]);

    }

    public function show($id)
    {

        $SMTPServer = SMTPServer::find($id);

        return $this->respond(['delivery_server' => $SMTPServer->toArray()]);

    }

    public function update($id)
    {

        $data = json_decode(file_get_contents('php://input'), true);

        $Server = SMTPServer::find($id);
        $Server->customer_id = $data['customer_id'];
        $Server->tracking_domain_id = $data['tracking_domain_id'];
        $Server->type = $data['server_type'];
        $Server->name = $data['name'];
        $Server->hostname = $data['hostname'];
        $Server->username = $data['username'];
        $Server->port = $data['port'];
        $Server->protocol = $data['protocol'];
        $Server->from_email = $data['from_email'];
        $Server->from_name = $data['from_name'];
        $Server->reply_to_email = $data['reply_to_email'];
        $Server->probability = $data['probability'];
        $Server->hourly_quota = $data['hourly_quota'];
        $Server->meta_data = $data['meta_data'];
        $Server->confirmation_key = $data['confirmation_key'];
        $Server->locked = $data['locked'];
        $Server->use_for = $data['use_for'];
        $Server->use_queue = $data['use_queue'];
        $Server->signing_enabled = $data['signing_enabled'];
        $Server->force_from = $data['force_from'];
        $Server->force_reply_to = $data['force_reply_to'];
        $Server->status = $data['status'];
        $Server->save();

        return $this->respond(['delivery_server_id' => $Server->server_id]);

    }

    public function destroy($id)
    {
        $SMTPServer = SMTPServer::find($id);

        $SMTPServer->forceDelete();

        return $this->respond(['delivery_server_id' => $id]);

    }
}
