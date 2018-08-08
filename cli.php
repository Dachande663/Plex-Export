<?php
/*
	Plex Export
	Luke Lanchester <luke@lukelanchester.com>
	Inclues the PHP JavascriptPacker at bottom, all credit to the original authors

	A CLI script to export information from your Plex library.
	Usage:
		php cli.php [-plex-url="http://your-plex-library:32400"] [-data-dir="plex-data"] [-sections=1,2,3 or "Movies,TV Shows"] [-token=TheTokenToUse]
		
	Dane22, a Plex Community member added support for Plex Tokens
	
	Token: To get a valid token, look here: https://support.plex.tv/hc/en-us/articles/204059436-Finding-your-account-token-X-Plex-Token

*/
$timer_start = microtime(true);
$plex_export_version = 1;
ini_set('memory_limit', '512M');
set_error_handler('plex_error_handler');
error_reporting(E_ALL ^ E_NOTICE | E_WARNING);


// Set-up
	plex_log('Welcome to the Plex Exporter v'.$plex_export_version);
	$defaults = array(
		'plex-url' => 'http://localhost:32400',
		'data-dir' => 'plex-data',
		'thumbnail-width' => 150,
		'thumbnail-height' => 250,
		'sections' => 'all',
		'sort-skip-words' => 'a,the,der,die,das',
		'token' => ''
	);
	$options = hl_parse_arguments($_SERVER['argv'], $defaults);
	if(substr($options['plex-url'],-1)!='/') $options['plex-url'] .= '/'; // Always have a trailing slash
	$options['absolute-data-dir'] = dirname(__FILE__).'/'.$options['data-dir']; // Run in current dir (PHP CLI defect)
	$options['sort-skip-words'] = (array) explode(',', $options['sort-skip-words']); # comma separated list of words to skip for sorting titles
	
	// Create the http header with a X-Plex-Token in it	if specified
	
	if (strlen($options['token']) == 0){
		$headers = array(
			'http'=>array(
    	'method'=>"GET"                 
			)
		);
	}
	else
	{
		$headers = array(
		'http'=>array(
		  'method'=>"GET",
		  'header'=>"X-Plex-Token: ".$options['token']              
			)
		);
	}

	$context = stream_context_create($headers);
	
	check_dependancies(); // Check everything is enabled as necessary


// Load details about all sections
	$all_sections = load_all_sections();
	if(!$all_sections) {
		plex_error('Could not load section data, aborting');
		exit();
	}


// If user wants to show all (supported) sections...
	if($options['sections'] == 'all') {
		$sections = $all_sections;
	} else {


// Otherwise, match sections by Title first, then ID
		$sections_to_show = array_filter(explode(',',$options['sections']));
		$section_titles = array();
		foreach($all_sections as $i=>$section) $section_titles[strtolower($section['title'])] = $i;
		foreach($sections_to_show as $section_key_or_title) {
			
			$section_title = strtolower(trim($section_key_or_title));
			if(array_key_exists($section_title, $section_titles)) {
				$section_id = $section_titles[$section_title];
				$sections[$section_id] = $all_sections[$section_id];
				continue;
			}
			
			$section_id = intval($section_key_or_title);
			if(array_key_exists($section_id, $all_sections)) {
				$sections[$section_id] = $all_sections[$section_id];
				continue;
			}
			
			plex_error('Could not find section: '.$section_key_or_title);
			
		} // end foreach: $sections_to_show
	} // end if: !all sections


// If no sections found (or matched)
	$num_sections = count($sections);
	if($num_sections==0) {
		plex_error('No sections were found to scan');
		exit();
	}


// Load details about each section
	$total_items = 0;
	$section_display_order = array();
	foreach($sections as $i=>$section) {
		plex_log('Scanning section: '.$section['title']);

		$items = load_items_for_section($section);

		if(!$items) {
			plex_error('No items were added for '.$section['title'].', skipping');
			$sections[$i]['num_items'] = 0;
			$sections[$i]['items'] = array();
			continue;
		}
		
		$num_items = count($items);
		if($section['type']=='show') {
			$num_items_episodes = 0;
			foreach($items as $item) $num_items_episodes += $item['num_episodes'];
			$total_items += $num_items_episodes;
		} else {
			$total_items += $num_items;	
		}

		plex_log('Analysing media items in section...');

		$sorts_title = $sorts_release = $sorts_rating = $sorts_added_at = array();
		$raw_section_genres = array();

		foreach($items as $key=>$item) {
			
			$title_sort = strtolower($item['titleSort']);
			$title_first_space = strpos($title_sort, ' ');
			if($title_first_space>0) {
				$title_first_word = substr($title_sort, 0, $title_first_space);
				if(in_array($title_first_word, $options['sort-skip-words'])) {
					$title_sort = substr($title_sort, $title_first_space+1);
				}
			}
			$sorts_title[$key] = $title_sort;
			$sorts_release[$key] = @strtotime($item['release_date']);
			$sorts_rating[$key] = ($item['user_rating'])?$item['user_rating']:$item['rating'];
			if(is_array($item['genre']) and count($item['genre'])>0) {
				foreach($item['genre'] as $genre) {
					$raw_section_genres[$genre]++;
				}
			}
			$sorts_added_at[$key] = $item['addedAt'];
		} // end foreach: $items (for sorting)

		asort($sorts_title, SORT_STRING);
		asort($sorts_release, SORT_NUMERIC);
		asort($sorts_added_at, SORT_NUMERIC);
		asort($sorts_rating, SORT_NUMERIC);
		$sorts['title_asc'] = array_keys($sorts_title);
		$sorts['release_asc'] = array_keys($sorts_release);
		$sorts['addedAt_asc'] = array_keys($sorts_added_at);
		$sorts['rating_asc'] = array_keys($sorts_rating);
		$sorts['title_desc'] = array_reverse($sorts['title_asc']);
		$sorts['release_desc'] = array_reverse($sorts['release_asc']);
		$sorts['addedAt_desc'] = array_reverse($sorts['addedAt_asc']);
		$sorts['rating_desc'] = array_reverse($sorts['rating_asc']);

		$section_genres = array();
		if(count($raw_section_genres)>0) {
			arsort($raw_section_genres);
			foreach($raw_section_genres as $genre=>$genre_count) {
				$section_genres[] = array(
					'genre' => $genre,
					'count' => $genre_count,
				);
			}
		}
		
		$section_display_order[] = $i;
		$sections[$i]['num_items'] = $num_items;
		$sections[$i]['items'] = $items;
		$sections[$i]['sorts'] = $sorts;
		$sections[$i]['genres'] = $section_genres;

		plex_log('Added '.$num_items.' '.hl_inflect($num_items,'item').' from the '.$section['title'].' section');

	} // end foreach: $sections_to_export


