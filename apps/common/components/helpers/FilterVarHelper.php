<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * FilterVarHelper
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class FilterVarHelper
{
    /**
     * FilterVarHelper::filter()
     *
     * @param string $variable
     * @param int $filters
     * @return bool
     */
    public static function filter($variable, $filter = FILTER_DEFAULT, $options = array())
    {
        if (is_array($options) && isset($options['encodeIDN'])) {
            unset($options['encodeIDN']);
            $variable = self::encodeIDN($variable);
        }
        return filter_var($variable, $filter, $options);
    }

    /**
     * FilterVarHelper::email()
     *
     * @param string $email
     * @return bool
     */
    public static function email($email)
    {
        return self::filter($email, FILTER_VALIDATE_EMAIL, array('encodeIDN' => true));
    }

    /**
     * FilterVarHelper::url()
     *
     * @param string $url
     * @return bool
     */
    public static function url($url)
    {
        return self::filter($url, FILTER_VALIDATE_URL, array('encodeIDN' => true));
    }

    /**
     * FilterVarHelper::ip()
     *
     * @param string $ip
     * @return bool
     */
    public static function ip($ip)
    {
        return self::filter($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
	 * Converts given IDN to the punycode.
	 * @param string $value IDN to be converted.
	 * @return string resulting punycode.
	 */
	public static function encodeIDN($value)
	{
		if(preg_match_all('/^(.*)@(.*)$/', $value, $matches)) {
			if (CommonHelper::functionExists('idn_to_ascii')) {
				$value = $matches[1][0].'@'.idn_to_ascii($matches[2][0]);
			} else {
                if (!class_exists('Net_IDNA2', false)) {
				    require_once(Yii::getPathOfAlias('root.apps.common.framework.vendors.Net_IDNA2.Net') . DIRECTORY_SEPARATOR . 'IDNA2.php');
                }
                try {
                    $idna   = new Net_IDNA2();
    				$_value = $matches[1][0].'@'.@$idna->encode($matches[2][0]);
                    $value  = $_value;
                } catch (Exception $e) {

                }
			}
		}
		return $value;
	}
}
