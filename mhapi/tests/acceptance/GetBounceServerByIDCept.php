<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get a bounce server by server id');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/bounce-servers/11');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);