// Output all data

	plex_log('Exporting data for '.$num_sections.' '.hl_inflect($num_sections,'section').' containing '.$total_items.' '.hl_inflect($total_items,'item'));

	$output = array(
		'status' => 'success',
		'version' => $plex_export_version,
		'last_generated' => time()*1000,
		'last_updated' => 'last updated : '.date('Y-m-d - H:i',time()),
		'total_items' => $total_items,
		'num_sections' => $num_sections,
		'section_display_order' => $section_display_order,
		'sections' => $sections
	);

	plex_log('Generating and minifying JSON output, this may take some time...');
	$raw_json = json_encode($output);
	$raw_js = 'var raw_plex_data = '.$raw_json.';';
	//$myPacker = new JavaScriptPacker($raw_js); # See bottom of file for relevant Class
	//$packed_js = $myPacker->pack();
	$packed_js = $raw_js;
	if(!$packed_js) {
		plex_error('Could not minify JSON output, aborting.');
		exit();
	}

	$filename = $options['absolute-data-dir'].'/data.js';
	$bytes_written = file_put_contents($filename, $packed_js);
	if(!$bytes_written) {
		plex_error('Could not save JSON data to '.$filename.', please make sure directory is writeable');
		exit();
	}

	plex_log('Wrote '.$bytes_written.' bytes to '.$filename);

	$timer_end = microtime(true);
	$time_taken = $timer_end - $timer_start;
	plex_log('Plex Export completed in '.round($time_taken,2).' seconds');









// Methods //////////////////////////////////////////////////////////////



/**
 * Parse a Movie
 **/
function load_data_for_movie($el) {

	global $options;
	global $context;

	$_el = $el->attributes();
	$key = intval($_el->ratingKey);
	if($key<=0) return false;
	$title = strval($_el->title);
	if (!$titleSort = strval($_el->titleSort)) {
	  $titleSort = $title;
	  plex_log('Scanning movie: '.$title);
	} else {
	  plex_log('Scanning movie: '.$title . ' ( sortTitle: '.$titleSort.' )');
	}

	$thumb = generate_item_thumbnail(strval($_el->thumb), $key, $title);

	$item = array(
		'key' => $key,
		'type' => 'movie',
		'thumb' => $thumb,
		'title' => $title,
		'titleSort' => $titleSort,
		'duration' => floatval($_el->duration),
		'view_count' => intval($_el->viewCount),
		'tagline' => ($_el->tagline)?strval($_el->tagline):false,
		'rating' => ($_el->rating)?floatval($_el->rating):false,
		'user_rating' => ($_el->userRating)?floatval($_el->userRating):false,
		'release_year' => ($_el->year)?intval($_el->year):false,
		'release_date' => ($_el->originallyAvailableAt)?strval($_el->originallyAvailableAt):false,
		'addedAt' => false,
		'content_rating' => ($_el->contentRating)?strval($_el->contentRating):false,
		'summary' => ($_el->summary)?strval($_el->summary):false,
		'studio' => ($_el->studio)?strval($_el->studio):false,
		'genre' => false,
		'director' => false,
		'role' => false,
		'media' => false
	);

	$media_el = $el->Media->attributes();
	if(intval($media_el->duration)>0) {
		$item['media'] = array(
			'bitrate' => ($media_el->bitrate)?intval($media_el->bitrate):false,
			'aspect_ratio' => ($media_el->aspectRatio)?floatval($media_el->aspectRatio):false,
			'audio_channels' => ($media_el->audioChannels)?intval($media_el->audioChannels):false,
			'audio_codec' => ($media_el->audioCodec)?strval($media_el->audioCodec):false,
			'video_codec' => ($media_el->videoCodec)?strval($media_el->videoCodec):false,
			'video_resolution' => ($media_el->videoResolution)?intval($media_el->videoResolution):false,
			'video_framerate' => ($media_el->videoFrameRate)?strval($media_el->videoFrameRate):false,
			'total_size' => false
		);
		$total_size = 0;
		foreach($el->Media->Part as $part) {
			$total_size += floatval($part->attributes()->size);
		}
		if($total_size>0) {
			$item['media']['total_size'] = $total_size;
		}
	}

	$url = $options['plex-url'].'library/metadata/'.$key;
	$xml = load_xml_from_url($url);
	if(!$xml) {
		plex_error('Could not load additional metadata for '.$title);
		return $item;
	}

	$genres = array();
	foreach($xml->Video->Genre as $genre) $genres[] = strval($genre->attributes()->tag);
	if(count($genres)>0) $item['genre'] = $genres;

	$directors = array();
	foreach($xml->Video->Director as $director) $directors[] = strval($director->attributes()->tag);
	if(count($directors)>0) $item['director'] = $directors;

	$roles = array();
	foreach($xml->Video->Role as $role) $roles[] = strval($role->attributes()->tag);
	if(count($roles)>0) $item['role'] = $roles;

	$item['addedAt']=intval($xml->Video->attributes()->addedAt);

	return $item;

} // end func: load_data_for_movie



