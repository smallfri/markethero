<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get all customers');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/customers');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);