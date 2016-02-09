<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Update a subscriber.');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$data =  <<<END
{
  "email": "chuck.mullaney@gmail.com",
  "firstname": "russell",
  "lastname": "hudson update",
  "list_uid": "vx819px7esd38"
}
END;

$I->sendPOST('/v1/subscriber/update',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);