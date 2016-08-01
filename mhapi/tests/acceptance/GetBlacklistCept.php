<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Cet Blacklists');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/blacklist');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['success']);