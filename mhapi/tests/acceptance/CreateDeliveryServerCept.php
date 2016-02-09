<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Create a delivery server.');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$email = 'fake@'.uniqid().'.com';

$data =  <<<END
{
  "bounce_server_id": 3,
  "confirmation_key": "33784f1307ae8a837e3ea5639d553bb78d43640c",
  "customer_id": 1,
  "force_from": "never",
  "force_reply_to": "value",
  "from_email": "russell@smallfri.com",
  "from_name": "Russell",
  "hostname": "smpt.smallfri.com",
  "hourly_quota": 0,
  "locked": "no",
  "meta_data": "boo",
  "name": "test server",
  "password": "KjV9g2JcyFGAHng",
  "port": 22,
  "probability": 100,
  "protocol": "ssl",
  "reply_to_email": "russell@smallfri.com",
  "server_type": "smtp",
  "signing_enabled": "yes",
  "status": "active",
  "tracking_domain_id": 1,
  "use_for": "all",
  "use_queue": "no",
  "username": "russell"
}
END;

$I->sendPOST('/v1/delivery-server',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);