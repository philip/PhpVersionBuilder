<?php
/**
 * PhpVersionBuilder run script
 *
 * @author Philip Olson <philip@roshambo.org>
 * @license MIT license
 * @link http://github.com/philip/PhpVersionBuilder
*/

require './inc/config.php';
require './inc/pvb-functions.php';

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

		$version = str_replace('php-', '', $fileinfo->getFileName());
		if (!isset($version_info[$version])) {
			echo "Found extractions directory for '$version' but skipping, it is not set within config php_versions", PHP_EOL;
			continue;
		}
		
		// Example prefix result: /path/to/phpbuilds/php-5.3.0
		$prefix = realpath(DIR_BUILD_PREFIX) . '/' . $fileinfo->getFileName();

		// Rudimentary cache check
		if (file_exists($prefix . '/bin/php')) {
			if (VERBOSE) {
				echo 'INFO: Already successfully built from: ', $fileinfo->getBaseName(), PHP_EOL;
			}
			continue;
		}
		
		// Optional version specific options, see inc/config.php
		if ($more_options = get_version_configs($config_options_versions, $fileinfo->getFileName())) {
			$config_options_run = array_merge($config_options_all, array($more_options));
		} else {
			$config_options_run = $config_options_all;
		}

		// Rudimentary build system
		build_php($fileinfo->getPathName(), $prefix, realpath(DIR_LOGS), $config_options_run);

		if (file_exists($prefix . '/bin/php')) {
			if (copy($prefix . '/bin/php', DIR_PHP_BINARIES . DIRECTORY_SEPARATOR . $fileinfo->getBaseName())) {
				chmod(DIR_PHP_BINARIES . DIRECTORY_SEPARATOR . $fileinfo->getBaseName(), 0755);
			}
		}
	}
	
	if (VERBOSE) {
		echo PHP_EOL;
		$status = get_status_binaries(DIR_BUILD_PREFIX);
		if ($status['bad']) {
			echo 'INFO: These PHP versions failed to build:', PHP_EOL;
			echo 'INFO: Check the logs for reasons why, as found in: ', DIR_LOGS, PHP_EOL;
			foreach ($status['bad'] as $version => $it) {
				echo "\t", $version, PHP_EOL;
			}
		}
		if ($status['good']) {
			echo 'INFO: These PHP versions built with success:', PHP_EOL;
			foreach ($status['good'] as $version => $it) {
				echo "\t", $version, PHP_EOL;
			}
		}
	}
}
