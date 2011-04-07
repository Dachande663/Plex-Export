<?php

class Template_Bacon {
	
	
	/**
	 * On load
	 **/
	public function generate() {
		
		$files = array(
			'assets/js/jquery.1.5.2.min.js',
			'assets/js/raphael.min.js',
			'assets/js/dracula.graffle.js',
			'assets/js/dracula.graph.js',
			'assets/js/jquery.autoSuggest.js',
			'assets/js/app.js',
			
			'assets/css/style.css',
			
			'assets/images/background.jpg',
			'assets/images/container-bg.png',
			'assets/images/default.png',
			'assets/images/favicon.ico',
			'assets/images/logo.png',
			'assets/images/spinner.gif'
		);
		
		$this->template->copyTemplateFiles($files);
		
		$data = $this->template->getTemplate('data', array('library'=>$this->library));
		$this->template->makeFile('data.csv', $data);
		
		$html = $this->template->getTemplate('index');
		$this->template->makeFile('index.html', $html);
		
	} // end func: init
	
	
} // end class: Template_Bacon