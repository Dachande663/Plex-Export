<?php
/*
	Plex Export
	Luke Lanchester <luke@lukelanchester.com>
	
	A CLI script to export information from your Plex library.
	Usage:
		php cli.php [-plex-url="http://your-plex-library:32400"] [-data-dir="plex-data"] [-sections=1,2,3]
	
*/
$timer_start = microtime(true);
$plex_export_version = 1;
ini_set('memory_limit', '128M');
set_error_handler('plex_error_handler');
error_reporting(E_ALL ^ E_NOTICE | E_WARNING);


plex_log('Welcome to the Plex Exporter v'.$plex_export_version);


// Load options
	$defaults = array(
		'plex-url' => 'http://localhost:32400',
		'data-dir' => 'plex-data',
		'thumbnail-width' => 150,
		'sections' => 'all'
	);
	$options = hl_parse_arguments($_SERVER['argv'], $defaults);
	if(substr($options['plex-url'],-1)!='/') $options['plex-url'] .= '/'; // Always have a trailing slash
	if($options['sections'] == 'all') {
		$options['sections'] = false;
	} else {
		$sections = array_filter(array_map('intval', explode(',',$options['sections'])));
		if(count($sections)>0) {
			$options['sections'] = $sections;
		} else {
			$options['sections'] = false;
		}
	}
	
	
// Run in script directory, regardless of current working directory
	$options['absolute-data-dir'] = dirname(__FILE__).'/'.$options['data-dir'];
	
	
// Check everything is enabled as necessary
	check_dependancies();


// Load details about all sections
	$sections = load_all_sections();
	if(!$sections) {
		plex_error('Could not load section data, aborting');
		exit();
	}
	$num_sections = count($sections);
	
	print_r($sections);
	die();


// Load details about each section
	
	$total_items = 0;
	
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
		$total_items += $num_items;
		
		plex_log('Analysing media items in section...');
		
		$sorts_title = $sorts_release = $sorts_rating = array();
		$raw_section_genres = array();
		
		foreach($items as $key=>$item) {
			$sorts_title[$key] = (substr(strtolower($item['title']),0,4)=='the ')?substr($item['title'],4):$item['title'];
			$sorts_release[$key] = @strtotime($item['release_date']);
			$sorts_rating[$key] = ($item['user_rating'])?$item['user_rating']:$item['rating'];
			if(is_array($item['genre']) and count($item['genre'])>0) {
				foreach($item['genre'] as $genre) {
					$raw_section_genres[$genre]++;
				}
			}
		}
		
		asort($sorts_title, SORT_STRING);
		asort($sorts_release, SORT_NUMERIC);
		asort($sorts_rating, SORT_NUMERIC);
		$sorts['title_asc'] = array_keys($sorts_title);
		$sorts['release_asc'] = array_keys($sorts_release);
		$sorts['rating_asc'] = array_keys($sorts_rating);
		$sorts['title_desc'] = array_reverse($sorts['title_asc']);
		$sorts['release_desc'] = array_reverse($sorts['release_asc']);
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
		
		$sections[$i]['num_items'] = $num_items;
		$sections[$i]['items'] = $items;
		$sections[$i]['sorts'] = $sorts;
		$sections[$i]['genres'] = $section_genres;
		
		plex_log('Added '.$num_items.' '.hl_inflect($num_items,'item').' from the '.$section['title'].' section');
		
	} // end foreach: $sections


// Output all data
	
	plex_log('Exporting data for '.$num_sections.' '.hl_inflect($num_sections,'section').' containing '.$total_items.' '.hl_inflect($total_items,'item'));
	
	$output = array(
		'status' => 'success',
		'version' => $plex_export_version,
		'last_generated' => time()*1000,
		'total_items' => $total_items,
		'num_sections' => $num_sections,
		'sections' => $sections
	);
	$output = json_encode($output);
	$filename = $options['absolute-data-dir'].'/data.js';
	$bytes_written = file_put_contents($filename, $output);
	
	if(!$bytes_written) {
		plex_error('Could not save JSON data to '.$filename.', please make sure directory is writeable');
		exit();
	}
	
	plex_log('Wrote '.$bytes_written.' bytes to '.$filename);
	
	$timer_end = microtime(true);
	$time_taken = $timer_end - $timer_start;
	plex_log('Plex Export completed in '.round($time_taken,2).' seconds');
	


// Methods

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



