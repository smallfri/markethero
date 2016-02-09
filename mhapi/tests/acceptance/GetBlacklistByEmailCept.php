<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Cet Blacklists by email');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/blacklist/russell9@smallfri.com');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);