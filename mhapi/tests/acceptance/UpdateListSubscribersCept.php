<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Update a subscriber.');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$data =  <<<END
{
  "email": "russel2332l@smallfri.com",
  "firstname": "russell",
  "lastname": "hudson update",
  "list_uid": "dv663ggbyx713"
}
END;

$I->sendPOST('/v1/subscriber/update',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);