/**
 * Parse a TV Show
 **/
function load_data_for_show($el) {

	global $options;

	$_el = $el->attributes();
	$key = intval($_el->ratingKey);
	if($key<=0) return false;
	$title = strval($_el->title);
	if (!$titleSort = strval($_el->titleSort)) {
	  plex_log('Scanning show: '.$title);
	} else {
	  plex_log('Scanning show: '.$title . ' ( sortTitle: '.$titleSort.' )');
	}

	$thumb = generate_item_thumbnail(strval($_el->thumb), $key, $title);

	$item = array(
		'key' => $key,
		'type' => 'show',
		'thumb' => $thumb,
		'title' => $title,
		'titleSort' => $titleSort,
		'rating' => ($_el->rating)?floatval($_el->rating):false,
		'user_rating' => ($_el->userRating)?floatval($_el->userRating):false,
		'release_year' => ($_el->year)?intval($_el->year):false,
		'release_date' => ($_el->originallyAvailableAt)?strval($_el->originallyAvailableAt):false,
		'duration' => floatval($_el->duration),
		'content_rating' => ($_el->contentRating)?strval($_el->contentRating):false,
		'summary' => ($_el->summary)?strval($_el->summary):false,
		'studio' => ($_el->studio)?strval($_el->studio):false,
		'tagline' => false,
		'num_episodes' => intval($_el->leafCount),
		'num_seasons' => false,
		'seasons' => array()
	);

	$genres = array();
	foreach($el->Genre as $genre) $genres[] = strval($genre->attributes()->tag);
	if(count($genres)>0) $item['genre'] = $genres;
	
	$url = $options['plex-url'].'library/metadata/'.$key.'/children';
	$xml = load_xml_from_url($url);
	if(!$xml) {
		plex_error('Could not load additional metadata for '.$title);
		return $item;
	}
	
	$seasons = array();
	$season_sort_order = array();
	foreach($xml->Directory as $el2) {
		if($el2->attributes()->type!='season') continue;
		$season_key = intval($el2->attributes()->ratingKey);
		$season_sort_order[intval($el2->attributes()->index)] = $season_key;
		$season = array(
			'key' => $season_key,
			'title' => strval($el2->attributes()->title),
			'num_episodes' => intval($el2->attributes()->leafCount),
			'actual_episodes' => 0,
			'episodes' => array(),
			'index' => intval($el2->attributes()->index)
		);
		
		$url = $options['plex-url'].'library/metadata/'.$season_key.'/children';
		$xml2 = load_xml_from_url($url);
		if(!$xml2) {
			plex_error('Could not load season data for '.$item['title'].' : '.$season['title']);
		}
		
		$episode_sort_order = array();
		foreach($xml2->Video as $el3) {
			if($el3->attributes()->type!='episode') continue;
			$episode_key = intval($el3->attributes()->ratingKey);
			$episode_sort_order[intval($el3->attributes()->index)] = $episode_key;
			$episode = array(
				'key' => $episode_key,
				'title' => strval($el3->attributes()->title),
				'index' => intval($el3->attributes()->index),
				'summary' => strval($el3->attributes()->summary),
				'rating' => floatval($el3->attributes()->rating),
				'duration' => floatval($el3->attributes()->duration),
				'view_count' => intval($el3->attributes()->viewCount)
			);
			$season['episodes'][$episode_key] = $episode;
			$season['actual_episodes']++;
		}
		
		ksort($episode_sort_order);
		$season['episode_sort_order'] = array_values($episode_sort_order);
		
		$seasons[$season_key] = $season;
	}	
	ksort($season_sort_order);
	$item['season_sort_order'] = array_values($season_sort_order);
	$item['num_seasons'] = count($seasons);
	if($item['num_seasons']>0) $item['seasons'] = $seasons;

	return $item;

} // end func: load_data_for_show


/**
 * Load all supported sections from given Plex API endpoint
 **/
function load_all_sections() {

	global $options;
	$url = $options['plex-url'].'library/sections';
	plex_log('Searching for sections in the Plex library at '.$options['plex-url']);

	$xml = load_xml_from_url($url);
	if(!$xml) return false;

	$total_sections = intval($xml->attributes()->size);
	if($total_sections<=0) {
		plex_error('No sections were found in this Plex library');
		return false;
	}

	$sections = array();
	$num_sections = 0;

	foreach($xml->Directory as $el) {
		$_el = $el->attributes();
		$key = intval($_el->key);
		$type = strval($_el->type);
		$title = strval($_el->title);
		if($type=='movie' or $type=='show') {
			$sections[$key] = array('key'=>$key, 'type'=>$type, 'title'=>$title);
			$num_sections++;
		} else {
			plex_error('Skipping section of unknown type: '.$type);
		}
	}

	if($num_sections==0) {
		plex_error('No valid sections found, aborting');
		return false;
	}

	if($total_sections!=$num_sections) {
		plex_log('Found '.$num_sections.' valid '.hl_inflect($num_sections, 'section').' out of a possible '.$total_sections.' '.hl_inflect($total_sections, 'section').' in this Plex library');
	} else {
		plex_log('Found '.$num_sections.' '.hl_inflect($num_sections, 'section').' in this Plex library');
	}

	return $sections;

} // end func: load_all_sections



/**
 * Load all items present in a section
 **/
