<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Create a Group email');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');
$data =  <<<END
{
  "body": "This is only a test. you will be informed later. ",
  "customer_id": 1,
  "from_email": "russell@smallfri.com",
  "from_name": "Bob",
  "plain_text": "test",
  "reply_to_email": "noreply@smallfri.com",
  "reply_to_name": "Bob",
  "send_at": "2016-02-05 14:33:00",
  "subject": "This is a test",
  "to_email": "russell@smallfri.com",
  "to_name": "Russell",
  "group_id" : 11
}
END;

$I->sendPOST('/v1/create-group-email',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);