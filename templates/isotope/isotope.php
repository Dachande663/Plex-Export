<?php

class Template_Isotope {
	
	
	/**
	 * On load
	 **/
	public function generate() {
		
		
		$js = array(
			'assets/js/jquery.1.5.1.min.js',
			'assets/js/jquery.isotope.js',
			'assets/js/jquery.isotope.min.js',
			'assets/js/utils.js'
		);
		
		$css = array(
			'assets/css/style.css',
			'assets/css/iphone.css',
			'assets/css/iphone-retina.css',
		);
		
		$images = array(
			'assets/images/background.jpg',
			'assets/images/container-bg.png',
			'assets/images/default.png',
			'assets/images/favicon.ico',
			'assets/images/icon-movie.png',
			'assets/images/icon-show.png',
			'assets/images/logo.png',
			'assets/images/spinner.gif',
			'assets/images/stars.png',
		);
		
		$fancybox = array(
			'assets/js/jquery.fancybox.1.3.4.min.js',
			'assets/css/jquery.fancybox.1.3.4.css',
			'assets/images/fancybox/blank.gif',
			'assets/images/fancybox/fancy_close.png',
			'assets/images/fancybox/fancy_loading.png',
			'assets/images/fancybox/fancy_nav_left.png',
			'assets/images/fancybox/fancy_nav_right.png',
			'assets/images/fancybox/fancy_shadow_e.png',
			'assets/images/fancybox/fancy_shadow_n.png',
			'assets/images/fancybox/fancy_shadow_ne.png',
			'assets/images/fancybox/fancy_shadow_nw.png',
			'assets/images/fancybox/fancy_shadow_s.png',
			'assets/images/fancybox/fancy_shadow_se.png',
			'assets/images/fancybox/fancy_shadow_sw.png',
			'assets/images/fancybox/fancy_shadow_w.png',
			'assets/images/fancybox/fancy_title_left.png',
			'assets/images/fancybox/fancy_title_main.png',
			'assets/images/fancybox/fancy_title_over.png',
			'assets/images/fancybox/fancy_title_right.png',
			'assets/images/fancybox/fancybox-x.png',
			'assets/images/fancybox/fancybox-y.png',
			'assets/images/fancybox/fancybox.png'
		);
		
		
		$this->template->copyTemplateFiles($js);
		$this->template->copyTemplateFiles($css);
		$this->template->copyTemplateFiles($images);
		$this->template->copyTemplateFiles($fancybox);
		
		
		foreach($this->library->getSections() as $section) {
			$template = false;
			switch($section->getType()) {
				case 'movie': $template = 'item_movie'; break;
				case 'show': $template = 'item_show'; break;
			}
			if(!$template) continue;
			foreach($section->getItems() as $item) {
				$this->template->makeItemImage('images/thumb_'.$item->getKey().'.jpeg', $item, 'thumb', 150, 225);
				$html = $this->template->getTemplate($template, array('item'=>$item, 'section'=>$section, 'library'=>$this->library));
				$this->template->makeFile('items/'.$item->getKey().'.html', $html);
			}
		}
		
		
		$html = $this->template->getTemplate('index', array('library'=>$this->library));
		$this->template->makeFile('index.html', $html);
		
		#$color_scheme = $this->template->getThemeOption('color');
		
		
	} // end func: init
	
	
} // end class: Template_Isotope