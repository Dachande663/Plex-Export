<?php
/**
 * Represents a Plex Media object (one or more files for an item)
 **/
class PlexMedia {
	
	protected $id;
	protected $duration = null;
	protected $bitrate = null;
	protected $aspectRatio = null;
	protected $audioChannels = null;
	protected $audioCodec = null;
	protected $videoCodec = null;
	protected $videoResolution = null;
	protected $container = null;
	protected $videoFrameRate = null;
	protected $numParts = 1;
	protected $filesize = null;
	protected $numSubs = 0;
	
	
	public function setID($val) { $this->id = absint($val); return $this; }
	public function getID() { return $this->id; }
	
	public function setDuration($val) { $this->duration = round($val/1000); return $this; }
	public function getDuration() { return $this->duration; }
	
	public function setBitrate($val) { if(!$val) return $this; $this->bitrate = absint($val); return $this; }
	public function getBitrate() { return $this->bitrate; }
	
	public function setAspectRatio($val) { if(!$val) return $this; $this->aspectRatio = floatval($val); return $this; }
	public function getAspectRatio() { return $this->aspectRatio; }
	
	public function setAudioChannels($val) { if(!$val) return $this; $this->audioChannels = absint($val); return $this; }
	public function getAudioChannels() { return $this->audioChannels; }
	
	public function setAudioCodec($val) { if(!$val) return $this; $this->audioCodec = trim($val); return $this; }
	public function getAudioCodec() { return $this->audioCodec; }
	
	public function setVideoCodec($val) { if(!$val) return $this; $this->videoCodec = trim($val); return $this; }
	public function getVideoCodec() { return $this->videoCodec; }
	
	public function setVideoResolution($val) { if(!$val) return $this; $this->videoResolution = trim($val); return $this; }
	public function getVideoResolution() { return $this->videoResolution; }
	
	public function setContainer($val) { if(!$val) return $this; $this->container = trim($val); return $this; }
	public function getContainer() { return $this->container; }
	
	public function setVideoFrameRate($val) { if(!$val) return $this; $this->videoFrameRate = trim($val); return $this; }
	public function getVideoFrameRate() { return $this->videoFrameRate; }
	
	public function setNumParts($val) { if($val>0) $this->numParts = absint($val); return $this; }
	public function getNumParts() { return $this->numParts; }
	
	public function setFilesize($val) { if($val>0) $this->filesize = (float) $val; return $this; }
	public function getFilesize() { return $this->filesize; }
	
	public function setNumSubs($val) { if($val>0) $this->numSubs = absint($val); return $this; }
	public function getNumSubs() { return $this->numSubs; }
	
	
	/**
	 * Parse an XML Media object into a PlexMedia object
	 *
	 **/
	public function _parseXMLToMedia($xml) {
		$attr = $xml->attributes();
		$media = new self;
		
		$media
			->setID($attr->id)
			->setDuration($attr->duration)
			->setBitrate($attr->bitrate)
			->setAspectRatio($attr->aspectRatio)
			->setAudioChannels($attr->audioChannels)
			->setAudioCodec($attr->audioCodec)
			->setVideoCodec($attr->videoCodec)
			->setVideoResolution($attr->videoResolution)
			->setContainer($attr->container)
			->setVideoFrameRate($attr->videoFrameRate)
		;
		
		$running_filesize = 0.0;
		$running_subs = 0;
		$running_parts = 0;
		if(isset($xml->Part)) {
			foreach($xml->Part as $part) {
				$running_parts++;
				$filesize = floatval($part->attributes()->size); # floatval, not int (max size diff)
				if($filesize>0) $running_filesize += $filesize;
				if(isset($part->Stream)) {
					# @thanks Mickey
					foreach($part->Stream as $stream) {
						# @todo codec may be open
						if($stream->attributes()->streamType==3 and $stream->attributes()->codec=='srt') $running_subs++;
					}
				}
			}
		}
		
		if($running_parts>0) $media->setNumParts($running_parts);
		if($running_filesize>0) $media->setFilesize($running_filesize);
		if($running_subs>0) $media->setNumSubs($running_subs);
		
		return $media;
	} // end func: _parseXMLToMedia
	
	
} // end class: PlexMedia