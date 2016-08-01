<?php 
$I = new AcceptanceTester($scenario);
return;
$I->wantTo('Create a list.');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$data =  <<<END
{
  "address_1": "100 Some Street",
  "address_2": "value",
  "city": "Myrtle Beach",
  "company_name": "Russells co",
  "country": "US",
  "customer_id": 11,
  "description": "russells new list",
  "from_email": "russell@smallfri.com",
  "from_name": "Russells Place",
  "name": "Russells list 2",
  "reply_to": "russell@smallfri.com",
  "subject": "Don't Miss This!",
  "subscribe": "yes",
  "subscribe_to": "russell@smallfri.com",
  "unsubscribe": "no",
  "unsubscribe_to": "russell@smallfri.com",
  "zip_code": 29588,
  "zone": "South Carolina"
}
END;

$I->sendPUT('/v1/list/vx819px7esd38',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);