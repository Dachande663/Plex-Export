#!/usr/bin/env php
<?php
/**
 * Plex Export v2
 *
 * Generate a HTML/JavaScript export of your Plex/Nine library
 * to share online.
 *
 **/

require_once 'system/bootstrap.php';

if(!is_cli()) die('You must run Plex Export from the command line. Please see the readme document for more help.');

try {
	$plexexport = new PlexExport();
	$args = parse_cli_arguments($argv);
	$plexexport->init($args);
} catch (Exception $e) {
	_errord($e->getMessage());
}
