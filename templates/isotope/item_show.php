<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset=utf-8 />
	<title>Plex Export</title>
	<meta name="viewport" content="initial-scale=1,minimum-scale=1,maximum-scale=1" />
	<link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">

	<link rel="stylesheet" href="../assets/css/style.css" type="text/css" />
	<link rel="stylesheet" media="all and (max-device-width:480px)" href="../assets/css/iphone.css" />
	<link rel="stylesheet" media="all and (-webkit-min-device-pixel-ratio:2)" href="../assets/css/iphone-retina.css" />
	<link rel="stylesheet" href="../assets/css/jquery.fancybox.1.3.4.css" type="text/css" />

</head>

<body>
<div id="plex-container">
	
	<div id="plex-header">
		<h1><a href="../index.html" title="Home">Plex Export</a></h1>
		<p>
			<span><?php echo _n('Library Item', 'Library Items', $library->getTotalSubItems()); ?></span>
			<strong><?php echo number_format($library->getTotalSubItems()); ?></strong>
		</p>
	</div>
	
	
	<div id="plex-item-page-sidebar">
		<h2><a href="../index.html#plex-section-<?php echo $section->getKey(); ?>">&laquo; <?php echo $section->getTitle(); ?></a></h2>
		<p><img src="../images/thumb_<?php echo $item->getKey(); ?>.jpeg" /></p>
		<ul>
			<?php if($item->getStudio()): ?>
				<li>Studio: <?php echo $item->getStudio(); ?></li>
			<?php endif; ?>
			<?php if($item->getNumSeasons()): ?>
				<li>Seasons: <?php echo $item->getNumSeasons(); ?></li>
			<?php endif; ?>
			<?php if($item->getNumEpisodes()): ?>
				<li>Episodes: <?php echo $item->getNumEpisodes(); ?></li>
			<?php endif; ?>
			<?php if($item->getNumViewedEpisodes()>0): ?>
				<li>Watched: <?php echo $item->getNumViewedEpisodes(); ?></li>
			<?php endif; ?>
			<?php if($item->getAddedAt()): ?>
				<li>Added: <?php echo $item->getAddedAt('j F Y'); ?></li>
			<?php endif; ?>
			<?php if($item->getUpdatedAt()): ?>
				<li>Updated: <?php echo $item->getUpdatedAt('j F Y'); ?></li>
			<?php endif; ?>
		</ul>
	</div>
	
	
	<div id="plex-item-page-content">
		
		<div class="plex-item-header">
			<div>
				<?php if($this->getThemeOption('watch-online')): ?>
					<span class="plex-watch-online-link">Watch Online</span>
				<?php endif; ?>
				<h2><?php echo $item->getTitle(); ?></h2>
				
				<?php
				$strap_items = array(); # put in an array so we can always ensure nice separators
				if($item->getContentRating()) $strap_items[] = $item->getContentRating();
				if($item->getDuration()) $strap_items[] = round($item->getDuration()/60).' mins';
				if($item->getOriginallyAvailableAt()) $strap_items[] = $item->getOriginallyAvailableAt('j F Y');
				if($item->getUserRating()) $strap_items[] = $item->getUserRating().'/10';
				elseif($item->getRating()) $strap_items[] = $item->getRating().'/10';
				
				if(count($strap_items)>0): ?>
					<p><?php echo implode(' | ', $strap_items); ?></p>
				<?php endif; ?>
			</div>
		</div><!-- .plex-item-header -->
		
		
		<div class="plex-item-main-content-full">
		
			<?php if($item->getNumObjectsOfType('genre')>0): ?>
				<div class="plex-item-genres">
					<p><?php echo implode(' | ', $item->getObjectsOfType('genre')); ?></p>
				</div>
			<?php endif; ?>
			
			<?php if($item->getSummary()): ?>
				<div class="plex-item-summary"><p><?php echo $item->getSummary(); ?></p></div>
			<?php endif; ?>
			
			
		</div><!-- .plex-item-main-content -->
		
		
		<?php if($item->getNumSeasons()>0): ?>
			<div class="plex-item-episode-browser">
				<h3>Episode Browser</h3>
				<ul>
					<?php foreach($item->getSeasons() as $season): ?>
						<li>
							<h4><?php echo $season->getTitle(); ?></h4>
							<?php if($season->getNumEpisodes()>0): ?>
								<ul>
									<?php foreach($season->getEpisodes() as $episode): ?>
										<li><?php echo $episode->getTitle(); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php else: ?>
								<p>No episodes in this season.</p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		
	</div><!-- #plex-item-page-content -->
	
	
	
	<pre><?php #print_r($item); ?></pre>
	
	
	
	
	
	<div id="plex-footer">
		<p><a href="http://l0ke.com/bl">Plex</a> + <a href="http://l0ke.com/bb">Plex Export</a></p>
	</div><!-- #plex-footer -->

</div><!-- #plex-container -->
</body>
</html>