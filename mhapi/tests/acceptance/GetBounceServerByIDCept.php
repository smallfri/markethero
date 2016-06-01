<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get a bounce server by server id');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/bounce-servers/11');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['error' => 'Bounce Server not found',
  'status_code' => 400,
]);