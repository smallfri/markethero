<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Get a bounce server by server id');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/bounce-servers/2');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(
   array (
      'success' =>
      array (
        'bounce_server' =>
        array (
          'server_id' => 2,
          'customer_id' => '11',
          'hostname' => 'mail.marketherobounce1.com',
          'username' => 'bounces',
          'password' => 'BsJcnB?y4x',
          'email' => 'bounces@marketherobounce1.com',
          'service' => 'pop3',
          'port' => '110',
          'protocol' => 'notls',
          'validate_ssl' => 'no',
          'locked' => 'no',
          'disable_authenticator' => '',
          'search_charset' => 'UTF-8',
          'delete_all_messages' => 'no',
          'status' => 'active',
          'date_added' => '0000-00-00 00:00:00',
          'last_updated' => '2016-06-05 21:29:43',
        ),
      ),
      'status_code' => 200,
    )
);