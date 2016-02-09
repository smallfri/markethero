<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get all customers');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/customers');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);