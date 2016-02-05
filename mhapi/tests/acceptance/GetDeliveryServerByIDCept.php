<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get delivery servers.');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/delivery-servers/1');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);