<?php
/**
 * Created by PhpStorm.
 * User: Russ
 * Date: 1/26/16
 * Time: 5:20 PM
 */

namespace App\Http\Controllers;

class ApiController extends Controller
{

    protected $statusCode = 200;

    public function respondNotFound($message = 'Not Found!')
    {

        return $this->setStatusCode(404)->respondWithError($message);

    }

    public function respondWithError($message)
    {

        return \Response::json(['error' => $message,'status_code' => 400]);
    }

    public function getStatusCode()
    {

        return $this->statusCode;
    }

    public function setStatusCode($statusCode)
    {

        $this->statusCode = $statusCode;

        return $this;
    }

    public function respondInternalError($message = 'Internal Error')
    {

        return $this->setStatusCode(500)->respondWithError($message);

    }

    public function respond($data,$headers = [])
    {

        return \Response::json(['success' => $data, 'status_code' => 200]);

    }

}