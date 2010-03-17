<?php

// Where PHP tarballs are downloaded to
define ('DIR_DOWNLOADS', './downloads/');

// Where PHP sources will be extracted (and built)
define ('DIR_EXTRACTIONS', './extractions/');

// The root --prefix for PHP builds.
// Ex: 'foo' will use --prefix=foo/php-5-3-0/ as the PHP 5.3.0 prefix
define ('DIR_BUILD_PREFIX', './phpbuilds/');

// Where all the logs (like from configure, make, etc.) are stored
define ('DIR_LOGS', './logs/');

// Optionally define the tar location, otherwise 'which tar' is used
define ('PATH_TAR', '');

// True to configure/make PHP, otherwise only download/extract
define ('DO_PHP_BUILD', true);

// True to output information as it happens
define ('VERBOSE', true);

// Options passed before ./configure (Ex: for LIBS, CFLAGS, etc.)
define ('PRE_CONFIGURE', '');

// Includes all versions greater than defined values, separated by major version
// Ex: '4', '5' === all PHP 4's and 5's. '5.2.4' === All 5 versions >= 5.2.4
$php_versions  = array('5.2.8');

// Which snaps to download/build
$snap_versions = array('5.3');

// Options passed to ./configure
// --prefix is already set, and based on the php version
$config_options = array(
	'--disable-all', 
	'--disable-cgi', 
	'--enable-cli',
);

// TODO: Add version specific options? 
$config_options_versions = array(
	'5.2.0+' => '--with-foo',
	'5.3.1'  => '--disable-bar',
}
