<?php
/**
 * PhpVersionBuilder : Downloads, extracts and builds [almost] all versions of PHP.
 *
 * @author Philip Olson <philip@php.net>
 * @license MIT license
 *
*/
set_time_limit(0);

require './inc/config.php';
require './inc/pvb-functions.php';

if (VERBOSE) {
	// Show all errors
	ini_set('display_errors', 1);
	error_reporting(-1);
}

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

		// Example prefix result: /path/to/phpbuilds/php-5.3.0
		$prefix = realpath(DIR_BUILD_PREFIX) . '/' . $fileinfo->getFileName();

		// Rudimentary cache check
		if (file_exists($prefix . '/bin/php')) {
			if (VERBOSE) {
				echo "INFO: Already successfully built from: " . $fileinfo->getBaseName() . "\n";
			}
			continue;
		}

		// Rudimentary build system
		build_php($fileinfo->getPathName(), $prefix, realpath(DIR_LOGS), $config_options);
	}
}
