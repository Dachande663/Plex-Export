<?php
/**
 * Represents a Plex Movie
 **/
class PlexItem_Movie extends PlexItem {
	
	protected $type = 'movie';
	protected $tagline = null;
	
	public function setTagline($val) { if($val=='') return $this; $this->tagline = trim($val); return $this; }
	public function getTagline() { return $this->tagline; }
	
	public function _parseXMLToItem($xml, $api) {
		$attr = $xml->attributes();
		$item = new self;
		$item
			->setKey($attr->ratingKey)
			->setSectionKey($this->getKey())
			->setGUID($attr->guid)
			->setTitle($attr->title)
			->setTitleSort( isset($attr->titleSort) ? $attr->titleSort : $attr->title )
			->setTagline($attr->tagline)
			->setSummary($attr->summary)
			->setRating($attr->rating)
			->setUserRating($attr->userRating)
			->setStudio($attr->studio)
			->setContentRating($attr->contentRating)
			->setViewCount($attr->viewCount)
			->setDuration($attr->duration)
			->setLastViewedAt($attr->lastViewedAt)
			->setOriginallyAvailableAt($attr->originallyAvailableAt)
			->setAddedAt($attr->addedAt)
			->setUpdatedAt($attr->updatedAt)
			->addImage('thumb', $api->build_url($attr->thumb))
			->addImage('art', $api->build_url($attr->art))
		;
		
		if(isset($xml->Genre)) foreach($xml->Genre as $obj) $item->addObjectOfType('genre', $obj->attributes()->id, $obj->attributes()->tag);
		if(isset($xml->Writer)) foreach($xml->Writer as $obj) $item->addObjectOfType('writer', $obj->attributes()->id, $obj->attributes()->tag);
		if(isset($xml->Director)) foreach($xml->Director as $obj) $item->addObjectOfType('director', $obj->attributes()->id, $obj->attributes()->tag);
		if(isset($xml->Role)) foreach($xml->Role as $obj) $item->addObjectOfType('role', $obj->attributes()->id, $obj->attributes()->tag);
		
		if(isset($xml->Media)) {
			$media = PlexMedia::_parseXMLToMedia($xml->Media);
			if(!$media) break;
			$item->setMediaObject($media);
		}
		
		return $item;
	} // end func: _parseXMLToItem
	
} // end class: PlexItem_Movie
