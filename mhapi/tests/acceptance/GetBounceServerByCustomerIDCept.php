<?php 
$I = new AcceptanceTester($scenario);

return;
$I->wantTo('Get bounce server by customer id.');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/bounce-server/203');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);