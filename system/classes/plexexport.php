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
		_log('Gathering item information (this may take a while)');
		$library = $api->get_library($this->options->sections);
		# @todo cache library
		#file_put_contents('library.serialized.php', serialize($library));
		#$library = unserialize(file_get_contents($this->dir_root.'library.serialized.php'));
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
         Location: '.$this->options->output_dir);
		
		
	} // end func: init
	
	
	
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
		
		$options->flush_export = (bool) $options->flush_export;
		$options->lang_dir = $this->dir_root.'system/lang/';
		
		return $options;
		
	} // end func: parse_arguments
	
	
	
} // end class: PlexExport