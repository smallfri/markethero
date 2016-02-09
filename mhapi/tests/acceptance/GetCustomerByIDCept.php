<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get customer by id');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/customer/email@domain.com');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);