function load_items_for_section($section) {

	global $options;
	$url = $options['plex-url'].'library/sections/'.$section['key'].'/all';

	$xml = load_xml_from_url($url);
	if(!$xml) return false;

	$num_items = intval($xml->attributes()->size);
	if($num_items<=0) {
		plex_error('No items were found in this section, skipping');
		return false;
	}

	switch($section['type']) {
		case 'movie':
			$object_to_loop = $xml->Video;
			$object_parser = 'load_data_for_movie';
			break;
		case 'show':
			$object_to_loop = $xml->Directory;
			$object_parser = 'load_data_for_show';
			break;
		default:
			plex_error('Unknown section type provided to parse: '.$section['type']);
			return false;
	}

	plex_log('Found '.$num_items.' '.hl_inflect($num_items,$section['type']).' in '.$section['title']);

	$items = array();
	foreach($object_to_loop as $el) {
		$item = $object_parser($el);
		if($item) $items[$item['key']] = $item;

	}

	return $items;

} // end func: load_items_for_section



/**
 * Load URL and parse as XML
 **/
function load_xml_from_url($url) {

	global $options;
	global $context;
	
	
	

	if(!@fopen($url, 'r', false, $context)) {
		plex_error('The Plex library could not be found at '.$options['plex-url']);
		return false;
	}

	$xml = file_get_contents($url, false, $context);
	$xml = @simplexml_load_string($xml);
	if(!$xml) {
		plex_error('Data could not be read from the Plex server at '.$url);
		return false;
	}

	if(!$xml) {
		plex_error('Invalid XML returned by the Plex server, aborting');
		return false;
	}

	return $xml;

} // end func: load_xml_from_url



/**
 * Load a thumbnail via Plex API and save
 **/
function generate_item_thumbnail($thumb_url, $key, $title) {

	global $options;
	global $context;

	$filename = '/thumb_'.$key.'.jpeg';
	$save_filename = $options['absolute-data-dir'].$filename;
	$return_filename = $options['data-dir'].$filename;

	if(file_exists($save_filename)) return $return_filename;

	if($thumb_url=='') {
		plex_error('No thumbnail URL was provided for '.$title, ', skipping');
		return false;
	}

	$source_url = $options['plex-url'].substr($thumb_url,1); # e.g. http://local:32400/library/metadata/123/thumb?=date
	$transcode_url = $options['plex-url'].'photo/:/transcode?width='.$options['thumbnail-width'].'&height='.$options['thumbnail-height'].'&url='.urlencode($source_url);

	$img_data = @file_get_contents($transcode_url, false, $context);
	if(!$img_data) {
		plex_error('Could not load thumbnail for '.$title,' skipping');
		return false;
	}

	$result = @file_put_contents($save_filename, $img_data);
	if(!$result) {
		plex_error('Could not save thumbnail for '.$title,' skipping');
		return false;
	}

	return $return_filename;

} // end func: generate_item_thumbnail



/**
 * Output a message to STDOUT
 **/
function plex_log($str) {
	$str = @date('H:i:s')." $str\n";
	fwrite(STDOUT, $str);
} // end func: plex_log



/**
 * Output an error to STDERR
 **/
function plex_error($str) {
	$str = @date('H:i:s')." Error: $str\n";
	fwrite(STDERR, $str);
} // end func: plex_error



/**
 * Capture PHP error events
 **/
function plex_error_handler($errno, $errstr, $errfile=null, $errline=null) {
	if(!(error_reporting() & $errno)) return;
	$str = @date('H:i:s')." Error: $errstr". ($errline?' on line '.$errline:'') ."\n";
	fwrite(STDERR, $str);
} // end func: plex_error_handler



/**
 * Check environment meets dependancies, exit() if not
 **/
function check_dependancies() {
	global $options;
	$errors = false;

	if(!extension_loaded('simplexml')) {
		plex_error('SimpleXML is not enabled');
		$errors = true;
	}

	if(!ini_get('allow_url_fopen')) {
		plex_error('Remote URL access is disabled (allow_url_fopen)');
		$errors = true;
	}

	if(!is_writable($options['absolute-data-dir'])) {
		plex_error('Data directory is not writeable at '.$options['absolute-data-dir']);
		$errors = true;
	}

	if($errors) {
		plex_error('Failed one or more dependancy checks; aborting');
		exit();
	}

} // end func: check_dependancies



/**
 * Produce output array from merger of inputs and defaults
 **/
function hl_parse_arguments($cli_args, $defaults) {
	$output = (array) $defaults;
	foreach($cli_args as $str) {
		if(substr($str,0,1)!='-') continue;
		$eq_pos = strpos($str, '=');
		$key = substr($str, 1, $eq_pos-1);
		if(!array_key_exists($key, $output)) continue;
		$output[$key] = substr($str, $eq_pos+1);
	}
	return $output;
} // end func: hl_parse_arguments



/**
 * Return plural form if !=1
 **/
function hl_inflect($num, $single, $plural=false) {
	if($num==1) return $single;
	if($plural) return $plural;
	return $single.'s';
} // end func: hl_inflect


























/* 
 * This is the php version of the Dean Edwards JavaScript's Packer,
 * Based on :
 * 
 * ParseMaster, version 1.0.2 (2005-08-19) Copyright 2005, Dean Edwards
 * a multi-pattern parser.
 * KNOWN BUG: erroneous behavior when using escapeChar with a replacement
 * value that is a function
 * 
 * packer, version 2.0.2 (2005-08-19) Copyright 2004-2005, Dean Edwards
 * 
 * License: http://creativecommons.org/licenses/LGPL/2.1/
 * 
 * Ported to PHP by Nicolas Martin.
 */
class JavaScriptPacker {
	// constants
	const IGNORE = '$1';

	// validate parameters
	private $_script = '';
	private $_encoding = 62;
	private $_fastDecode = true;
	private $_specialChars = false;
	
