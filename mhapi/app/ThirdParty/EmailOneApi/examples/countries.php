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
$endpoint = new EmailOneApi_Endpoint_Countries();

/*===================================================================================*/

// GET ALL ITEMS
$response = $endpoint->getCountries($pageNumber = 23, $perPage = 10);

// DISPLAY RESPONSE
echo '<pre>';
print_r($response->body);
echo '</pre>';

/*===================================================================================*/

// get country zones
$response = $endpoint->getZones(223, $pageNumber = 1, $perPage = 10);

// DISPLAY RESPONSE
echo '<hr /><pre>';
print_r($response->body);
echo '</pre>';