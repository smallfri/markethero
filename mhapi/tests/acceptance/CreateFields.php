<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Crate a field.');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$data =  <<<END
{
  "default_value": "A default value",
  "help_text": "Help Text",
  "label": "Some Label",
  "list_id": 149,
  "required": "yes",
  "sort_order": 10,
  "tag": "some label tag",
  "type_id": 1,
  "visibility": "yes"
}
END;

$I->sendPUT('/v1/fields',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);