function load_data_for_movie($el) {
	
	global $options;
	
	$_el = $el->attributes();
	$key = intval($_el->ratingKey);
	if($key<=0) return false;
	$title = strval($_el->title);
	plex_log('Scanning movie: '.$title);
	
	$thumb = generate_item_thumbnail(strval($_el->thumb), $key, $title);
	
	$item = array(
		'key' => $key,
		'type' => 'movie',
		'thumb' => $thumb,
		'title' => $title,
		'duration' => floatval($_el->duration),
		'view_count' => intval($_el->viewCount),
		'tagline' => ($_el->tagline)?strval($_el->tagline):false,
		'rating' => ($_el->rating)?floatval($_el->rating):false,
		'user_rating' => ($_el->userRating)?floatval($_el->userRating):false,
		'release_year' => ($_el->year)?intval($_el->year):false,
		'release_date' => ($_el->originallyAvailableAt)?strval($_el->originallyAvailableAt):false,
		'content_rating' => ($_el->contentRating)?strval($_el->contentRating):false,
		'summary' => ($_el->summary)?strval($_el->summary):false,
		'studio' => ($_el->studio)?strval($_el->studio):false,
		'genre' => false,
		'director' => false,
		'role' => false,
		'media' => false,
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
	
	return $item;
	
} // end func: load_data_for_movie





function load_data_for_show($el) {
	
	global $options;
	
	$_el = $el->attributes();
	$key = intval($_el->ratingKey);
	if($key<=0) return false;
	$title = strval($_el->title);
	plex_log('Scanning show: '.$title);
	
	$thumb = generate_item_thumbnail(strval($_el->thumb), $key, $title);
	
	$item = array(
		'key' => $key,
		'type' => 'movie',
		'thumb' => $thumb,
		'title' => $title,
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
	foreach($xml->Directory as $el2) {
		if($el2->attributes()->type!='season') continue;
		$season_key = intval($el2->attributes()->ratingKey);
		$season = array(
			'key' => $season_key,
			'title' => strval($el2->attributes()->title),
			'num_episodes' => intval($el2->attributes()->leafCount),
			'index' => intval($el2->attributes()->index)
		);
		$seasons[$season_key] = $season;
	}
	$item['num_seasons'] = count($seasons);
	if($item['num_seasons']>0) $item['seasons'] = $seasons;
	
	return $item;
	
} // end func: load_data_for_show



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
		if($options['sections'] and !in_array($key, $options['sections'])) {
			plex_log('Skipping section: '.$title);
			continue;
		}
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



function load_xml_from_url($url) {
	
	global $options;
	
	if(!@fopen($url, 'r')) {
		plex_error('The Plex library could not be found at '.$options['plex-url']);
		return false;
	}
	
	$xml = @simplexml_load_file($url);
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



function generate_item_thumbnail($thumb_url, $key, $title) {
	
	global $options;
	
	$filename = '/thumb_'.$key.'.png';
	$save_filename = $options['absolute-data-dir'].$filename;
	$return_filename = $options['data-dir'].$filename;
	
	if(file_exists($save_filename)) return $return_filename;
	
	if($thumb_url=='') {
		plex_error('No thumbnail URL was provided for '.$title, ', skipping');
		return false;
	}
	
	$source = $options['plex-url'].substr($thumb_url,1);
	$img_data = @file_get_contents($source);
	if(!$img_data) {
		plex_error('Could not load thumbnail for '.$title,' skipping');
		return false;
	}
	
	$im = imagecreatefromstring($img_data);
	$width = imagesx($im);
	
	if($width > $options['thumbnail-width']) {
		$height = imagesy($im);
		$scale = $width / $options['thumbnail-width'];
		$new_width = $options['thumbnail-width'];
		$new_height = $height / $scale;
		$old_image = $im;
		$im = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($im, $old_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		imagedestroy($old_image);
	}
	
	imagepng($im, $save_filename);
    imagedestroy($im);
	unset($img_data);
	return $return_filename;
	
} // end func: generate_item_thumbnail


function plex_log($str) {
	$str = @date('H:i:s')." $str\n";
	fwrite(STDOUT, $str);
} // end func: plex_log


function plex_error($str) {
	$str = @date('H:i:s')." Error: $str\n";
	fwrite(STDERR, $str);
} // end func: plex_error


function plex_error_handler($errno, $errstr, $errfile=null, $errline=null) {
	if(!(error_reporting() & $errno)) return;
	$str = @date('H:i:s')." Error: $errstr". ($errline?' on line '.$errline:'') ."\n";
	fwrite(STDERR, $str);
} // end func: plex_error_handler


function check_dependancies() {
	global $options;
	$errors = false;
	
	if(!extension_loaded('simplexml')) {
		plex_error('SimpleXML is not enabled');
		$errors = true;
	}
	
	if(!extension_loaded('gd')) {
		plex_error('GD is not enabled');
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


function hl_inflect($num, $single, $plural=false) {
	if($num==1) return $single;
	if($plural) return $plural;
	return $single.'s';
} // end func: hl_inflect