	private $LITERAL_ENCODING = array(
		'None' => 0,
		'Numeric' => 10,
		'Normal' => 62,
		'High ASCII' => 95
	);
	
	public function __construct($_script, $_encoding = 62, $_fastDecode = true, $_specialChars = false)
	{
		$this->_script = $_script . "\n";
		if (array_key_exists($_encoding, $this->LITERAL_ENCODING))
			$_encoding = $this->LITERAL_ENCODING[$_encoding];
		$this->_encoding = min((int)$_encoding, 95);
		$this->_fastDecode = $_fastDecode;	
		$this->_specialChars = $_specialChars;
	}
	
	public function pack() {
		$this->_addParser('_basicCompression');
		if ($this->_specialChars)
			$this->_addParser('_encodeSpecialChars');
		if ($this->_encoding)
			$this->_addParser('_encodeKeywords');
		
		// go!
		return $this->_pack($this->_script);
	}
	
	// apply all parsing routines
	private function _pack($script) {
		for ($i = 0; isset($this->_parsers[$i]); $i++) {
			$script = call_user_func(array(&$this,$this->_parsers[$i]), $script);
		}
		return $script;
	}
	
	// keep a list of parsing functions, they'll be executed all at once
	private $_parsers = array();
	private function _addParser($parser) {
		$this->_parsers[] = $parser;
	}
	
	// zero encoding - just removal of white space and comments
	private function _basicCompression($script) {
		$parser = new ParseMaster();
		// make safe
		$parser->escapeChar = '\\';
		// protect strings
		$parser->add('/\'[^\'\\n\\r]*\'/', self::IGNORE);
		$parser->add('/"[^"\\n\\r]*"/', self::IGNORE);
		// remove comments
		$parser->add('/\\/\\/[^\\n\\r]*[\\n\\r]/', ' ');
		$parser->add('/\\/\\*[^*]*\\*+([^\\/][^*]*\\*+)*\\//', ' ');
		// protect regular expressions
		$parser->add('/\\s+(\\/[^\\/\\n\\r\\*][^\\/\\n\\r]*\\/g?i?)/', '$2'); // IGNORE
		$parser->add('/[^\\w\\x24\\/\'"*)\\?:]\\/[^\\/\\n\\r\\*][^\\/\\n\\r]*\\/g?i?/', self::IGNORE);
		// remove: ;;; doSomething();
		if ($this->_specialChars) $parser->add('/;;;[^\\n\\r]+[\\n\\r]/');
		// remove redundant semi-colons
		$parser->add('/\\(;;\\)/', self::IGNORE); // protect for (;;) loops
		$parser->add('/;+\\s*([};])/', '$2');
		// apply the above
		$script = $parser->exec($script);

		// remove white-space
		$parser->add('/(\\b|\\x24)\\s+(\\b|\\x24)/', '$2 $3');
		$parser->add('/([+\\-])\\s+([+\\-])/', '$2 $3');
		$parser->add('/\\s+/', '');
		// done
		return $parser->exec($script);
	}
	
	private function _encodeSpecialChars($script) {
		$parser = new ParseMaster();
		// replace: $name -> n, $$name -> na
		$parser->add('/((\\x24+)([a-zA-Z$_]+))(\\d*)/',
					 array('fn' => '_replace_name')
		);
		// replace: _name -> _0, double-underscore (__name) is ignored
		$regexp = '/\\b_[A-Za-z\\d]\\w*/';
		// build the word list
		$keywords = $this->_analyze($script, $regexp, '_encodePrivate');
		// quick ref
		$encoded = $keywords['encoded'];
		
		$parser->add($regexp,
			array(
				'fn' => '_replace_encoded',
				'data' => $encoded
			)
		);
		return $parser->exec($script);
	}
	
	private function _encodeKeywords($script) {
		// escape high-ascii values already in the script (i.e. in strings)
		if ($this->_encoding > 62)
			$script = $this->_escape95($script);
		// create the parser
		$parser = new ParseMaster();
		$encode = $this->_getEncoder($this->_encoding);
		// for high-ascii, don't encode single character low-ascii
		$regexp = ($this->_encoding > 62) ? '/\\w\\w+/' : '/\\w+/';
		// build the word list
		$keywords = $this->_analyze($script, $regexp, $encode);
		$encoded = $keywords['encoded'];
		
		// encode
		$parser->add($regexp,
			array(
				'fn' => '_replace_encoded',
				'data' => $encoded
			)
		);
		if (empty($script)) return $script;
		else {
			//$res = $parser->exec($script);
			//$res = $this->_bootStrap($res, $keywords);
			//return $res;
			return $this->_bootStrap($parser->exec($script), $keywords);
		}
	}
	
