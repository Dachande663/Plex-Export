<?php
/**
 * Represents a Plex TV Show
 **/
class PlexItem_Show extends PlexItem {
	
	protected $type = 'show';
	protected $numSubItems = 1;
	protected $numEpisodes = 0;
	protected $numViewedEpisodes = 0;
	protected $numSeasons = 0;
	protected $seasons = array();
	
	public function setNumEpisodes($val) { if(!$val) return $this; $this->numEpisodes = absint($val); return $this; }
	public function getNumEpisodes() { return $this->numEpisodes; }
	
	public function setNumViewedEpisodes($val) { if(!$val) return $this; $this->numViewedEpisodes = absint($val); return $this; }
	public function getNumViewedEpisodes() { return $this->numViewedEpisodes; }
	
	public function addEpisode($episode) {
		$s = $episode->getSeasonNumber();
		if(!isset($this->seasons[$s])) {
			$season = new PlexItem_Season;
			$season->setSeasonNumber($s);
			$this->seasons[$s] = $season;
			$this->numSeasons++;
		}
		$this->seasons[$s]->addEpisode($episode);
	}
	public function getNumSeasons() { return $this->numSeasons; }
	public function getSeasons() { return $this->seasons; }
	public function getSeasonByNumber($season_id) { return (isset($this->seasons[$season_id])) ? $this->seasons[$season_id] : false; }
	
	
	
	
	
	public function _parseXMLToItem($xml_array, $api) {
		extract($xml_array); # xml & xml_tree
		$attr = $xml->attributes();
		$item = new self;
		$item
			->setKey($attr->ratingKey)
			->setSectionKey($this->getKey())
			->setSubItemsCount($attr->leafCount) # number of episodes
			->setGUID($attr->guid)
			->setTitle($attr->title)
			->setTitleSort( isset($attr->titleSort) ? $attr->titleSort : $attr->title )
			->setSummary($attr->summary)
			->setRating($attr->rating)
			->setUserRating($attr->userRating)
			->setStudio($attr->studio)
			->setContentRating($attr->contentRating)
			->setDuration($attr->duration)
			->setOriginallyAvailableAt($attr->originallyAvailableAt)
			->setAddedAt($attr->addedAt)
			->setUpdatedAt($attr->updatedAt)
			->setNumEpisodes($attr->leafCount)
			->setNumViewedEpisodes($attr->viewedLeafCount)
			->addImage('thumb', $api->build_url($attr->thumb))
			->addImage('art', $api->build_url($attr->art))
			->addImage('banner', $api->build_url($attr->banner))
		;
		
		if(isset($xml->Genre)) foreach($xml->Genre as $obj) $item->addObjectOfType('genre', $obj->attributes()->id, $obj->attributes()->tag);
		
		$episode_keys = array();
		$season_from_key = array(); # nasty hack to pack season ID to _parse
		foreach($xml_tree->MetadataItem->MetadataItem as $season) {
			foreach($season->MetadataItem as $episode) {
				$key = absint($episode->attributes()->id);
				if(!$key) continue;
				$episode_keys[$key] = $api->build_url('library/metadata/'.$key);
				$season_from_key[$key] = absint($season->attributes()->index);
			}
		}
		
		$xml_episodes = $api->transport->multi_get_xml($episode_keys); # Get each episode
		
		if($xml_episodes and count($xml_episodes)>0) {
			foreach($xml_episodes as $key=>$epxml) {
				$episode = PlexItem_Episode::_parseXMLToItem(array('xml'=>$epxml->Video, 'season'=>$season_from_key[$key]), $api);
				if(!$episode) continue;
				$item->addEpisode($episode);
			}
		}
		
		return $item;
	} // end func: _parseXMLToItem
	
	
} // end class: PlexItem_Show
