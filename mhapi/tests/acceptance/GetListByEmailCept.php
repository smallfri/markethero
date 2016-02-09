<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get a list.');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/list/customer/1/page/1/per_page/2');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);