	private function _analyze($script, $regexp, $encode) {
		// analyse
		// retreive all words in the script
		$all = array();
		preg_match_all($regexp, $script, $all);
		$_sorted = array(); // list of words sorted by frequency
		$_encoded = array(); // dictionary of word->encoding
		$_protected = array(); // instances of "protected" words
		$all = $all[0]; // simulate the javascript comportement of global match
		if (!empty($all)) {
			$unsorted = array(); // same list, not sorted
			$protected = array(); // "protected" words (dictionary of word->"word")
			$value = array(); // dictionary of charCode->encoding (eg. 256->ff)
			$this->_count = array(); // word->count
			$i = count($all); $j = 0; //$word = null;
			// count the occurrences - used for sorting later
			do {
				--$i;
				$word = '$' . $all[$i];
				if (!isset($this->_count[$word])) {
					$this->_count[$word] = 0;
					$unsorted[$j] = $word;
					// make a dictionary of all of the protected words in this script
					//  these are words that might be mistaken for encoding
					//if (is_string($encode) && method_exists($this, $encode))
					$values[$j] = call_user_func(array(&$this, $encode), $j);
					$protected['$' . $values[$j]] = $j++;
				}
				// increment the word counter
				$this->_count[$word]++;
			} while ($i > 0);
			// prepare to sort the word list, first we must protect
			//  words that are also used as codes. we assign them a code
			//  equivalent to the word itself.
			// e.g. if "do" falls within our encoding range
			//      then we store keywords["do"] = "do";
			// this avoids problems when decoding
			$i = count($unsorted);
			do {
				$word = $unsorted[--$i];
				if (isset($protected[$word]) /*!= null*/) {
					$_sorted[$protected[$word]] = substr($word, 1);
					$_protected[$protected[$word]] = true;
					$this->_count[$word] = 0;
				}
			} while ($i);
			
			// sort the words by frequency
			// Note: the javascript and php version of sort can be different :
			// in php manual, usort :
			// " If two members compare as equal,
			// their order in the sorted array is undefined."
			// so the final packed script is different of the Dean's javascript version
			// but equivalent.
			// the ECMAscript standard does not guarantee this behaviour,
			// and thus not all browsers (e.g. Mozilla versions dating back to at
			// least 2003) respect this. 
			usort($unsorted, array(&$this, '_sortWords'));
			$j = 0;
			// because there are "protected" words in the list
			//  we must add the sorted words around them
			do {
				if (!isset($_sorted[$i]))
					$_sorted[$i] = substr($unsorted[$j++], 1);
				$_encoded[$_sorted[$i]] = $values[$i];
			} while (++$i < count($unsorted));
		}
		return array(
			'sorted'  => $_sorted,
			'encoded' => $_encoded,
			'protected' => $_protected);
	}
	
	private $_count = array();
	private function _sortWords($match1, $match2) {
		return $this->_count[$match2] - $this->_count[$match1];
	}
	
	// build the boot function used for loading and decoding
	private function _bootStrap($packed, $keywords) {
		$ENCODE = $this->_safeRegExp('$encode\\($count\\)');

		// $packed: the packed script
		$packed = "'" . $this->_escape($packed) . "'";

		// $ascii: base for encoding
		$ascii = min(count($keywords['sorted']), $this->_encoding);
		if ($ascii == 0) $ascii = 1;

		// $count: number of words contained in the script
		$count = count($keywords['sorted']);

		// $keywords: list of words contained in the script
		foreach ($keywords['protected'] as $i=>$value) {
			$keywords['sorted'][$i] = '';
		}
		// convert from a string to an array
		ksort($keywords['sorted']);
		$keywords = "'" . implode('|',$keywords['sorted']) . "'.split('|')";

		$encode = ($this->_encoding > 62) ? '_encode95' : $this->_getEncoder($ascii);
		$encode = $this->_getJSFunction($encode);
		$encode = preg_replace('/_encoding/','$ascii', $encode);
		$encode = preg_replace('/arguments\\.callee/','$encode', $encode);
		$inline = '\\$count' . ($ascii > 10 ? '.toString(\\$ascii)' : '');

		// $decode: code snippet to speed up decoding
		if ($this->_fastDecode) {
			// create the decoder
			$decode = $this->_getJSFunction('_decodeBody');
			if ($this->_encoding > 62)
				$decode = preg_replace('/\\\\w/', '[\\xa1-\\xff]', $decode);
			// perform the encoding inline for lower ascii values
			elseif ($ascii < 36)
				$decode = preg_replace($ENCODE, $inline, $decode);
			// special case: when $count==0 there are no keywords. I want to keep
			//  the basic shape of the unpacking funcion so i'll frig the code...
			if ($count == 0)
				$decode = preg_replace($this->_safeRegExp('($count)\\s*=\\s*1'), '$1=0', $decode, 1);
		}

		// boot function
		$unpack = $this->_getJSFunction('_unpack');
		if ($this->_fastDecode) {
			// insert the decoder
			$this->buffer = $decode;
			$unpack = preg_replace_callback('/\\{/', array(&$this, '_insertFastDecode'), $unpack, 1);
		}
		$unpack = preg_replace('/"/', "'", $unpack);
		if ($this->_encoding > 62) { // high-ascii
			// get rid of the word-boundaries for regexp matches
			$unpack = preg_replace('/\'\\\\\\\\b\'\s*\\+|\\+\s*\'\\\\\\\\b\'/', '', $unpack);
		}
		if ($ascii > 36 || $this->_encoding > 62 || $this->_fastDecode) {
			// insert the encode function
			$this->buffer = $encode;
			$unpack = preg_replace_callback('/\\{/', array(&$this, '_insertFastEncode'), $unpack, 1);
		} else {
			// perform the encoding inline
			$unpack = preg_replace($ENCODE, $inline, $unpack);
		}
		// pack the boot function too
		$unpackPacker = new JavaScriptPacker($unpack, 0, false, true);
		$unpack = $unpackPacker->pack();
		
		// arguments
		$params = array($packed, $ascii, $count, $keywords);
		if ($this->_fastDecode) {
			$params[] = 0;
			$params[] = '{}';
		}
		$params = implode(',', $params);
		
		// the whole thing
		return 'eval(' . $unpack . '(' . $params . "))\n";
	}
	
	private $buffer;
	private function _insertFastDecode($match) {
		return '{' . $this->buffer . ';';
	}
	private function _insertFastEncode($match) {
		return '{$encode=' . $this->buffer . ';';
	}
	
	// mmm.. ..which one do i need ??
	private function _getEncoder($ascii) {
		return $ascii > 10 ? $ascii > 36 ? $ascii > 62 ?
		       '_encode95' : '_encode62' : '_encode36' : '_encode10';
	}
	
