<?php

global $people; $people = array();
global $items; $items = array();
$known_guids = array();
$include_just_actors = ($this->getThemeOption('just-actors', false)) ? true : false;


# Get all items and actors
foreach($library->getSections() as $section) {
	
	if($section->getType() != 'movie' and $section->getType() != 'show') continue; # only do movies and tv
	
	foreach($section->getItems() as $item) {
		if($item->getNumObjectsOfType('role')==0) continue;
		if(array_key_exists($item->getGUID(), $known_guids)) continue;
		$known_guids[$item->getGUID()] = true;
		
		if(!array_key_exists('i_'.$item->getKey(), $items)) {
			$items['i_'.$item->getKey()] = array(
				'title' => $item->getTitle(),
				'num_people' => 0,
				'people' => array()
			);
		}
		
		$item_people = array();
		
		if($section->getType() == 'movie') {
			
			if($item->getNumObjectsOfType('role')>0) $item_people = $item->getObjectsOfType('role');
			if(!$include_just_actors) {
				if($item->getNumObjectsOfType('director')>0) $item_people = $item_people + $item->getObjectsOfType('director');
				if($item->getNumObjectsOfType('writer')>0) $item_people = $item_people + $item->getObjectsOfType('writer');
			}
			
		} elseif($section->getType() == 'show') {
			
			foreach($item->getSeasons() as $season) {
				foreach($season->getEpisodes() as $episode) {
					if($episode->getNumObjectsOfType('role')>0) $item_people = $item_people + $episode->getObjectsOfType('role');
					if(!$include_just_actors) {
						if($episode->getNumObjectsOfType('director')>0) $item_people = $item_people + $episode->getObjectsOfType('director');
						if($episode->getNumObjectsOfType('writer')>0) $item_people = $item_people + $episode->getObjectsOfType('writer');
					}
				}
			}
			
		}
		
		if(count($item_people)>0) {
			foreach($item_people as $person_id=>$person_title) {
				$items['i_'.$item->getKey()]['num_people']++;
				$items['i_'.$item->getKey()]['people']['p_'.$person_id] = 'p_'.$person_id;
				if(!array_key_exists('p_'.$person_id, $people)) {
					$people['p_'.$person_id] = array(
						'title' => $person_title,
						'num_items' => 0,
						'items' => array()
					);
				}
				$people['p_'.$person_id]['num_items']++;
				$people['p_'.$person_id]['items']['i_'.$item->getKey()] = 'i_'.$item->getKey();
			}
		} # end foreach role
		
	} # end foreach: getItems()
	
} # end foreach: getSections()



# Eliminate any people who only have one item
foreach($people as $person_id=>$person) {
	if(count($person['items'])>1) continue;
	unset($people[$person_id]);
	foreach($person['items'] as $item_id) {
		unset($items[$item_id]['people'][$person_id]);
	}
}



# Eliminate any items with less people than allowed
foreach($items as $item_id=>$item) {
	if(count($item['people'])>=1) continue;
	unset($items[$item_id]);
}



# Output items and people with relationships, sort by most popular
function sort_item_people($a, $b) {
	global $people;
	if($people[$a]['num_items'] == $people[$b]['num_items']) return 0;
	return ($people[$a]['num_items'] > $people[$b]['num_items']) ? -1 : 1;
}
function sort_people_items($a, $b) {
	global $items;
	if($items[$a]['num_people'] == $items[$b]['num_people']) return 0;
	return ($items[$a]['num_people'] > $items[$b]['num_people']) ? -1 : 1;
}
function sort_by_title($a, $b) {
	return strcmp($a['title'], $b['title']);
}

uasort($items, 'sort_by_title');
foreach($items as $item_id=>$item) {
	uasort($item['people'], 'sort_item_people');
	echo "\ni\t{$item_id}\t{$item['title']}\t".implode("\t", $item['people']);
}

uasort($people, 'sort_by_title');
foreach($people as $person_id=>$person) {
	uasort($person['items'], 'sort_people_items');
	echo "\np\t{$person_id}\t{$person['title']}\t".implode("\t", $person['items']);
}


