<?php


for($i = 0; $i < 100; ++$i){
    $ch = curl_init('http://m-staging.markethero.io/mhapi/v1/create-group-email');
      curl_setopt($ch, CURLOPT_USERPWD, "noreply@markethero.io:KjV9g2JcyFGAHng");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        $data_string
            = '{
          "body": "This is only a test. Newer",
          "customer_id": 11,
          "from_email": "russell@smallfri.com",
          "from_name": "Bob",
          "group_id": 57,
          "plain_text": "test",
          "reply_to_email": "noreply@smallfri.com",
          "reply_to_name": "Bob",
          "send_at": "2016-02-05 14:33:00",
          "subject": "Newest subject test #0'.$i.'",
          "to_email": "russell@smallfri.com",
          "to_name": "Russell"
    }';

    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

      $ret = curl_exec($ch);
      $result = json_decode($ret,true);

      echo $result['someData'] . ": ".$i."<br>";
      curl_close($ch);

}