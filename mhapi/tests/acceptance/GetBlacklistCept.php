<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Cet Blacklists');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$I->sendGET('/v1/blacklist');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);