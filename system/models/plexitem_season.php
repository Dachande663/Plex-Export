<?php
/**
 * Represents a Plex TV Show Season
 **/
class PlexItem_Season extends PlexItem {
	
	protected $type = 'season';
	protected $seasonNumber = null;
	protected $title = 'Season';
	protected $numEpisodes = 0;
	protected $episodes = array();
	
	public function setSeasonNumber($val) {
		if(!$val) return $this;
		$this->seasonNumber = absint($val);
		$this->setTitle('Season '.$this->seasonNumber);
		return $this;
	}
	public function getSeasonNumber() { return $this->seasonNumber; }
	
	public function addEpisode($episode) {
		$e = $episode->getEpisodeNumber();
		if(!isset($this->episodes[$e])) $this->numEpisodes++;
		$this->episodes[$e] = $episode;
	}
	public function getNumEpisodes() { return $this->numEpisodes; }
	public function getEpisodes() { return $this->episodes; }
	public function getEpisodeByNumber($episode_id) { return (isset($this->episodes[$episode_id])) ? $this->episode[$episode_id] : false; }
	
	public function _parseXMLToItem($xml_array, $api) {}
	
} // end class: PlexItem_Season
