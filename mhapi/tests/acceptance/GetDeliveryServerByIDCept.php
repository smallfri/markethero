<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get delivery server by id.');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/delivery-servers/5');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);