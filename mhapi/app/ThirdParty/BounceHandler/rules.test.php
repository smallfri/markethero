<?php
/**
 * This tests the corectness of rules!
 */
// Comment when needed.
exit('');

ini_set('display_errors', 1);
error_reporting(-1);

define('MW_PATH', true);
require_once dirname(__FILE__) . '/BounceHandler.php';

$rules  = require dirname(__FILE__) . '/rules.php';
$string = 'dummy string';

foreach ($rules[BounceHandler::COMMON_RULES] as $info) {
    foreach ($info['regex'] as $regex) {
        echo strtoupper($info['bounceType']) . " bounce testing for: {$regex} \n";
        preg_match($regex, $string, $matches);
    }
}