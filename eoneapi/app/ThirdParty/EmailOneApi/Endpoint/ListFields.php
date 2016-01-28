<?php
/**
 * This file contains the lists fields endpoint for EmailOneApi PHP-SDK.
 * 
 *
 *
 *
 */
 
 
/**
 * EmailOneApi_Endpoint_ListFields handles all the API calls for handling the list custom fields.
 * 
 *
 * @package EmailOneApi
 * @subpackage Endpoint
 * @since 1.0
 */
class EmailOneApi_Endpoint_ListFields extends EmailOneApi_Base
{
    /**
     * Get fields from a certain mail list
     * 
     * Note, the results returned by this endpoint can be cached.
     * 
     * @param string $listUid
     * @return EmailOneApi_Http_Response
     */
    public function getFields($listUid)
    {
        $client = new EmailOneApi_Http_Client(array(
            'method'        => EmailOneApi_Http_Client::METHOD_GET,
            'url'           => $this->config->getApiUrl(sprintf('lists/%s/fields', $listUid)),
            'paramsGet'     => array(),
            'enableCache'   => true,
        ));
        
        return $response = $client->request();
    }
}