	// zero encoding
	// characters: 0123456789
	private function _encode10($charCode) {
		return $charCode;
	}
	
	// inherent base36 support
	// characters: 0123456789abcdefghijklmnopqrstuvwxyz
	private function _encode36($charCode) {
		return base_convert($charCode, 10, 36);
	}
	
	// hitch a ride on base36 and add the upper case alpha characters
	// characters: 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
	private function _encode62($charCode) {
		$res = '';
		if ($charCode >= $this->_encoding) {
			$res = $this->_encode62((int)($charCode / $this->_encoding));
		}
		$charCode = $charCode % $this->_encoding;
		
		if ($charCode > 35)
			return $res . chr($charCode + 29);
		else
			return $res . base_convert($charCode, 10, 36);
	}
	
	// use high-ascii values
	// characters: ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ
	private function _encode95($charCode) {
		$res = '';
		if ($charCode >= $this->_encoding)
			$res = $this->_encode95($charCode / $this->_encoding);
		
		return $res . chr(($charCode % $this->_encoding) + 161);
	}
	
	private function _safeRegExp($string) {
		return '/'.preg_replace('/\$/', '\\\$', $string).'/';
	}
	
	private function _encodePrivate($charCode) {
		return "_" . $charCode;
	}
	
	// protect characters used by the parser
	private function _escape($script) {
		return preg_replace('/([\\\\\'])/', '\\\$1', $script);
	}
	
	// protect high-ascii characters already in the script
	private function _escape95($script) {
		return preg_replace_callback(
			'/[\\xa1-\\xff]/',
			array(&$this, '_escape95Bis'),
			$script
		);
	}
	private function _escape95Bis($match) {
		return '\x'.((string)dechex(ord($match)));
	}
	
	
	private function _getJSFunction($aName) {
		if (defined('self::JSFUNCTION'.$aName))
			return constant('self::JSFUNCTION'.$aName);
		else 
			return '';
	}
	
	// JavaScript Functions used.
	// Note : In Dean's version, these functions are converted
	// with 'String(aFunctionName);'.
	// This internal conversion complete the original code, ex :
	// 'while (aBool) anAction();' is converted to
	// 'while (aBool) { anAction(); }'.
	// The JavaScript functions below are corrected.
	
	// unpacking function - this is the boot strap function
	//  data extracted from this packing routine is passed to
	//  this function when decoded in the target
	// NOTE ! : without the ';' final.
	const JSFUNCTION_unpack =

'function($packed, $ascii, $count, $keywords, $encode, $decode) {
    while ($count--) {
        if ($keywords[$count]) {
            $packed = $packed.replace(new RegExp(\'\\\\b\' + $encode($count) + \'\\\\b\', \'g\'), $keywords[$count]);
        }
    }
    return $packed;
}';
/*
'function($packed, $ascii, $count, $keywords, $encode, $decode) {
    while ($count--)
        if ($keywords[$count])
            $packed = $packed.replace(new RegExp(\'\\\\b\' + $encode($count) + \'\\\\b\', \'g\'), $keywords[$count]);
    return $packed;
}';
*/
	
	// code-snippet inserted into the unpacker to speed up decoding
	const JSFUNCTION_decodeBody =
//_decode = function() {
// does the browser support String.replace where the
//  replacement value is a function?

'    if (!\'\'.replace(/^/, String)) {
        // decode all the values we need
        while ($count--) {
            $decode[$encode($count)] = $keywords[$count] || $encode($count);
        }
        // global replacement function
        $keywords = [function ($encoded) {return $decode[$encoded]}];
        // generic match
        $encode = function () {return \'\\\\w+\'};
        // reset the loop counter -  we are now doing a global replace
        $count = 1;
    }
';
//};
/*
'	if (!\'\'.replace(/^/, String)) {
        // decode all the values we need
        while ($count--) $decode[$encode($count)] = $keywords[$count] || $encode($count);
        // global replacement function
        $keywords = [function ($encoded) {return $decode[$encoded]}];
        // generic match
        $encode = function () {return\'\\\\w+\'};
        // reset the loop counter -  we are now doing a global replace
        $count = 1;
    }';
*/
	
	 // zero encoding
	 // characters: 0123456789
	 const JSFUNCTION_encode10 =
'function($charCode) {
    return $charCode;
}';//;';
	
	 // inherent base36 support
	 // characters: 0123456789abcdefghijklmnopqrstuvwxyz
	 const JSFUNCTION_encode36 =
'function($charCode) {
    return $charCode.toString(36);
}';//;';
	
	// hitch a ride on base36 and add the upper case alpha characters
	// characters: 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
	const JSFUNCTION_encode62 =
'function($charCode) {
    return ($charCode < _encoding ? \'\' : arguments.callee(parseInt($charCode / _encoding))) +
    (($charCode = $charCode % _encoding) > 35 ? String.fromCharCode($charCode + 29) : $charCode.toString(36));
}';
	
	// use high-ascii values
	// characters: ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ
	const JSFUNCTION_encode95 =
'function($charCode) {
    return ($charCode < _encoding ? \'\' : arguments.callee($charCode / _encoding)) +
        String.fromCharCode($charCode % _encoding + 161);
}'; 
	
}


class ParseMaster {
	public $ignoreCase = false;
	public $escapeChar = '';
	
	// constants
	const EXPRESSION = 0;
	const REPLACEMENT = 1;
	const LENGTH = 2;
	
	// used to determine nesting levels
	private $GROUPS = '/\\(/';//g
	private $SUB_REPLACE = '/\\$\\d/';
	private $INDEXED = '/^\\$\\d+$/';
	private $TRIM = '/([\'"])\\1\\.(.*)\\.\\1\\1$/';
	private $ESCAPE = '/\\\./';//g
	private $QUOTE = '/\'/';
	private $DELETED = '/\\x01[^\\x01]*\\x01/';//g
	
