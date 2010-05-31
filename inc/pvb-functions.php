<?php
/**
 * PhpVersionBuilder function library
 *
 * @author Philip Olson <philip@roshambo.org>
 * @license MIT license
 * @link http://github.com/philip/PhpVersionBuilder
*/

/**
 * Get information about PHP releases, including filenames and version numbers
 */
function get_php_version_info($php_versions) {
	
	$data = array();
	foreach ($php_versions as $php_version) {

		$php_version_major = $php_version{0};

		if (!is_numeric($php_version_major)) {
			trigger_error("Configured with invalid version information: $php_version", E_USER_ERROR);
		}

		$ver_url  = 'http://' . choose_random_mirror() . '/releases/index.php?serialize=1&version=' . $php_version_major . '&max=42';
		$versions = unserialize(file_get_contents($ver_url));
		
		if (isset($versions['error'])) {
			continue;
		}

		if (!is_array($versions) || count($versions) < 1) {
			trigger_error("Unable to locate PHP versions for version: [$php_version]", E_USER_ERROR);
		}
		
		$count = 0;
		foreach ($versions as $version => $vinfo) {
			
			if (version_compare($version, $php_version, '<')) {
				continue;
			}

			//FIXME: Note: Not all PHP versions have [the smaller] .bz2
			//FIXME: Will this check always work?
			$filename = $vinfo['source'][0]['filename'];
			$md5hash  = isset($vinfo['source'][0]['md5']) ? $vinfo['source'][0]['md5'] : '';
			if (false === strpos($filename, 'tar.gz')) {
				$filename = $vinfo['source'][1]['filename'];
				$md5hash  = isset($vinfo['source'][1]['md5']) ? $vinfo['source'][1]['md5'] : '';
			}

			$data[$version] = array(
				'date'		=> trim($vinfo['date']),
				'md5hash'	=> trim($md5hash),
				'filename'	=> trim($filename),
				'museum'	=> (array_key_exists('museum', $vinfo) ? $vinfo['museum'] : false),
			);
			$count++;
		}
		if (VERBOSE) {
			echo "INFO: Found $count downloads for version $php_version_major ($php_version or greater)", PHP_EOL;
		}
	}
	return $data;
}

/**
 * Initialize the script.
 */
function initialize_environment() {

	if (version_compare(PHP_VERSION, '5.2.0', '<')) {
		trigger_error('PHP version 5.2.0 or greater is required. Sorry.', E_USER_ERROR);
		return false;
	}

	$dirs = array(DIR_DOWNLOADS, DIR_EXTRACTIONS, DIR_LOGS, DIR_BUILD_PREFIX);

	foreach ($dirs as $dir) {
		if (!is_dir($dir)) {
			if (!mkdir($dir)) {
				trigger_error("Unable to create the directory: [$dir]", E_USER_ERROR);
				return false;
			} else {
				if (VERBOSE) {
					echo "INFO: Created directory: $dir", PHP_EOL;
				}
			}
		}
		if (!is_readable($dir) || !is_writable($dir)) {
			trigger_error("Unable read and write to the directory: [$dir]", E_USER_ERROR);
			return false;
		}
	}
	
	//FIXME: Making a poor assumption about gunzip existing, so deal with that (maybe gunzip |tar xf) (thanks hannes)
	//FIXME: Make a native PHP version available (see gzopen and streamwrapper.dir-readdir docs) (thanks hannes)
	//FIXME: Define final value for these locations, perhaps as a constant or as helper functions (ex. get_path_tar())
	if (constant('PATH_TAR')) {
		if (!file_exists(PATH_TAR)) {
			trigger_error("Unable to locate the defined PATH_TAR command: [" . PATH_TAR . "]", E_USER_ERROR);
			return false;
		}
	} else {
		$tar = run_shell_command('which tar');
		if (false === strpos($tar['stdout'], '/tar')) {
			trigger_error("Unable to locate the tar command, needed for file extraction", E_USER_ERROR);
			return false;
		}
	}
	if (VERBOSE) {
		echo "INFO: Found tar here: {$tar['stdout']}", PHP_EOL;
	}
	
	if (ini_get('allow_url_fopen') == 0) {
		trigger_error("Need allow_url_fopen enabled, to download PHP sources from php.net", E_USER_ERROR);
		return false;
	}
	return true;
}

/**
 * Extract a directory of .tar.gz files
 */
