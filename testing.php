<?php
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "http://m-staging.markethero.io/mhapi/v1/customer/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);

curl_setopt($ch, CURLOPT_POST, TRUE);

curl_setopt($ch, CURLOPT_POSTFIELDS, "{
  \"customer\": {
    \"first_name\": \"sample name\",
    \"last_name\": \"sample last name\",
    \"email\": \"email@domain.com\",
    \"confirm_email\": \"email@domain.com\",
    \"confirm_password\": \"password\",
    \"fake_password\": \"password\",
    \"group_id\": 1
  }
}");

//curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//  "username:russell@smallfri.com",
//  "password:KjV9g2JcyFGAHng",
//  "Content-Type: application/json"
//));

curl_setopt($ch, CURLOPT_USERPWD, "russell@smallfri.com" . ":" . 'KjV9g2JcyFGAHng');


$response = curl_exec($ch);
curl_close($ch);

var_dump($response);