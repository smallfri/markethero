<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Create a Segment.');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');

$data =  <<<END
{
  "field_id": 1307,
  "list_id": 149,
  "name": "Segment Name",
  "operator_id": 9,
  "operator_match": "any",
  "value": "me@domain.com"
}
END;

$I->sendPOST('/v1/segment',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);