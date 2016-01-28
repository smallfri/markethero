<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * CommonHelper
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.1
 */
 
class CommonHelper 
{
    /**
     * CommonHelper::getQueriesFromSqlFile()
     * 
     * @param string $sqlFile
     * @param string $dbPrefix
     * @return array
     */
    public static function getQueriesFromSqlFile($sqlFile, $dbPrefix = null)
    {
        if (!is_file($sqlFile) || !is_readable($sqlFile)) {
            return array();
        }
        
        if (!empty($dbPrefix)) {
            $searchReplace = array(
                'CREATE TABLE IF NOT EXISTS `'  => 'CREATE TABLE IF NOT EXISTS `' . $dbPrefix, 
                'DROP TABLE IF EXISTS `'        => 'DROP TABLE IF EXISTS `' . $dbPrefix, 
                'INSERT INTO `'                 => 'INSERT INTO `' . $dbPrefix, 
                'ALTER TABLE `'                 => 'ALTER TABLE `' . $dbPrefix,
                'REFERENCES `'                  => 'REFERENCES `' . $dbPrefix,
                'UPDATE `'                      => 'UPDATE `' . $dbPrefix,
                'DELETE FROM `'                 => 'DELETE FROM `' . $dbPrefix,
            );  
            $search  = array_keys($searchReplace);
            $replace = array_values($searchReplace);  
        }

        $queries = array();
        $query   = '';
        $lines   = file($sqlFile);
        
        foreach ($lines as $line) {
            
            if (empty($line) || strpos($line, '--') === 0 || strpos($line, '#') === 0 || strpos($line, '/*!') === 0) {
                continue;
            }
            
            $query .= $line;
            
            if (!preg_match('/;\s*$/', $line)) {
                continue;
            }
            
            if (!empty($dbPrefix)) {
                $query = str_replace($search, $replace, $query);    
            }
            
            if (!empty($query)) {
                $queries[] = $query;
            }            
            
            $query = '';
        }
        
        return $queries;
    }
    
    /**
     * CommonHelper::functionExists()
     * 
     * @param string $name
     * @return bool
     */
    public static function functionExists($name) 
    {
        static $_exists     = array();
        static $_disabled   = null;
        static $_shDisabled = null;
        
        if (isset($_exists[$name]) || array_key_exists($name, $_exists)) {
            return $_exists[$name];
        }
        
        if (!function_exists($name)) {
            return $_exists[$name] = false;
        }
        
        if ($_disabled === null) {
            $_disabled = ini_get('disable_functions');
            $_disabled = explode(',', $_disabled);
            $_disabled = array_map('trim', $_disabled);
        }
        
        if (is_array($_disabled) && in_array($name, $_disabled)) {
            return $_exists[$name] = false;
        }
        
        if ($_shDisabled === null) {
            $_shDisabled = ini_get('suhosin.executor.func.blacklist');
            $_shDisabled = explode(',', $_shDisabled);
            $_shDisabled = array_map('trim', $_shDisabled);
        }
        
        if (is_array($_shDisabled) && in_array($name, $_shDisabled)) {
            return $_exists[$name] = false;
        }

        return $_exists[$name] = true;
    }
    
    /**
     * CommonHelper::findPhpCliPath()
     * 
     * @since 1.3.3.1
     * @return string
     */
    public static function findPhpCliPath()
    {
        static $cliPath;
        
        if ($cliPath !== null) {
            return $cliPath;
        }
        
        $cliPath = '/usr/bin/php';
        
        if (!self::functionExists('exec')) {
            return $cliPath;
        }
        
        $variants = array('php-cli', 'php5-cli', 'php5', 'php');
        foreach ($variants as $variant) {
            $out = @exec(sprintf('command -v %s 2>&1', $variant), $lines, $status);
            if ($status != 0 || empty($out)) {
                continue;
            }
            $cliPath = $out;
            break;
        }

        return $cliPath;
    }
    
    // since 1.3.5.6
    public static function getIpAddressInfoUrl($ipAddress)
    {
        $url = "http://who.is/whois-ip/ip-address/" . $ipAddress;
        return Yii::app()->hooks->applyFilters('get_ip_address_info_url', $url, $ipAddress);
    }
    
    // since 1.3.5.6
    public static function getUserAgentInfoUrl($userAgent)
    {
        $url = 'javascript:;';
        return Yii::app()->hooks->applyFilters('get_user_agent_info_url', $url, $userAgent);
    }
}