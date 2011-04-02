<?php

/**
 * PlexExport Controller
 **/
class PlexExport {
	
	const DEFAULT_PLEX_URL = 'http://localhost:32400/';
	const DEFAULT_TEMPLATE = 'isotope';
	const DEFAULT_OUTPUT_DIR = 'export';
	const DEFAULT_LANG = 'en_US';
	
	private $dir_root = '';
	private $dir_templates = '';
	private $options = array();
	private $version = '2.0.1';
	
	
	
	/**
	 * Constructor
	 **/
	public function __construct() {
		$cwd = dirname(__FILE__);
		$cwd = substr($cwd, 0, -14); # remove system/classes
		$this->dir_root = $cwd;
		$this->dir_templates = $this->dir_root.'templates/';
		$this->dir_cache = $this->dir_root.'cache/';
	} // end func: __construct
	
	
	
	/**
	 * Begin PlexExport
	 **/
	public function init($arguments) {
		
		_log('Welcome to Plex Export v'.$this->version);
		$this->options = $this->parse_arguments($arguments);
		$api = new PlexAPI($this->options->plex_url);
		$mofile = $this->options->lang_dir.$this->options->lang.'.mo';
		gettext_load_domain($this->options->lang, $mofile);
		
		
		_log('Task 1 of 2: Scanning Plex Media Server Library');
		$library = $this->load_library_from_cache();
		$cache_hit = true;
		if(!$library) {
			_log('Gathering item information (this may take a while)');
			$library = $api->get_library($this->options->sections);
			if($library) {
				$this->add_library_to_cache($library);
				$cache_hit = false;
			}
		} else {
			_log('Reusing last scan from cache');
		}
		
		
		if(!$library or $library->getNumSections()==0 or $library->getNumItems()==0) _errord('No sections or items found in Library, aborting');
		_log('Library scan completed, found '.$library->getNumItems().' '.inflect($library->getNumItems(),'item').' in '.$library->getNumSections().' '.inflect($library->getNumSections(),'section'));
		
		
		_log('Task 2 of 2: Generating Plex Export Template');
		$renderer = new PlexTemplateGenerator($this->options, $api, $library);
		$renderer->render();
		_log('Template generated successfully');
		
		
		_log('Your Plex Export has been successfully generated!
         Library: '.$library->getFriendlyName().'
         Sections: '.$library->getNumSections().'
         Items: '.$library->getNumItems().'
         Template: '.$this->options->template.'
         Time: '.get_time_elapsed().' seconds
         Cache: '.( ($cache_hit) ? 'Yes' : 'No' ).'
         Location: '.$this->options->output_dir);
		
		
	} // end func: init
	
	
	
	/**
	 * Loads a cached library from disk or returns false if invalid
	 * @todo clear existing cache
	 **/
	private function load_library_from_cache() {
		
		if($this->options->flush_library) return false;
		
		$cache_file = $this->get_cache_filename();
		if(!file_exists($cache_file)) return false;
		
		$cache_created = filemtime($cache_file);
		if($cache_created==0 or $cache_created + $this->options->library_cache_for < time()) return false;
		
		$cache_str = file_get_contents($cache_file);
		if(!$cache_str or strlen($cache_str)==0) return false;
		
		$cache_data = unserialize($cache_str);
		if(!$cache_data) return false;
		
		$class = get_class($cache_data);
		if($class != 'PlexLibrary') return false;
		
		return $cache_data;
	} // end func: load_library_from_cache
	
	
	
	/**
	 * Caches a library instance to disk
	 **/
	private function add_library_to_cache($library) {
		
		if(!file_exists($this->dir_cache)) {
			$mk = mkdir($this->dir_cache, 0777, true);
			if(!$mk) return false;
		}
		
		$cache_file = $this->get_cache_filename();
		$cache_str = serialize($library);
		$result = file_put_contents($cache_file, $cache_str);
		if($result==0) return false;
		
		return true;
	} // end func: add_library_to_cache
	
	
	
	/**
	 * Returns cache filename, based on current options
	 **/
	private function get_cache_filename() {
		
		# Use only options that when changed will affect the library
		$opts = array(
			'plex-url'=>$this->options->plex_url,
			'sections'=>$this->options->sections
		);
		$cache_file = $this->dir_cache.'library.'.md5(serialize($opts)).'.cache';
		
		return $cache_file;
	} // end func: get_cache_filename
	
	
	
	/**
	 * Parse an input array of arguments
	 **/
	private function parse_arguments($arguments) {
		
		$arguments = (array) $arguments;
		
		$defaults = array(
			'plex_url' => self::DEFAULT_PLEX_URL,
			'sections' => false,
			'template' => self::DEFAULT_TEMPLATE,
			'output_dir' => $this->dir_root.self::DEFAULT_OUTPUT_DIR.'/',
			'lang' => 'en_US',
			'flush' => false,
			'flush_library' => false,
			'library_cache_for' => 3600,
			'flush_export' => false
		);
		
		$options = (object) array_merge($defaults, $arguments);
		
		if(substr($options->plex_url, 0, 7)!='http://') $options->plex_url = 'http://'.$options->plex_url;
		if(substr($options->plex_url, -1)!='/') $options->plex_url .= '/';
		
		if($options->sections!='') {
			$sections = explode(',', $options->sections);
			$sections = array_map('trim', $sections); # remove whitespace
			$sections = array_filter($sections); # remove any empty sections
			if(count($sections)==0) $sections = false;
			$options->sections = $sections;
		}
		
		$options->template = preg_replace('/[^a-zA-Z0-9_\s]/', '', $options->template);
		$options->template_dir = $this->dir_templates.$options->template.'/';
		$options->template_file = $options->template_dir.$options->template.'.php';
		if(!file_exists($options->template_file)) throw new Exception('The selected theme could not be found: '.$options->template);
		
		if(substr($options->output_dir, -1)!='/') $options->output_dir .= '/';
		if(!file_exists($options->output_dir)) {
			$make_output_dir = mkdir($options->output_dir, 0777, true);
			if(!$make_output_dir) throw new Exception('The selected output location could not be found: '.$options->output_dir);
		}
		
		$options->flush = (bool) $options->flush;
		$options->flush_library = (bool) $options->flush_library;
		$options->flush_export = (bool) $options->flush_export;
		if($options->flush) {
			$options->flush_library = true;
			$options->flush_export = true;
		}
		
		$options->library_cache_for = absint($options->library_cache_for);
		if($options->library_cache_for<=0 or $options->library_cache_for>86400) $options->library_cache_for = $defaults['library_cache_for'];
		
		$options->lang_dir = $this->dir_root.'system/lang/';
		
		return $options;
		
	} // end func: parse_arguments
	
	
	
} // end class: PlexExport