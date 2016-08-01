<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Cet Blacklists');

$I->amHttpAuthenticated('russell@smallfri.com','KjV9g2JcyFGAHng');

$I->sendGET('/v1/blacklist');

$I->seeResponseIsJson();

$I->seeResponseContainsJson(
    array (
      'success' =>
      array (
        'blacklist' =>
        array (
          0 =>
          array (
            'email_id' => 5779203,
            'subscriber_id' => NULL,
            'customer_id' => NULL,
            'email' => 'rooster@smallfri.com',
            'reason' => 'smtp; 550 No Such User Here"',
            'date_added' => '2016-07-22 12:02:23',
            'last_updated' => '2016-07-22 12:02:23',
          ),
          1 =>
          array (
            'email_id' => 578000000,
            'subscriber_id' => NULL,
            'customer_id' => NULL,
            'email' => 'chuck@jvme.com',
            'reason' => 'smtp; 554 5.4.5 [internal] Delivery not attempted (message expired)',
            'date_added' => '2016-07-20 17:42:28',
            'last_updated' => '2016-07-20 17:42:28',
          ),
          2 =>
          array (
            'email_id' => 578000001,
            'subscriber_id' => NULL,
            'customer_id' => NULL,
            'email' => 'testingaccount5@mail.com',
            'reason' => 'smtp; 550 Requested action not taken: mailbox unavailable',
            'date_added' => '2016-07-24 12:42:13',
            'last_updated' => '2016-07-24 12:42:13',
          ),
          3 =>
          array (
            'email_id' => 578000002,
            'subscriber_id' => NULL,
            'customer_id' => NULL,
            'email' => 'testingaccount2@mail.com',
            'reason' => '',
            'date_added' => '2016-07-24 21:02:12',
            'last_updated' => '2016-07-24 21:02:12',
          ),
        ),
      ),
      'status_code' => 200,
    )

);