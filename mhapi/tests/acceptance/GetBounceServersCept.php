<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get bounce servers');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/bounce-servers');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);