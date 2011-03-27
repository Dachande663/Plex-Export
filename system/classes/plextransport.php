<?php
/**
 * Multi_curl wrapper
 **/
class PlexTransport {
	
	const MAX_SIMULTANEOUS_CONNECTIONS = 96; # 128 usually, give ourselves some leeway
	
	
	/**
	 * Get a server page as XML object
	 **/
	public function get_xml($url) {
		$str = self::get($url);
		if(!$str) return false;
		$xml = @simplexml_load_string($str);
		if(!$xml) return false;
		return $xml;
	} // end func: get_xml
	
	
	/**
	 * Get a server page as XML object
	 **/
	public function multi_get_xml($urls, $max_chunk_size=0, $chunk_interval_sleep=0) {
		$strs = self::multi_get($urls, $max_chunk_size, $chunk_interval_sleep);
		if(!$strs) return false;
		$xmls = array();
		foreach($strs as $i=>$str) {
			if(!$str) {
				$xmls[$i] = false;
				continue;
			}
			$xml = @simplexml_load_string($str);
			$xmls[$i] = ($xml)?$xml:false;
		} // end foreach STRS
		return $xmls;
	} // end func: multi_get_xml
	
	
	/**
	 * Get a server page
	 **/
	public function get($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($http_status!=200) return false;
		curl_close($ch);
		if(!$output or strlen($output)==0) return false;
		return $output;
	} // end func: get
	
	
	/**
	 * Get multiple pages using multi_curl
	 **/
	public function multi_get($urls, $max_chunk_size=0, $chunk_interval_sleep=0) {
		if(!is_array($urls)) return false;
		
		$num_urls = count($urls);
		if($num_urls<=0) return false;
		
		$max_chunk_size = ($max_chunk_size>0 and $max_chunk_size<=self::MAX_SIMULTANEOUS_CONNECTIONS) ? $max_chunk_size : self::MAX_SIMULTANEOUS_CONNECTIONS;
		$url_batches = array_chunk($urls, $max_chunk_size, true);
		$num_batches_remaining = count($url_batches);
		
		$responses = array();
		foreach($url_batches as $batch) {
			$batch_response = self::multi_get_sub($batch);
			$responses = $responses + $batch_response;
			$num_batches_remaining--;
			if($num_batches_remaining>0) sleep($chunk_interval_sleep); # give the server breathing room
		}
		
		return $responses;
	} // end func: multi_get
	
	
	
	/**
	 * Do the actual work of multi_get on a smaller batch
	 **/
	private function multi_get_sub($urls) {
		$master = curl_multi_init();
		$reqs = $responses = array();
		
		foreach($urls as $i=>$url) {
			$responses[$i] = false;
			$reqs[$i] = curl_init($url);
			curl_setopt($reqs[$i], CURLOPT_RETURNTRANSFER, 1);
			curl_multi_add_handle($master, $reqs[$i]);
		}
		
		$running = 0;
		do {
			curl_multi_exec($master, $running);
		} while ($running > 0);
		
		foreach($reqs as $i=>$ch) {
			$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if($http_status==200) {
				$responses[$i] = curl_multi_getcontent($ch);
			} else {
				$responses[$i] = false;
			}
			curl_multi_remove_handle($master, $ch);
		}
		
		curl_multi_close($master);
		return $responses;
	} // end func: multi_get_sub
	
	
} // end class: PlexTransport
