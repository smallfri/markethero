<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get bounce servers');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/bounce-servers');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);