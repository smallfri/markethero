<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get a list.');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/list/customer/203/page/1/per_page/2');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);