<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Create a subscriber.');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$email = 'me@'.uniqid().'domain.com';

$data =  <<<END
{
  "email": "$email",
  "firstname": "russell",
  "lastname": "hudson update",
  "list_uid": "dv663ggbyx713"
}
END;

$I->sendPOST('/v1/subscriber',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);