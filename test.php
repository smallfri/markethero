<?php

require_once dirname(__FILE__) . '/emailone-php-sdk/EmailONE/Autoloader.php';

// register the autoloader.
MailWizzApi_Autoloader::register();


$config = new MailWizzApi_Config(array(
    'apiUrl'        => 'http://deva.emailone.net/api',
    'publicKey'     => $_POST['public_key'],
    'privateKey'    => $_POST['private_key'],

    // components
    'components' => array(
        'cache' => array(
            'class'     => 'MailWizzApi_Cache_File',
            'filesPath' => dirname(__FILE__) . '/../MailWizzApi/Cache/data/cache', // make sure it is writable by webserver
        )
    ),
));

// now inject the configuration and we are ready to make api calls
MailWizzApi_Base::setConfig($config);

// and if it is and we have post values, then we can proceed in sending the subscriber.
if (!empty($_POST)) {

    $listUid    = $_POST['list_id'];// you'll take this from your customers area, in list overview from the address bar.
    $endpoint   = new MailWizzApi_Endpoint_ListSubscribers();

    $response   = $endpoint->create($listUid, array(
        'EMAIL' => isset($_POST['EMAIL']) ? $_POST['EMAIL'] : null,
        'FNAME' => isset($_POST['FNAME']) ? $_POST['FNAME'] : null,
        'LNAME' => isset($_POST['LNAME']) ? $_POST['LNAME'] : null,
    ));
    $response   = $response->body;

    // if the returned status is success, we are done.
    if ($response->itemAt('status') == 'success') {
        exit(MailWizzApi_Json::encode(array(
            'status'    => 'success',
            'message'   => 'Thank you for joining our email list. Please confirm your email address now!'
        )));
    }
    
    // otherwise, the status is error
    exit(MailWizzApi_Json::encode(array(
        'status'    => 'error',
        'message'   => $response->itemAt('error')
    )));
}
?>