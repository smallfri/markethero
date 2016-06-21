<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Create a customer');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$email = 'fake@'.uniqid().'.com';

$data =  <<<END
{
  "customer": {
    "confirm_email": "email@domain.com",
    "confirm_password": "password",
    "email": "$email",
    "fake_password": "password",
    "first_name": "sample name",
    "group_id": 1,
    "last_name": "sample last name",
    "timezone": "UTC"
  }
}
END;

$I->sendPOST('/v1/customer',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);