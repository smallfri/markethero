<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Create a Group');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');
$data =  <<<END
 {
   "customer_id": 11
 }
END;

$I->sendPOST('/v1/create-group-email-group',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);