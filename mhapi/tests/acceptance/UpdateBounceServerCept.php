<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Update a bounce server');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');
$data =  <<<END
{
  "customer_id": 11,
  "delete_all_messages": "no",
  "disable_authenticator": "",
  "email": "russell@smallfri.com",
  "hostname": "smpt.smallfri.com",
  "locked": "no",
  "password": "KjV9g2JcyFGAHng",
  "port": 22,
  "protocol": "ssl",
  "search_charset": "UTF-8",
  "service": "pop3",
  "status": "active",
  "username": "russell",
  "validate_ssl": "no"
}
END;

$I->sendPOST('/v1/bounce-servers',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);