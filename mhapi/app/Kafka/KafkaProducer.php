<?php

$conf = new RdKafka\Conf();
$conf->set('security.protocol', 'plaintext');
$conf->set('broker.version.fallback', '0.8.2.1');

$rk = new RdKafka\Producer($conf);
$rk->setLogLevel(LOG_DEBUG);
$rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");



$topic = $rk->newTopic("email_one_email_to_be_sent");



for ($i = 1; $i < 2; $i++) {
    $message = '{
    "reply_to_email":"admin@markethero.io",
    "to_email":"russell@smallfri.com",
    "from_email":"admin@markethero.io",
    "plain_text":"content",
    "to_name":"esteban@gmail.com",
    "reply_to_name":"Admin Admin",
    "subject":"Subject",
    "send_at":"2016-11-15 01:38:28",
    "id":25,
    "body":"message"
    "customer_id":11,
    "from_name":"Admin User"
    }';

    $digits = 3;


    $message = [
        'reply_to_email' => 'russell@smallfri.com',
        'to_email' => 'russell@smallfri.com',
        'from_email' => 'russell@smallfri.com',
        'to_name' => 'russell@smallfri.com',
        'reply_to_name' => 'russell@smallfri.com',
        'subject' => 'testing testing testing',
        'send_at' => '2016-11-15 01:38:28',
        'id' => rand(pow(10, $digits-1), pow(10, $digits)-1),
        'body' => uniqid(),
        'plain_text' => 'message',
        'customer_id' => 11,
        'from_name' => 'Russell',


    ];

    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message));
    var_dump( json_encode($message));
}









?>