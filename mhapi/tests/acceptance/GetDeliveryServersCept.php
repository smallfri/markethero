<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get delivery servers.');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/delivery-servers');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);