<?php
/**
 * Represents a Plex Item
 **/
abstract class PlexItem {
	
	# Abstract
	protected $type = '';
	abstract public function _parseXMLToItem($xml, $api);
	
	
	# Common
	protected $key = 0;
	protected $sectionKey = 0;
	protected $numSubItems = 1;
	protected $guid = null;
	protected $title = null;
	protected $titleSort = null;
	protected $summary = null;
	protected $rating = null;
	protected $userRating = null;
	protected $studio = null;
	protected $contentRating = null;
	protected $duration = null;
	protected $viewCount = null;
	protected $lastViewedAt = null;
	protected $originallyAvailableAt = null;
	protected $addedAt = null;
	protected $updatedAt = null;
	protected $images = array();
	protected $objects = array(); # writers, directors, actors, genres etc...
	protected $mediaObj = null;
	
	
	public function setKey($val) { $this->key = absint($val); return $this; }
	public function getKey() { return $this->key; }
	
	public function setSectionKey($val) { $this->sectionKey = absint($val); return $this; }
	public function getSectionKey() { return $this->sectionKey; }
	
	public function setSubItemsCount($val) { $this->numSubItems = absint($val); return $this; }
	public function getSubItemsCount() { return $this->numSubItems; }
	
	public function setGUID($val) { if($val=='') return $this; $this->guid = trim($val); return $this; }
	public function getGUID() { return $this->guid; }
	
	public function setTitle($val) { if($val=='') return $this; $this->title = trim($val); return $this; }
	public function getTitle() { return $this->title; }
	
	public function setTitleSort($val) { if($val=='') return $this; $this->titleSort = trim($val); return $this; }
	public function getTitleSort() { return $this->titleSort; }
	
	public function setSummary($val) { if($val=='') return $this; $this->summary = trim($val); return $this; }
	public function getSummary() { return $this->summary; }
	
	public function setRating($val) { if(!$val) return $this; $this->rating = floatval($val); return $this; }
	public function getRating() { return $this->rating; }
	
	public function setUserRating($val) { if(!$val) return $this; $this->userRating = floatval($val); return $this; }
	public function getUserRating() { return $this->userRating; }
	
	public function setStudio($val) { if($val=='') return $this; $this->studio = trim($val); return $this; }
	public function getStudio() { return $this->studio; }
	
	public function setContentRating($val) { if($val=='') return $this; $this->contentRating = trim($val); return $this; }
	public function getContentRating() { return $this->contentRating; }
	
	public function setViewCount($val) { if(!$val) return $this; $this->viewCount = absint($val); return $this; }
	public function getViewCount() { return $this->viewCount; }
	
	public function setLastViewedAt($val) { if(!$val) return $this; $this->lastViewedAt = absint($val); return $this; }
	public function getLastViewedAt($format='U') { if($format=='U') return $this->lastViewedAt; return @date($format, $this->lastViewedAt); }
	
	public function setDuration($val) { if(!$val) return $this; $this->duration = round($val/1000); return $this; }
	public function getDuration() { return $this->duration; }
	
	public function setOriginallyAvailableAt($val) { if(!$val) return $this; $this->originallyAvailableAt = @strtotime($val); return $this; }
	public function getOriginallyAvailableAt($format='U') { if($format=='U') return $this->originallyAvailableAt; return @date($format, $this->originallyAvailableAt); }
	
	public function setAddedAt($val) { if(!$val) return $this; $this->addedAt = absint($val); return $this; }
	public function getAddedAt($format='U') { if($format=='U') return $this->addedAt; return @date($format, $this->addedAt); }
	
	public function setUpdatedAt($val) { if(!$val) return $this; $this->updatedAt = absint($val); return $this; }
	public function getUpdatedAt($format='U') { if($format=='U') return $this->updatedAt; return @date($format, $this->updatedAt); }
	
	public function setMediaObject($mediaObj) { if(!$mediaObj) return $this; $this->mediaObj = $mediaObj; return $this; }
	public function getMedia() { return $this->mediaObj; }
	
	public function addObjectOfType($type, $id, $tag) {
		$id = absint($id);
		if(!$id) return $this;
		$tag = trim($tag);
		if($tag=='') return $this;
		$this->objects[$type][$id] = $tag;
	}
	public function getObjectsOfType($type) {
		if(!isset($this->objects[$type]) or empty($this->objects[$type])) return false;
		return $this->objects[$type];
	}
	public function getNumObjectsOfType($type) {
		if(!isset($this->objects[$type]) or empty($this->objects[$type])) return 0;
		return count($this->objects[$type]);
	}
	
	
	public function addImage($type, $url) {
		$modified = time();
		$matches = array();
		$match = preg_match('/\?t=([0-9]+)/', $url, $matches);
		if($match===1) $modified = (int) $matches[1];
		$this->images[$type] = array(
			'url' => $url,
			'modified' => $modified
		);
		return $this;
	}
	public function getImageURL($type) {
		return (isset($this->images[$type]['url'])) ? $this->images[$type]['url'] : false;
	}
	public function getImageTimestamp($type='thumb') {
		return (isset($this->images[$type]['modified'])) ? $this->images[$type]['modified'] : false;;
	}
	
	
} // end class: PlexItem
