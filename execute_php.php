#!/usr/bin/env php
<?php
require './inc/config.php';
require './inc/pvb-functions.php';

// FIXME: Use getopt() I suppose
if ($_SERVER['argc'] === 1) {
	echo 'Error: Pass in a PHP script to execute.' . PHP_EOL;
	echo 'Usage: ' . $_SERVER['argv'][0] . ' phpfile.php [mode=html|stdout]' . PHP_EOL;
	exit;
}
$mode = DEFAULT_OUTPUT_TYPE;
if (!empty($_SERVER['argv'][2]) && strtolower($_SERVER['argv'][2]) === 'html') {
	$mode = 'html';
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

$text = array();
foreach ($status['good'] as $version => $it) {

	$out = run_shell_command(DIR_BUILD_PREFIX . $version . '/bin/php -f ' . $test_script_name);

	if ($out['stdout']) {
		$text[$version] = $out['stdout'];
	} else {
		$text[$version] = 'No output';
	}
}

uksort($text, 'strnatcmp');

// FIXME: print_r() isn't ideal here
if ($mode === 'stdout') {
	print_r($text);
	exit;
}

// Colours defined in inc/config.php
$uniques        = array_unique($text);
$colour_matches = array_combine(array_slice($colours, 0, count($uniques)), $uniques);

// FIXME: Better HTML (tables?)
echo '<table border="1">', PHP_EOL;
foreach ($text as $version => $output) {
	$keys = array_keys($colour_matches, $output, TRUE);
	if (empty($keys[0])) {
		echo 'ERROR: Very bad.', PHP_EOL;
		exit;
	}
	$bgcolor = '#' . $keys[0];

	echo '<tr bgcolor="', $bgcolor, '"><td valign="top">', $version, '</td><td>', htmlentities($output, ENT_QUOTES, 'UTF-8'), '</td></tr>', PHP_EOL;
}
echo '</table>';
