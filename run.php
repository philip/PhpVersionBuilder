<?php
/**
 * PhpVersionBuilder : Downloads, extracts and builds [almost] all versions of PHP.
 *
 * @author Philip Olson <philip@php.net>
 * @license MIT license
 *
*/
set_time_limit(0);

define ('DIR_EXTRACTIONS',	'./extractions/');				// All extractions are done here
define ('DIR_DOWNLOADS',	'./downloads/');				// All downloads go in here
define ('DIR_BUILD_PREFIX',	'/home/philip/phpbuilds/');		// The builds (make install) go here
define ('PATH_TAR',			'');							// Optional, otherwise 'which tar' path is used
define ('DO_PHP_BUILD',		false);							// Whether do build PHP

$php_versions  = array('4', '5', '6');
$snap_versions = array('5.2', '5.3', '6.0');

require './lib/pvb-functions.php';

// Creates directories and checks prerequisites
initialize_environment();

// Get filenames, locations, and dates for each PHP version
$version_info = get_php_version_info($php_versions);

// Download PHP sources from php.net
download_php_sources($version_info, DIR_DOWNLOADS);

// Download latest snapshots from snaps.php.net
download_snap_sources($snap_versions, DIR_DOWNLOADS);

// Extract all tarballs from previous downloads
extract_php_sources(DIR_EXTRACTIONS, DIR_DOWNLOADS);

// Optionally build PHP
if (DO_PHP_BUILD) {
	$it = new FilesystemIterator(DIR_EXTRACTIONS);

	foreach ($it as $fileinfo) {

		// Rudimentary cache check
		if (file_exists(DIR_BUILD_PREFIX . $fileinfo->getFileName() . '/bin/php')) {
			continue;
		}

		// Rudimentary build system
		build_php($fileinfo->getPathName(), DIR_BUILD_PREFIX . $fileinfo->getFileName());
	}
}