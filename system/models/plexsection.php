<?php
/**
 * Represents a Plex Section
 **/
abstract class PlexSection {
	
	# Abstract
	protected $type = '';
	abstract public function _loadItemsFromPlex($plexapi);
	
	
	# Common
	protected $key = 0;
	protected $title = '';
	protected $updatedAt = 0;
	protected $items = array();
	protected $numItems = 0;
	protected $totalSubItems = 0; # number of items including individual tv episodes etc
	protected $sorts = array(); # genres, directors etc sorted DESC
	
	public function setKey($val) { $this->key = absint($val); return $this; }
	public function setTitle($val) { $this->title = (string) $val; return $this; }
	public function setUpdatedAt($val) { $this->updatedAt = (int) $val; return $this; } # do not abs(), could be negative
	
	public function getKey() { return $this->key; }
	public function getType() { return $this->type; }
	public function getTotalSubItems() { return $this->totalSubItems; }
	public function getTitle() { return $this->title; }
	public function getUpdatedAt() { return $this->updatedAt; }
	
	public function getItems() { return $this->items; }
	public function getNumItems() { return $this->numItems; }
	public function getTotalNumItems() { return $this->numItems; }
	public function getItemByKey($key) {
		if(!array_key_exists($key, $this->items)) return false;
		return $this->items[$key];
	}
	public function addItem($item) {
		if(!array_key_exists($item->getKey(), $this->items)) {
			$this->numItems += 1;
			$this->totalSubItems += $item->getSubItemsCount();
			
			
			if($item->getRating() or $item->getUserRating()) {
				$rating = ($item->getUserRating()>0) ? $item->getUserRating() : $item->getRating();
				$this->addSortValue('rating', floor($rating), floor($rating));
			}
			
			if($item->getNumObjectsOfType('genre')>0) foreach($item->getObjectsOfType('genre') as $id=>$obj) $this->addSortValue('genre', $id, $obj);
			if($item->getNumObjectsOfType('director')>0) foreach($item->getObjectsOfType('director') as $id=>$obj) $this->addSortValue('director', $id, $obj);
			if($item->getNumObjectsOfType('writer')>0) foreach($item->getObjectsOfType('writer') as $id=>$obj) $this->addSortValue('writer', $id, $obj);
			if($item->getNumObjectsOfType('role')>0) foreach($item->getObjectsOfType('role') as $id=>$obj) $this->addSortValue('role', $id, $obj);
			
		}
		$this->items[$item->getKey()] = $item;
	}
	
	
	public function getValuesForSort($type) {
		if(!isset($this->sorts[$type])) return false;
		if($this->sorts[$type]['count']==0) return false;
		return $this->sorts[$type]['values'];
	}
	public function getNumValuesForSort($type) {
		if(!isset($this->sorts[$type])) return false;
		return (int) $this->sorts[$type]['count'];
	}
	public function addSortValue($type, $id, $val=false) {
		if(!isset($this->sorts[$type])) {
			$this->sorts[$type] = array(
				'count' => 0,
				'values' => array()
			);
		}
		if(!isset($this->sorts[$type]['values'][$id])) {
			$this->sorts[$type]['count']++;
			$this->sorts[$type]['values'][$id] = array(
				'label' => ($val) ? $val : $id,
				'count' => 0
			);
		}
		$this->sorts[$type]['values'][$id]['count']++;
	}
	public function onAddItemsComplete() {
		foreach($this->sorts as $key=>$sorts) {
			if($sorts['count']==0) continue;
			uasort($sorts['values'], array($this, 'sortSorts'));
			$this->sorts[$key]['values'] = $sorts['values'];
		}
	}
	public function sortSorts($a, $b) {
		if($a['count'] == $b['count']) return 0;
		return ($a['count'] < $b['count']) ? 1 : -1;
	}
	
	
	
	
	
	
	
	
	
	
	# Parser
	public function _parseXMLToSection($xml) {
		$attr = $xml->attributes();
		
		switch($attr->type) {
			case 'movie': $class = 'PlexSection_Movie'; break;
			case 'show': $class = 'PlexSection_Show'; break;
			default: return false;
		}
		
		$section = new $class;
		$section->setKey($attr->key)
				->setTitle($attr->title)
				->setUpdatedAt($attr->updatedAt);
		
		return $section;
	} // end func: _parseXMLToSection
	
	
} // end class: PlexSection
