<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Cet Spam');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/bounce/2008');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);