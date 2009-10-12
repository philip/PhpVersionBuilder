<?php
/**
 * PhpVersionBuilder : Function library
 *
 * @author Philip Olson <philip@php.net>
 * @license MIT License
 *
*/

/**
 * Get information about PHP releases, including filenames and version numbers
 */
function get_php_version_info($php_versions) {
	
	$data = array();
	foreach ($php_versions as $php_version) {

		$ver_url  = 'http://' . choose_random_mirror() . '/releases/index.php?serialize=1&version=' . $php_version . '&max=42';
		$versions = unserialize(file_get_contents($ver_url));
		
		if (isset($versions['error'])) {
			continue;
		}

		if (!is_array($versions) || count($versions) < 1) {
			trigger_error("Unable to locate PHP versions for version: [$php_version]", E_USER_ERROR);
		}
		
		foreach ($versions as $version => $vinfo) {
			
			//FIXME: Note: Not all PHP versions have [the smaller] .bz2
			//FIXME: Will this check always work?
			$filename = $vinfo['source'][0]['filename'];		
			if (false === strpos($filename, 'tar.gz')) {
				$filename = $vinfo['source'][1]['filename'];
			}

			$data[$version] = array(
				'date'		=> trim($vinfo['date']),
				'filename'	=> trim($filename),
				'museum'	=> trim($vinfo['museum']),
			);
		}
	}
	return $data;
}

/**
 * Initialize the script.
 */
function initialize_environment() {

	$dirs = array(DIR_DOWNLOADS, DIR_EXTRACTIONS);

	foreach ($dirs as $dir) {
		if (!is_dir($dir)) {
			if (!mkdir($dir)) {
				trigger_error("Unable to create the directory: [$dir]", E_USER_ERROR);
				return false;
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
		$tar = shell_exec('which tar');
		if (false === strpos($tar, '/tar')) {
			trigger_error("Unable to locate the tar command, needed for file extraction", E_USER_ERROR);
			return false;
		}
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

	foreach ($it as $fileinfo) {
		$filepath = $fileinfo->getPathName();

		//FIXME: Snapshot extraction check appears a bit more tricky, with timestamped dirs (fix)
		//FIXME: Is this check always going to work for regular downloads?
		// Checks if version previously extracted
		if (is_dir($extractpath . str_replace('.tar.gz', '', $fileinfo->getFileName()))) {
			continue;
		}
		
		$tar = constant('PATH_TAR') ? PATH_TAR : 'tar';

		//FIXME: Research compatability with various sytems
		$command = "$tar xfvz $filepath -C $extractpath";
		shell_exec($command);
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

		$filepath = $path . '/' . $vinfo['filename'];
		
		if (file_exists($filepath)) {
			continue;
		}

		//FIXME: Do a URL check here (for 404, etc)
		if (empty($vinfo['museum'])) {
			$link = 'http://' . choose_random_mirror() . '/distributions/' . $vinfo['filename'];
		} else {
			$link = 'http://museum.php.net/php' . $version[0] . '/' . $vinfo['filename'];
		}

		//FIXME: copy() here? Consider alternative approaches
		copy($link, $filepath);
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
		copy($link, $filepath);
	}
	return true;
}

/**
 * FIXME: Add logging, error handing, configure management, etc.
 * FIXME: Document what is needed, especially with older PHP versions
 * FIXME: Allow custom environments, like old bison for old PHP versions
 * Build a PHP version
 */
function build_php ($phpdir, $prefix) {

	$command = "cd $phpdir && ./configure --prefix=$prefix --disable-all --disable-cgi --enable-cli && make && make install";
	shell_exec($command);
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
