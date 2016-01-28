<?php
/**
 * This file contains the list segments endpoint for EmailOneApi PHP-SDK.
 * 
 *
 *
 *
 */
 
 
/**
 * EmailOneApi_Endpoint_ListSegments handles all the API calls for handling the list segments.
 * 
 *
 * @package EmailOneApi
 * @subpackage Endpoint
 * @since 1.0
 */
class EmailOneApi_Endpoint_ListSegments extends EmailOneApi_Base
{
    /**
     * Get segments from a certain mail list
     * 
     * Note, the results returned by this endpoint can be cached.
     * 
     * @param string $listUid
     * @param integer $page
     * @param integer $perPage
     * @return EmailOneApi_Http_Response
     */
    public function getSegments($listUid, $page = 1, $perPage = 10)
    {
        $client = new EmailOneApi_Http_Client(array(
            'method'        => EmailOneApi_Http_Client::METHOD_GET,
            'url'           => $this->config->getApiUrl(sprintf('lists/%s/segments', $listUid)),
            'paramsGet'     => array(
                'page'      => (int)$page, 
                'per_page'  => (int)$perPage
            ),
            'enableCache'   => true,
        ));
        
        return $response = $client->request();
    }
}