<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Create a bounce server');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/customer/email@domain.com');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);