<?php
/**
 * This file contains the transactional emails endpoint for EmailOneApi PHP-SDK.
 * 
 *
 *
 *
 */
 
 
/**
 * EmailOneApi_Endpoint_TransactionalEmails handles all the API calls for transactional emails.
 * 
 *
 * @package EmailOneApi
 * @subpackage Endpoint
 * @since 1.0
 */
class EmailOneApi_Endpoint_TransactionalEmails extends EmailOneApi_Base
{
    /**
     * Get all transactional emails of the current customer
     * 
     * Note, the results returned by this endpoint can be cached.
     * 
     * @param integer $page
     * @param integer $perPage
     * @return EmailOneApi_Http_Response
     */
    public function getEmails($page = 1, $perPage = 10)
    {
        $client = new EmailOneApi_Http_Client(array(
            'method'        => EmailOneApi_Http_Client::METHOD_GET,
            'url'           => $this->config->getApiUrl('transactional-emails'),
            'paramsGet'     => array(
                'page'      => (int)$page, 
                'per_page'  => (int)$perPage
            ),
            'enableCache'   => true,
        ));
        
        return $response = $client->request();
    }
    
    /**
     * Get one transactional email
     * 
     * Note, the results returned by this endpoint can be cached.
     * 
     * @param string $emailUid
     * @return EmailOneApi_Http_Response
     */
    public function getEmail($emailUid)
    {
        $client = new EmailOneApi_Http_Client(array(
            'method'        => EmailOneApi_Http_Client::METHOD_GET,
            'url'           => $this->config->getApiUrl(sprintf('transactional-emails/%s', (string)$emailUid)),
            'paramsGet'     => array(),
            'enableCache'   => true,
        ));
        
        return $response = $client->request();
    }
    
    /**
     * Create a new transactional email
     * 
     * @param array $data
     * @return EmailOneApi_Http_Response
     */
    public function create(array $data)
    {
        var_dump($data);
        if (!empty($data['body'])) {
            $data['body'] = base64_encode($data['body']);
        }
        
        if (!empty($data['plain_text'])) {
            $data['plain_text'] = base64_encode($data['plain_text']);
        }
        
        $client = new EmailOneApi_Http_Client(array(
            'method'        => EmailOneApi_Http_Client::METHOD_POST,
            'url'           => $this->config->getApiUrl('transactional-emails'),
            'paramsPost'    => array(
                'email'  => $data
            ),
        ));
        
        return $response = $client->request();
    }
    
    /**
     * Delete existing transactional email
     * 
     * @param string $emailUid
     * @return EmailOneApi_Http_Response
     */
    public function delete($emailUid)
    {
        $client = new EmailOneApi_Http_Client(array(
            'method'    => EmailOneApi_Http_Client::METHOD_DELETE,
            'url'       => $this->config->getApiUrl(sprintf('transactional-emails/%s', $emailUid)),
        ));
        
        return $response = $client->request();
    }
}