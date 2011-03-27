<?php


/**
 * Gettext::translate
 **/
function __($str) {
	global $_PLEX_MO;
	return $_PLEX_MO->translate($str);
} // end func: recursive_glob



/**
 * Gettext::translate with echo
 **/
function _e($str) {
	echo __($str);
} // end func: recursive_glob



/**
 * Gettext::plural
 **/
function _n($single, $plural, $number) {
	global $_PLEX_MO;
	return $_PLEX_MO->translate_plural($single, $plural, $number);
} // end func: recursive_glob



/**
 * Gettext::import_from_file
 **/
function gettext_load_domain($lang, $mofile) {
	global $_PLEX_MO;
	if($lang == PlexExport::DEFAULT_LANG) return;
	if(!file_exists($mofile)) return;
	$new_mo = new Mo();
	$new_mo->import_from_file($mofile);
	$_PLEX_MO->merge_with($new_mo);
} // end func: gettext_load_domain





/**
 * Convert a large byte to Kb, Gb etc
 **/
function convert_bytes_to_higher_order($bytes, $v=1000) {
	
	if($bytes<$v) return array('value'=>$bytes, 'order'=>'b');
	
	$kb = $bytes/$v;
	if($kb<$v) return array('value'=>$kb, 'order'=>'kb');
	
	$mb = $kb/$v;
	if($mb<$v) return array('value'=>$mb, 'order'=>'mb');
	
	$gb = $mb/$v;
	if($gb<$v) return array('value'=>$gb, 'order'=>'gb');
	
	$tb = $tb/$v;
	return array('value'=>$tb, 'order'=>'tb');
	
} // end func: convert_bytes_to_higher_order





/**
 * Return absolute integer value
 **/
function absint($int) {
	return abs(intval($int));
} // end func: absint



/**
 * Return all files in all sub-directories that match pattern
 **/
function recursive_glob($pattern='*', $flags = 0, $path=false) {
	if(!$path) $path = dirname($pattern).'/';
	$paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
	$files = glob($path.$pattern, $flags);
	foreach($paths as $path) {
		$files = array_merge($files, recursive_glob($pattern, $flags, $path));
	}
	return $files;
} // end func: recursive_glob



/**
 * Recursively empty and delete a directory
 * - http://www.php.net/manual/en/function.rmdir.php#98622
 **/
function recursive_rmdir($dir) {
	if(is_dir($dir)) {
		$objects = scandir($dir);
		foreach($objects as $object) {
			if($object == '.' or $object == '..') continue;
			if(filetype($dir.'/'.$object) == 'dir')
				recursive_rmdir($dir.'/'.$object); 
			else
				unlink($dir.'/'.$object);
		}
		reset($objects);
		rmdir($dir);
	}
} // end func: recursive_rmdir



/**
 * Parse commandline arguments (-foo=bar)
 **/
function parse_cli_arguments($inputs) {
	
	$inputs = (array) $inputs;
	array_shift($inputs); # remove filename
	
	$args = array();
	
	foreach($inputs as $str) {
		if($str[0]!='-') continue;
		$eq = strpos($str, '=');
		$key = substr($str, 1, $eq-1);
		$key = str_replace('-', '_', $key); # convert - to _ for var names
		$value = substr($str, $eq+1);
		$args[$key] = $value;
	}
	
	return $args;
	
} // end func: parse_cli_arguments



/**
 * Returns true if running as a CLI application
 **/
function is_cli() {
	static $is_cli = null;
	if($is_cli!==null) return $is_cli;
	$is_cli = (php_sapi_name()=='cli' and !isset($_SERVER['REMOTE_ADDR'])) ? true : false;
	return $is_cli;
} // end func: is_cli



/**
 * Output a log message to STDOUT
 **/
function _log($str) {
	$str = @date('H:i:s')." $str\n";
	if(is_cli())
		fwrite(STDOUT, $str);
	else
		echo $str."/n";
} // end func: _log



/**
 * Output an error message to STDERR
 **/
function _error($str) {
	$str = @date('H:i:s')." Error: $str\n";
	if(is_cli())
		fwrite(STDERR, $str);
	else
		echo $str."/n";
} // end func: _error



/**
 * Call _error and then die()
 **/
function _errord($str) {
	_error($str);
	die();
} // end func: _errord



/**
 * Return $single or $plural form based on $count
 **/
function inflect($count, $single, $plural=false) {
	if($count==1) return $single;
	if($plural) return $plural;
	return $single.'s';
} // end func: inflect



/**
 * Return the time elapsed since execution start
 **/
function get_time_elapsed($precision=3) {
	global $_timer_start;
	$diff = microtime(true) - $_timer_start;
	return round($diff, $precision);
} // end func: get_time_elapsed



/**
 * Print_r wrapped in <pre>
 **/
function _print_r() {
	echo '<pre>';
	foreach(func_get_args() as $a) print_r($a);
	echo '</pre>';
} // end func: _print_r







