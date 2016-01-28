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

// CREATE THE ENDPOINT
$endpoint = new EmailOneApi_Endpoint_ListFields();

/*===================================================================================*/

// GET ALL ITEMS
$response = $endpoint->getFields('LIST-UNIQUE-ID');

// DISPLAY RESPONSE
echo '<pre>';
print_r($response->body);
echo '</pre>';