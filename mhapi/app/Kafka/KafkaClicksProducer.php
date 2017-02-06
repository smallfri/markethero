<?php

$conf = new RdKafka\Conf();
$conf->set('security.protocol', 'plaintext');
$conf->set('broker.version.fallback', '0.8.2.1');

$rk = new RdKafka\Producer($conf);
$rk->setLogLevel(LOG_DEBUG);
//$rk->addBrokers("kafka-3.int.markethero.io, kafka-2.int.markethero.io,kafka-1.int.markethero.io");
$rk->addBrokers("zk-1.prod.markethero.io, zk-2.prod.markethero.io, zk-3.prod.markethero.io");



$topic = $rk->newTopic("email_one_click_email_events");



for ($i = 1; $i < 10; $i++) {

//    {"@type":"email-one-click-email-event","clickedIP":"0:0:0:0:0:0:0:1","clickedDate":1481392207803,"externalId":"5b1758e2721f928ad726eebf8819555bb7d370436a58725431f7df9e53e162b1","emailOneId":"5304c9e9-aba7-486d-8f6a-3f89bb593d82","groupId":154}


    $message = [
        'clickedIP' => '0:0:0:0:0:0:0:1',
        "clickedDate"=>"1481392207803",
        'externalId' => uniqid(),
        'emailOneId' => uniqid(),
        'group_id' => 40,
    ];


        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message));
            var_dump( json_encode($message));
}









?>