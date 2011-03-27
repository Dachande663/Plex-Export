<?php
/**
 * Template Generator controller
 **/
class PlexTemplateGenerator {
	
	private $options = false;
	private $api = false; # PlexAPI
	private $library = false; # PlexLibrary
	private $template = false; # PlexTemplate
	private $output_dir = false;
	
	
	
	/**
	 * Constructor
	 **/
	public function __construct($options, $api, $library) {
		
		$this->options = $options;
		$this->api = $api;
		$this->library = $library;
		
		if(!file_exists($this->options->template_file)) throw new Exception('Could not open template: '.$this->options->template_file);
		require_once $this->options->template_file;
		$classname = 'Template_'.ucfirst($this->options->template);
		if(!class_exists($classname)) throw new Exception('Could not load template: '.$this->options->template);
		
		$mofile = $this->options->template_dir.'lang/'.$this->options->lang.'.mo';
		gettext_load_domain($this->options->lang, $mofile);
		
		$this->template = new $classname;
		$this->template->template = new PlexTemplateHelper($this->options);
		$this->template->library = $library;
		
	} // end func: __construct
	
	
	
	
	/**
	 * Generate all necessary files and save to disk
	 **/
	public function render() {
		
		# Trash the existing export
		if($this->options->flush_export) {
			recursive_rmdir($this->options->output_dir);
			_log('Flushed: '.$this->options->output_dir);
		}
		
		$this->template->generate(); # Make Template!
		
		$data = $this->template->template->_getFinalTemplateOutput();
		
		if(count($data['files_to_copy'])>0) {
			_log('Copying '.count($data['files_to_copy']).' files');
			$num = $this->copy_files_from_template_to_output($data['files_to_copy']);
			_log( ($num==0) ? 'No files needed copying' : 'Saved '.$num.' files' );
		}
		
		if(count($data['files_to_save'])>0) {
			_log('Creating '.count($data['files_to_save']).' files');
			$num = $this->create_files_in_output($data['files_to_save']);
			_log( ($num==0) ? 'No files needed saving' : 'Saved '.$num.' files' );
		}
		
		if(count($data['images_to_save'])>0) {
			_log('Getting '.count($data['images_to_save']).' images (this may take a while)');
			$num = $this->create_item_images_in_output($data['images_to_save']);
			_log( ($num==0) ? 'No images needed saving' : 'Saved '.$num.' images' );
		}
		
	} // end func: render
	
	
	
	
	/**
	 * Copy files from /template to /output, check overwriting
	 **/
	private function copy_files_from_template_to_output($files) {
		
		$return_num = 0;
		
		foreach($files as $filedata) {
			
			$source = $this->options->template_dir.$filedata['file'];
			$dest = $this->options->output_dir.$filedata['file'];
			if(!file_exists($source)) throw new Exception('Could not find template file: '.$source);
			
			# Check if file needs to be overwritten
			if(!$filedata['force_overwrite'] and file_exists($dest)) {
				$source_modified = filemtime($source);
				$dest_modified = filemtime($dest);
				if($source_modified <= $dest_modified) continue; # skip any files that haven't been modified
			}
			
			# Check each output directory exists
			$dest_dir = dirname($dest);
			if(!file_exists($dest_dir)) {
				$mk = mkdir($dest_dir, 0777, true);
				if(!$mk) throw new Exception('Could not create export directory: '.$dest_dir);
			}
			
			$cp = copy($source, $dest);
			if(!$cp) throw new Exception('Could not create export file: '.$dest);
			$return_num++;
			
		}
		
		return $return_num;
		
	} // end func: copy_files_from_template_to_output
	
	
	
	
	/**
	 * Create necessary files
	 **/
	private function create_files_in_output($files) {
		
		$return_num = 0;
		
		foreach($files as $filedata) {
			
			$dest = $this->options->output_dir.$filedata['file'];
			
			# Check each output directory exists
			$dest_dir = dirname($dest);
			if(!file_exists($dest_dir)) {
				$mk = mkdir($dest_dir, 0777, true);
				if(!$mk) throw new Exception('Could not create export directory: '.$dest_dir);
			}
			
			$pc = file_put_contents($dest, $filedata['data']);
			if($pc===false) throw new Exception('Could not create export file: '.$dest);
			$return_num++;
			
		}
		
		return $return_num;
		
	} // end func: create_files_in_output
	
	
	
	
	/**
	 * Create images from items IF NECESSARY
	 **/
	private function create_item_images_in_output($images) {
		$return_num = 0;
		$actual_images_to_get = array();
		
		# Determine which images actually need updating
		foreach($images as $img) {
			
			$img = (object) $img;
			$image_url = $img->item->getImageURL($img->type);
			
			if(!$image_url) continue;
			$dest = $this->options->output_dir.$img->file;
			
			if(file_exists($dest)) {
				$plex_file_modified = $img->item->getImageTimestamp($img->type);
				$export_file_modified = filemtime($dest);
				if($plex_file_modified < $export_file_modified) continue;
			}
			
			$width = absint($img->width);
			$height = absint($img->height);
			$actual_images_to_get[$dest] = array('url'=>$image_url, 'width'=>$width, 'height'=>$height);
			
		}
		
		if(count($actual_images_to_get)==0) return;
		
		# Get all the images
		$image_datas = $this->api->get_transcoded_images($actual_images_to_get);
		if(!$image_datas) throw new Exception('Could not retrieve images from Plex');
		
		# Save images
		foreach($image_datas as $dest=>$image_data) {
			
			if(!$img) return false;
			
			$dest_dir = dirname($dest);
			if(!file_exists($dest_dir)) {
				$mk = mkdir($dest_dir, 0777, true);
				if(!$mk) throw new Exception('Could not create export directory: '.$dest_dir);
			}
			
			$pc = file_put_contents($dest, $image_data);
			if($pc===false) throw new Exception('Could not create export image: '.$dest);
			$return_num++;
		}
		
		return $return_num;
		
	} // end func: create_item_images_in_output
	
	
} // end class: PlexTemplateGenerator