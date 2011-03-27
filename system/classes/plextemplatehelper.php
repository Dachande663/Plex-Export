<?php

/**
 * Helper class for Plex Templates
 **/
class PlexTemplateHelper {
	
	private $options = false;
	private $files_to_copy = array();
	private $files_to_save = array();
	private $images_to_save = array();
	
	
	
	/**
	 * Add an image as transcoded by Plex
	 * - If image already exists and is not newer, skip
	 **/
	public function makeItemImage($file, $item, $type, $width=0, $height=0) {
		$this->images_to_save[$file] = array('file' => $file, 'item' => $item, 'type' => $type, 'width' => $width, 'height' => $height);
	} // end func: makeItemImage
	
	
	
	/**
	 * Include an existing file in the output
	 **/
	public function copyTemplateFiles($files, $force_overwrite=false) {
		$files = (array) $files;
		foreach($files as $file) $this->files_to_copy[$file] = array('file'=>$file, 'force_overwrite'=>(bool)$force_overwrite);
	} // end func: copyTemplateFiles
	
	
	
	/**
	 * Save $data to $file in output dir
	 **/
	public function makeFile($file, $str) {
		$this->files_to_save[$file] = array('file'=>$file, 'data'=>(string)$str);
	} // end func: makeFile
	
	
	
	/**
	 * Use a given $template_file and return the output as a string
	 **/
	public function getTemplate($template_file, $data=false) {
		$template_file = $this->options->template_dir.$template_file.'.php';
		if(!file_exists($template_file)) throw new Exception('Could not open template file: '.$template_file);
		if($data) extract($data);
		
		ob_start();
		include $template_file;
		$output = ob_get_clean();
		
		return $output;
	} // end func: getTemplate
	
	
	
	/**
	 * Returns true if file exists in output dir (or will)
	 **/
	public function file_exists($file) {
		if(array_key_exists($file, $this->files_to_copy)) return true;
		if(array_key_exists($file, $this->files_to_save)) return true;
		if(array_key_exists($file, $this->images_to_save)) return true;
		return false;
	} // end func: file_exists
	
	
	
	/**
	 * Get a theme option as passed in by CLI
	 **/
	public function getThemeOption($option, $default=false) {
		$opt_key = 'theme_opt_'.str_replace('-','_',$option);
		if(isset($this->options->$opt_key)) return $this->options->$opt_key;
		return $default;
	} // end func: getThemeOption
	
	
	
	### INTERNAL METHODS, DO NOT USE THESE ###
	
	
	/**
	 * Constructor
	 **/
	public function __construct($options) {
		$this->options = $options;
	} // end func: __construct
	
	
	/**
	 * Internal function: return all data to be written for template
	 **/
	public function _getFinalTemplateOutput() {
		
		$output = array(
			'files_to_copy' => $this->files_to_copy,
			'files_to_save' => $this->files_to_save,
			'images_to_save' => $this->images_to_save
		);
		
		return $output;
	} // end func: _getFinalTemplateOutput
	
	
} // end class: PlexTemplateHelper