<?php
/**
 * Plex API wrapper
 **/
class PlexAPI {
	
	public $transport = false; # PlexTransport
	private $base_url = '';
	private $all_sections_page = null;
	
	
	/**
	 * Constructor
	 **/
	public function __construct($base_url) {
		
		$this->transport = new PlexTransport;
		
		if($base_url=='') throw new Exception('No Plex API URL was provided');
		if(substr($base_url, 0, 7)!='http://') $base_url = 'http://'.$base_url;
		if(substr($base_url, -1)!='/') $base_url .= '/';
		$this->base_url = $base_url;
		
	} // end func: __construct
	
	
	
	
	/**
	 * Return a complete PlexLibrary object
	 * - Contains selected PlexSections and PlexItems
	 **/
	public function get_library($sections_to_get=false) {
		
		$library = $this->init_library_object();
		
		_log('Found Plex Library on "'.$library->getFriendlyName().'"');
		
		$section_keys = $this->get_section_keys_by_filters($sections_to_get);
		if(!$section_keys) return $library; # no sections found or wanted
		
		_log('Scanning '.count($section_keys).' '.inflect(count($section_keys),'section'));
		
		foreach($section_keys as $section_key) {
			$section = $this->init_section_object($section_key);
			if(!$section) continue;
			
			_log('Scanning section: '.$section->getTitle());
			$section->_loadItemsFromPlex($this); # this is not the nicest way to do this, but is efficient
			if(!$section) continue;
			
			$library->addSection($section);
			_log('Added '.$section->getNumItems().' '.inflect($section->getNumItems(),'item').' from section');
		}
		
		return $library;
		
	} // end func: get_library
	
	
	
	
	/**
	 * Return multiple images from Plex API, scaled to dimensions
	 **/
	public function get_transcoded_images($image_inputs) {
		
		$image_reqs = array();
		foreach($image_inputs as $file=>$img) {
			$image_reqs[$file] = $this->build_url('photo/:/transcode?width='.$img['width'].'&height='.$img['height'].'&url='.urlencode($img['url']));
		}
		
		$image_datas = $this->transport->multi_get($image_reqs);
		
		if(!$image_datas) return false;
		$image_datas = array_filter($image_datas); # remove any that failed
		if(count($image_datas)==0) return false;
		return $image_datas;
		
	} // end func: get_transcoded_images
	
	
	
	
	/**
	 * Return a PlexSection with no PlexItems
	 **/
	private function init_section_object($section_key) {
		
		$all_sections = $this->get_all_sections_data(); # we already have section data, so use it!
		
		foreach($all_sections->Directory as $section_data) {
			$sec_attr = $section_data->attributes();
			if($sec_attr->key != $section_key) continue;
			$section = PlexSection::_parseXMLToSection($section_data);
			if(!$section) return false; # Not a supported PlexSection type
			return $section;
		}
		
		return false;
		
	} // end func: init_section_object
	
	
	
	
	/**
	 * Given an array of section names & keys (or false), return a list of section keys
	 **/
	private function get_section_keys_by_filters($sections_to_get) {
		
		$section_filters = (!$sections_to_get or empty($sections_to_get)) ? false : array_map('strtolower', (array) $sections_to_get);
		$all_sections = $this->get_all_sections_data();
		if(!$all_sections) return false;
		$section_ids = array();
		
		foreach($all_sections->Directory as $section) {
			
			$sec_attr = $section->attributes();
			
			if(!$section_filters) {
				$section_ids[] = absint($sec_attr->key);
				continue;
			}
			
			# Attempt to match section against each filter, title first, then key
			$title = strtolower($sec_attr->title);
			$key = absint($sec_attr->key);
			foreach($section_filters as $section_filter) {
				if($title == $section_filter) {
					$section_ids[] = $key;
					continue 2;
				} elseif($key == $section_filter) {
					$section_ids[] = $key;
					continue 2;
				}
			}
			
		}
		
		if(count($section_ids)==0) return false;
		return $section_ids;
		
	} // end func: get_section_keys_by_filters
	
	
	
	
	/**
	 * Returns the /library/sections data
	 **/
	private function get_all_sections_data() {
		if($this->all_sections_page!==null) return $this->all_sections_page;
		
		$all_sections_url = $this->build_url('library/sections');
		$all_sections = $this->transport->get_xml($all_sections_url);
		if(!$all_sections) throw new Exception('Could not retrieve section list from Plex: '.$all_sections_url);
		if(absint($all_sections->attributes()->size) <= 0) $this->all_sections_page = false;
		
		$this->all_sections_page = $all_sections;
		return $this->all_sections_page;
	} // end func: get_all_sections
	
	
	
	
	/**
	 * Return a very bare PlexLibrary object
	 **/
	private function init_library_object() {
		$url = $this->build_url();
		$xml = $this->transport->get_xml($url);
		if(!$xml) throw new Exception('Could not connect to Plex API: '.$url);
		$attr = $xml->attributes();
		
		$library = new PlexLibrary();
		$library->setFriendlyName($attr->friendlyName)
				->setMachineIdentifier($attr->machineIdentifier)
				->setPlexVersion($attr->version);
		
		return $library;
	} // end func: init_library_object
	
	
	
	
	/**
	 * Build a Plex API URL
	 **/
	public function build_url($path=false) {
		if($path and substr($path, 0, 1)=='/') {
			$path = substr($path, 1);
		}
		return $this->base_url.$path;
	} // end func: build_url
	
	
	
	
} // end class: PlexAPI