	public function add($expression, $replacement = '') {
		// count the number of sub-expressions
		//  - add one because each pattern is itself a sub-expression
		$length = 1 + preg_match_all($this->GROUPS, $this->_internalEscape((string)$expression), $out);
		
		// treat only strings $replacement
		if (is_string($replacement)) {
			// does the pattern deal with sub-expressions?
			if (preg_match($this->SUB_REPLACE, $replacement)) {
				// a simple lookup? (e.g. "$2")
				if (preg_match($this->INDEXED, $replacement)) {
					// store the index (used for fast retrieval of matched strings)
					$replacement = (int)(substr($replacement, 1)) - 1;
				} else { // a complicated lookup (e.g. "Hello $2 $1")
					// build a function to do the lookup
					$quote = preg_match($this->QUOTE, $this->_internalEscape($replacement))
					         ? '"' : "'";
					$replacement = array(
						'fn' => '_backReferences',
						'data' => array(
							'replacement' => $replacement,
							'length' => $length,
							'quote' => $quote
						)
					);
				}
			}
		}
		// pass the modified arguments
		if (!empty($expression)) $this->_add($expression, $replacement, $length);
		else $this->_add('/^$/', $replacement, $length);
	}
	
	public function exec($string) {
		// execute the global replacement
		$this->_escaped = array();
		
		// simulate the _patterns.toSTring of Dean
		$regexp = '/';
		foreach ($this->_patterns as $reg) {
			$regexp .= '(' . substr($reg[self::EXPRESSION], 1, -1) . ')|';
		}
		$regexp = substr($regexp, 0, -1) . '/';
		$regexp .= ($this->ignoreCase) ? 'i' : '';
		
		$string = $this->_escape($string, $this->escapeChar);
		$string = preg_replace_callback(
			$regexp,
			array(
				&$this,
				'_replacement'
			),
			$string
		);
		$string = $this->_unescape($string, $this->escapeChar);
		
		return preg_replace($this->DELETED, '', $string);
	}
		
	public function reset() {
		// clear the patterns collection so that this object may be re-used
		$this->_patterns = array();
	}

	// private
	private $_escaped = array();  // escaped characters
	private $_patterns = array(); // patterns stored by index
	
	// create and add a new pattern to the patterns collection
	private function _add() {
		$arguments = func_get_args();
		$this->_patterns[] = $arguments;
	}
	
	// this is the global replace function (it's quite complicated)
	private function _replacement($arguments) {
		if (empty($arguments)) return '';
		
		$i = 1; $j = 0;
		// loop through the patterns
		while (isset($this->_patterns[$j])) {
			$pattern = $this->_patterns[$j++];
			// do we have a result?
			if (isset($arguments[$i]) && ($arguments[$i] != '')) {
				$replacement = $pattern[self::REPLACEMENT];
				
				if (is_array($replacement) && isset($replacement['fn'])) {
					
					if (isset($replacement['data'])) $this->buffer = $replacement['data'];
					return call_user_func(array(&$this, $replacement['fn']), $arguments, $i);
					
				} elseif (is_int($replacement)) {
					return $arguments[$replacement + $i];
				
				}
				$delete = ($this->escapeChar == '' ||
				           strpos($arguments[$i], $this->escapeChar) === false)
				        ? '' : "\x01" . $arguments[$i] . "\x01";
				return $delete . $replacement;
			
			// skip over references to sub-expressions
			} else {
				$i += $pattern[self::LENGTH];
			}
		}
	}
	
	private function _backReferences($match, $offset) {
		$replacement = $this->buffer['replacement'];
		$quote = $this->buffer['quote'];
		$i = $this->buffer['length'];
		while ($i) {
			$replacement = str_replace('$'.$i--, $match[$offset + $i], $replacement);
		}
		return $replacement;
	}
	
	private function _replace_name($match, $offset){
		$length = strlen($match[$offset + 2]);
		$start = $length - max($length - strlen($match[$offset + 3]), 0);
		return substr($match[$offset + 1], $start, $length) . $match[$offset + 4];
	}
	
	private function _replace_encoded($match, $offset) {
		return $this->buffer[$match[$offset]];
	}
	
	
	// php : we cannot pass additional data to preg_replace_callback,
	// and we cannot use &$this in create_function, so let's go to lower level
	private $buffer;
	
	// encode escaped characters
	private function _escape($string, $escapeChar) {
		if ($escapeChar) {
			$this->buffer = $escapeChar;
			return preg_replace_callback(
				'/\\' . $escapeChar . '(.)' .'/',
				array(&$this, '_escapeBis'),
				$string
			);
			
		} else {
			return $string;
		}
	}
	private function _escapeBis($match) {
		$this->_escaped[] = $match[1];
		return $this->buffer;
	}
	
	// decode escaped characters
	private function _unescape($string, $escapeChar) {
		if ($escapeChar) {
			$regexp = '/'.'\\'.$escapeChar.'/';
			$this->buffer = array('escapeChar'=> $escapeChar, 'i' => 0);
			return preg_replace_callback
			(
				$regexp,
				array(&$this, '_unescapeBis'),
				$string
			);
			
		} else {
			return $string;
		}
	}
	private function _unescapeBis() {
		if (isset($this->_escaped[$this->buffer['i']])
			&& $this->_escaped[$this->buffer['i']] != '')
		{
			 $temp = $this->_escaped[$this->buffer['i']];
		} else {
			$temp = '';
		}
		$this->buffer['i']++;
		return $this->buffer['escapeChar'] . $temp;
	}
	
	private function _internalEscape($string) {
		return preg_replace($this->ESCAPE, '', $string);
	}
}
