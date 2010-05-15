<?php
/*
	This outputs the major + minor + release for each PHP verision.
	Example: Outputs '5.3.3'	

	It's one method to test the colour coding
*/

// Code taken from the manual @ http://php.net/phpversion
// As these constants were introduced in PHP 5.2.8
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
if (PHP_VERSION_ID < 50207) {
    define('PHP_MAJOR_VERSION',   $version[0]);
    define('PHP_MINOR_VERSION',   $version[1]);
    define('PHP_RELEASE_VERSION', $version[2]);
}

echo PHP_MAJOR_VERSION, '.', PHP_MINOR_VERSION, '.', PHP_RELEASE_VERSION;
echo PHP_EOL;
