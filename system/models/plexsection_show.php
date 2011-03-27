<?php
/**
 * Represents a Plex TV Show Section
 **/
class PlexSection_Show extends PlexSection {
	
	protected $type = 'show';
	
	
	/**
	 * Internal method: load and parse all items in this section
	 **/
	public function _loadItemsFromPlex($api) {
		
		# First call /library/sections/KEY/all to get all of the item Keys in this section
		$items_url = $api->build_url('library/sections/'.$this->getKey().'/all');
		$items_list = $api->transport->get_xml($items_url);
		if(!$items_list) throw new Exception('Could not retrieve item list for section from Plex: '.$all_sections_url);
		
		# Get all individual show metadatas AND show trees (seasons & episodes)
		$item_urls = array();
		foreach($items_list->Directory as $item_data) {
			$item_key = absint($item_data->attributes()->ratingKey);
			$item_urls[$item_key] = $api->build_url('library/metadata/'.$item_key);
			$item_urls['t'.$item_key] = $api->build_url('library/metadata/'.$item_key.'/tree');
		}
		
		# Get all TV shows and trees
		#$item_urls = array_slice($item_urls, 0, 4, true); # @todo debug
		$item_xmls = $api->transport->multi_get_xml($item_urls);
		
		# Finally, process each item
		$items = array();
		foreach($item_xmls as $xml_key=>$item_xml) {
			if($xml_key[0]=='t') continue; # skip tree results
			if(!$item_xml or !$item_xmls['t'.$xml_key]) continue;
			$item = PlexItem_Show::_parseXMLToItem(array('xml'=>$item_xml->Directory, 'xml_tree'=>$item_xmls['t'.$xml_key]), $api);
			if(!$item) continue;
			$this->addItem($item);
		}
		
		$this->onAddItemsComplete(); # apply sorts etc
		
		return true;
		
	} // end func: _loadItemsFromPlex
	
	
} // end class: PlexSection_Show
