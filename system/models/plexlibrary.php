<?php
/**
 * Represents a Plex Library
 **/
class PlexLibrary {
	
	private $friendlyName = '';
	private $machineIdentifier = '';
	private $plexVersion = '';
	
	private $sections = array();
	private $numSections = 0;
	private $numItems = 0;
	private $totalSubItems = 0;
	
	public function setFriendlyName($val) { $this->friendlyName = (string) $val; return $this; }
	public function setMachineIdentifier($val) { $this->machineIdentifier = (string) $val; return $this; }
	public function setPlexVersion($val) { $this->plexVersion = (string) $val; return $this; }
	
	public function getFriendlyName() { return $this->friendlyName; }
	public function getMachineIdentifier() { return $this->machineIdentifier; }
	public function getPlexVersion() { return $this->plexVersion; }
	
	public function getSections() { return $this->sections; }
	public function getNumSections() { return $this->numSections; }
	public function getNumItems() { return $this->numItems; }
	public function getTotalSubItems() { return $this->totalSubItems; }
	public function getSectionByKey($key) {
		if(!array_key_exists($key, $this->sections)) return false;
		return $this->sections[$key];
	}
	public function addSection($section) {
		if(!array_key_exists($section->getKey(), $this->sections)) {
			$this->numSections++;
			$this->numItems += $section->getNumItems();
			$this->totalSubItems += $section->getTotalSubItems();
		}
		$this->sections[$section->getKey()] = $section;
	}
	
} // end class: PlexLibrary