<?php

$conf = new RdKafka\Conf();
$conf->set('security.protocol', 'plaintext');
$conf->set('broker.version.fallback', '0.8.2.1');

$rk = new RdKafka\Producer($conf);
$rk->setLogLevel(LOG_DEBUG);
//$rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");
$rk->addBrokers("zk-1.prod.markethero.io, zk-2.prod.markethero.io, zk-3.prod.markethero.io");



$topic = $rk->newTopic("email_one_email_to_be_sent");



for ($i = 0; $i < 10; $i++) {

    $message = [
        'reply_to_email' => 'russell@smallfri.com',
        "to_email"=>"smallfriinc@gmail.com",
        'from_email' => 'russell@smallfri.com',
        'to_name' => 'russell@smallfri.com',
        'reply_to_name' => 'russell@smallfri.com',
        'subject' => uniqid(),
        'send_at' => '2016-11-15 01:38:28',
        'id' => $i,
        'body' => uniqid(),
        'plain_text' => 'message',
        'customer_id' => 11,
        'group_id' => 40,
        'from_name' => 'Russell',


    ];


        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message));
            var_dump( json_encode($message));
}









?>