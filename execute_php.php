#!/usr/bin/env php
<?php
require './inc/config.php';
require './inc/pvb-functions.php';

if ($_SERVER['argc'] === 1) {
	echo 'Error: Pass in a PHP script to execute.' . PHP_EOL;
	echo 'Usage: ' . $_SERVER['argv'][0] . ' phpfile.php' . PHP_EOL;
	exit;
}

$test_script_name = realpath($_SERVER['argv'][1]);
if (!$test_script_name) {
	echo "Error: The test script [{$_SERVER['argv'][1]}] does not exist. Please try again." . PHP_EOL;
	exit;
}

$status = get_status_binaries(DIR_BUILD_PREFIX);

if (!count($status['good'])) {
	echo 'Could not find binaries to test against' . PHP_EOL;
	exit;
}

foreach ($status['good'] as $version => $it) {

	$out = run_shell_command(DIR_BUILD_PREFIX . $version . '/bin/php -f ' . $test_script_name);

	echo "Version: {$version}";
	echo PHP_EOL . '---------------------------------' . PHP_EOL;
	if ($out['stdout']) {
		echo $out['stdout'];
	} else {
		echo 'No output';
	}
	echo PHP_EOL . '---------------------------------' . PHP_EOL;
}
