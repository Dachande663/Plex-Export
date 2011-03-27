<?php
/**
 * Represents a Plex Movie Section
 **/
class PlexSection_Movie extends PlexSection {
	
	protected $type = 'movie';
	
	
	/**
	 * Internal method: load and parse all items in this section
	 **/
	public function _loadItemsFromPlex($plexapi) {
		
		# First call /library/sections/KEY/all to get all of the item Keys in this section
		$items_url = $plexapi->build_url('library/sections/'.$this->getKey().'/all');
		$items_list = $plexapi->transport->get_xml($items_url);
		if(!$items_list) throw new Exception('Could not retrieve item list for section from Plex: '.$all_sections_url);
		
		# Then get the individual metadata for each item (which includes everything in the first call, hence ignoring its data)
		$item_urls = array();
		foreach($items_list->Video as $item_data) {
			$item_key = absint($item_data->attributes()->ratingKey);
			$item_urls[$item_key] = $plexapi->build_url('library/metadata/'.$item_key);
		}
		$item_xmls = $plexapi->transport->multi_get_xml($item_urls);
		
		# And finally, process each item and add it to the section
		$items = array();
		foreach($item_xmls as $item_xml) {
			if(!$item_xml) continue;
			$item = PlexItem_Movie::_parseXMLToItem($item_xml->Video, $plexapi);
			if(!$item) continue;
			$this->addItem($item);
		}
		
		$this->onAddItemsComplete(); # apply sorts etc
		
		return true;
		
	} // end func: _loadItemsFromPlex
	
	
} // end class: PlexSection_Movie
