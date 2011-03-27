<?php
/**
 * Represents a Plex TV Show Episode
 **/
class PlexItem_Episode extends PlexItem {
	
	protected $type = 'episode';
	protected $seasonNumber = null;
	protected $episodeNumber = null;
	
	public function setSeasonNumber($val) { if(!$val) return $this; $this->seasonNumber = absint($val); return $this; }
	public function getSeasonNumber() { return $this->seasonNumber; }
	
	public function setEpisodeNumber($val) { if(!$val) return $this; $this->episodeNumber = absint($val); return $this; }
	public function getEpisodeNumber() { return $this->episodeNumber; }
	
	
	public function _parseXMLToItem($xml_array, $api) {
		
		$xml = $xml_array['xml'];
		$season_number = $xml_array['season'];
		
		$attr = $xml->attributes();
		$item = new self;
		
		$item
			->setKey($attr->ratingKey)
			->setGUID($attr->guid)
			->setSeasonNumber($season_number)
			->setEpisodeNumber($attr->index)
			->setTitle($attr->title)
			->setTitleSort( isset($attr->titleSort) ? $attr->titleSort : $attr->title )
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
		;
		
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
	
	
} // end class: PlexItem_Episode
