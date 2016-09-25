<?php

namespace App\Http\Controllers;

use App\Models\CampaignAbuseModel;
use App\Http\Requests;
use Aws\S3\S3Client;
use Illuminate\Queue\SqsQueue;
use Zend\Http\Response;
use App\Logger;

class SQSController extends ApiController
{

    public function index(){

        $s3 = new SqsQueue([
            'version' => 'latest',
            'region'  => 'us-east-1'
        ]);
    }


}
