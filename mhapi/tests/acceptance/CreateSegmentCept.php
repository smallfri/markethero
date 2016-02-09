<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Create a Segment.');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$data =  <<<END
{
  "field_id": 1,
  "list_id": 1,
  "name": "Segment Name",
  "operator_id": 9,
  "operator_match": "any",
  "value": "me@domain.com"
}
END;

$I->sendPOST('/v1/segment',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);