function extract_php_sources($extractpath, $sourcepath) {

	$it = new FilesystemIterator($sourcepath);

	if (VERBOSE) {
		echo PHP_EOL, 'INFO: Extracting PHP sources now.', PHP_EOL;
	}
	foreach ($it as $fileinfo) {
		$filepath = $fileinfo->getPathName();

		//FIXME: Snapshot extraction check appears a bit more tricky, with timestamped dirs (fix)
		//FIXME: Is this check always going to work for regular downloads?
		// Checks if version previously extracted
		if (is_dir($extractpath . str_replace('.tar.gz', '', $fileinfo->getFileName()))) {
			if (VERBOSE) {
				echo "INFO: Already extracted $filepath", PHP_EOL;
			}
			continue;
		}
		
		$tar = constant('PATH_TAR') ? PATH_TAR : 'tar';

		//FIXME: Research compatability with various sytems
		$command = "$tar xfvz $filepath -C $extractpath";
		$out     = run_shell_command($command);
		if (!empty($out['stderr'])) {
			trigger_error("Unable to untar file. Command ($command) Error: ({$out['stderr']}", E_USER_ERROR);
			return false;
		}
	}
	return true;
}

/**
 * Download the PHP sources from php.net
 */

function download_php_sources ($versions, $path = 'downloads') {

	foreach ($versions as $version => $vinfo) {
		//FIXME: Add real (and working) logging mechanism
		if (empty($vinfo['filename'])) {
			$log['no_filename'][] = $vinfo;
			continue;
		}

		$filename = $vinfo['filename'];
		$filepath = $path . '/' . $filename;
		//FIXME: Add md5_file() check here
		if (file_exists($filepath)) {
			if (VERBOSE) {
				echo "INFO: Already downloaded: {$vinfo['filename']}", PHP_EOL;
			}
			continue;
		}

		//FIXME: Do a URL check here (for 404, etc)
		if (empty($vinfo['museum'])) {
			$link = 'http://' . choose_random_mirror() . '/distributions/' . $filename;
		} else {
			$link = 'http://museum.php.net/php' . $version[0] . '/' . $filename;
		}

		//FIXME: Test with older PHP's as md5 hashes aren't always available
		if (download_file($link, $filepath, $vinfo['md5hash'])) {
			echo " ... finished downloading $filename." . PHP_EOL;
		} else {
			echo "ERROR: Unable to download from link ($link) to filepath ($filepath).", PHP_EOL;
		}
	}
	return true;
}

/**
 * Download the PHP snapshots from snaps.php.net
 */
function download_snap_sources ($versions, $path = 'downloads') {

	foreach ($versions as $version) {
		$filename = 'php' . $version . '-latest.tar.gz';
		$filepath = $path . '/' . $filename;
		
		if (file_exists($filepath)) {
			continue;
		}

		//FIXME: Do a URL check here (for 404, etc)
		$link = 'http://snaps.php.net/' . $filename;

		//FIXME: copy() here? Consider alternative approaches
		if (copy($link, $filepath)) {
			if (VERBOSE) {
				echo "INFO: Downloaded snapshot for version: $version", PHP_EOL;
			}
		}
	}
	return true;
}

/**
 * FIXME: Add better logging, error handling, configure management, etc.
 * FIXME: Document what is needed, especially with older PHP versions
 * FIXME: Allow custom environments, like old bison for old PHP versions
 * Build a PHP version
 */
function build_php ($phpdir, $prefix, $logpath, $config_options) {
	
	$logbase = $logpath . '/' . basename($phpdir);
	
	$preconfig = '';
	if (PRE_CONFIGURE) {
		$preconfig = PRE_CONFIGURE . ' ';
	}
	
	$commands = array(
		'configure'		=> "{$preconfig}./configure --prefix=$prefix " . implode(' ', $config_options),
		'make'			=> "make",
		'make-install'	=> "make install",
	);
	
	if (VERBOSE) {
		echo "INFO: Building $phpdir with prefix $prefix", PHP_EOL;
	}
	
	foreach ($commands as $command_name => $command) {

		// TODO: Add error checking/reporting. E.g., if configure failed, say so.
		if (VERBOSE) {
			echo 'INFO: Running ', $command_name,' now', PHP_EOL;
			if ($command_name === 'configure') {
				echo 'INFO: Command: ', $command, PHP_EOL;
			}
		}

		$descriptors = array(
			0 => array('pipe', 'r'), // stdin
			1 => array('file', "{$logbase}-out-{$command_name}", 'w'), // stdout
			2 => array('file', "{$logbase}-err-{$command_name}", 'w')  // stderr
		);
		$pipes = array();

		$process = proc_open($command, $descriptors, $pipes, $phpdir);
		fclose($pipes[0]);
		proc_close($process);

	}
}

