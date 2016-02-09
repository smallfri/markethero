<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Cet Spam');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/spam');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);