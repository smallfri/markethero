<?php
/**
 * This file contains examples for using the EmailOneApi PHP-SDK.
 *
 *
 *
 *
 */

// require the setup which has registered the autoloader
require_once dirname(__FILE__) . '/setup.php';

// create the lists endpoint:
$endpoint = new EmailOneApi_Endpoint_Customers();

$response = $endpoint->create(array(
    // required
    'customer' => array(
        'first_name'  => 'Rusell', // required
        'last_name'   => 'Hudson', // required
        'email'   => 'smallfriinc5@gmail.com', // required
        'confirm_email'   => 'smallfriinc5@gmail.com', // required
        'confirm_password'   => 'jack1999', // required
        'fake_password'   => 'jack1999', // required
        'group_id' => 1
    ),

));

// and get the response
echo '<pre>';
print_r($response->body);
echo '</pre>';