/**
 * Runs a command, and logs all output.
 * FIXME: Determine ways this might fail, and adjust accordingly
*/
function run_shell_command ($command, $dir = NULL) {

	$errors = array();
	$pipes  = array();

	// Dir must be an absolute path, or NULL
	if ($dir) {
		if (is_dir($dir)) {
			$dir = realpath($dir);
		} else {
			$dir      = NULL;
			$errors[] = 'The set DIR does not exist: ' . $dir;
		}
	}
	
	$descriptors = array(
		0 => array('pipe', 'r'), // stdin
		1 => array('pipe', 'w'), // stdout
		2 => array('pipe', 'a')  // stderr
	);

	$process = proc_open($command, $descriptors, $pipes, $dir);
	
	$out = array(
		'command'=> $command,
		'dir'    => $dir,
		'errors' => $errors,
		'stdin'  => stream_get_contents($pipes[0]),
		'stdout' => stream_get_contents($pipes[1]),
		'stderr' => stream_get_contents($pipes[2]),
	);
	
	fclose($pipes[0]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	proc_close($process);
	
	return $out;
}

/**
 * Get status of PHP binaries, and returns information as an array
 * FIXME: Make better use of debugging information (likely breaking API) e.g., $out
*/
function get_status_binaries($path) {

	// Directories of builds	
	$iterator = new GlobIterator($path .'php-*', FilesystemIterator::CURRENT_AS_FILEINFO);

	$good = $bad = array();
	foreach ($iterator as $it) {

		// If bin/php exists and outputs something known
		$out = run_shell_command($it->getPathname() . '/bin/php -v');

		// FIXME: Better known check, and deal with bads
		if (false !== strpos($out['stdout'], '(cli) (built:')) {
			$good[$it->getBasename()] = $it;
		} else {
			$bad[$it->getBasename()]  = $it;
		}
	}
	uksort($good, 'strnatcmp');
	uksort($bad,  'strnatcmp');
	
	return array('good' => $good, 'bad' => $bad);
}

/**
 * Checks if $configure_options are valid
 * Returns false with error, array() if yes, array(...) if no
*/
function check_configure_valid ($path, $configure_options) {

	$out = run_shell_command($path . '/configure --help');

	if (empty($out['stdout'])) {
		return false;
	}
	$configure_help = $out['stdout'];

	$unknowns = array();
	foreach ($configure_options as $option) {
		if (false === stripos($configure_help, $option)) {
			$unknowns[] = $option;
		}
	}
	return $unknowns;
}

/**
 * Download a file ($url) to a given path ($savepath) and display progress information while doing it
 * Optionally check saved file against a known md5 hash
*/
function download_file ($url, $savepath, $md5hash = false) {

	// Stream the file, and output status information as defined in stream_notification_callback() via STREAM_NOTIFY_PROGRESS
	// Requires PHP 5.2.0+
	$ctx = stream_context_create();
	stream_context_set_params($ctx, array('notification' => 'stream_notification_callback'));

	$fp = fopen($url, 'r', false, $ctx);
	if (is_resource($fp) && file_put_contents($savepath, $fp)) {
		fclose($fp);
		if ($md5hash) {
			if (md5_file($savepath) === $md5hash) {
				return true;
			} else {
				return false;
			}
		}
		return true;
	}
	return false;
}

function get_version_configs($options, $version) {

	if (empty($options)) {
		return false;
	}
	
	// php-5.3.0 -> 5.3
	$version = preg_replace('/(php-)(\d+).(\d+).(\d+)/', '$2.$3', $version);
	
	if (empty($options[$version])) {
		return false;
	}
	return $options[$version];
}

/**
 * Choose a random mirror
 * FIXME: Ensure the mirror is working, and consider removing this sketchy feature
*/
function choose_random_mirror() {
	
	$known_mirrors = array('us', 'us2', 'uk', 'uk2', 'www');
	shuffle($known_mirrors);
	return $known_mirrors[0] . '.php.net';
}

/**
 * For use with download_file(), namely for progress notification of the download
 * Bulk of this taken from the PHP Manual @ http://php.net/stream_notification_callback
*/
function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
	static $filesize = null;

	switch($notification_code) {
	case STREAM_NOTIFY_RESOLVE:
	case STREAM_NOTIFY_AUTH_REQUIRED:
	case STREAM_NOTIFY_COMPLETED:
	case STREAM_NOTIFY_FAILURE:
	case STREAM_NOTIFY_AUTH_RESULT:
		break;

	case STREAM_NOTIFY_REDIRECTED:
		#echo 'Being redirected to: ', $message, PHP_EOL;
		break;

	case STREAM_NOTIFY_CONNECT:
		#echo 'Connected...' . PHP_EOL;
		break;

	case STREAM_NOTIFY_FILE_SIZE_IS:
		$filesize = $bytes_max;
		#echo 'Filesize: ', $filesize, PHP_EOL;
		break;

	case STREAM_NOTIFY_MIME_TYPE_IS:
		#echo 'Mime-type: ', $message, PHP_EOL;
		break;

	case STREAM_NOTIFY_PROGRESS:
		if ($bytes_transferred > 0) {
			if (!isset($filesize)) {
				printf("\rUnknown filesize.. %2d kb done..", $bytes_transferred/1024);
			} else {
				if (VERBOSE) {
					$length = (int)(($bytes_transferred/$filesize)*100);
					printf("\r%d%% (%2d/%2d kb)", $length, ($bytes_transferred/1024), $filesize/1024);
				}
			}
		}
		break;
	}
}
