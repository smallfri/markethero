<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Create a campaign');

$I->amHttpAuthenticated('russell@smallfri.com','jack1999');
$data =  <<<END
{
  "autoresponder_event": "AFTER-SUBSCRIBE",
  "autoresponder_open_campaign_id": "null",
  "autoresponder_time_unit": "hour",
  "autoresponder_time_value": 1,
  "email_stats_address": "russell@smallfri.com",
  "from_email": "Russell@smallfri.com",
  "fromname": "Russell",
  "inline_css": "yes",
  "json_feed": "yes",
  "list_uid": "ed3493rvea4e6",
  "name": "My New Campaign",
  "plain_text_email": "yes",
  "reply_to": "noreply@smallfri.com",
  "segment_uid": "",
  "send_at": "2016-02-01 08:54:00",
  "subject": "This rocks!",
  "template_url": "<!DOCTYPE html> <html><head><meta name=\"charset\" content=\"utf-8\"><title></title></head><body>[UNSUBSCRIBE_URL], [COMPANY_FULL_ADDRESS]                              testing 1<br><br><a href=\"http://www.google.com\">Test</a><br><br> chuck Mullaney<br><br><br><br><br> &nbsp;</body></html> ",
  "type": "regular",
  "url_tracking": "yes",
  "xml_feed": "no"
}
END;

$I->sendPOST('/v1/campaign',$data);

$I->seeResponseIsJson();

$I->seeResponseContainsJson(['status_code'=>'200']);