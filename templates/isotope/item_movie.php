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
			<?php if($item->getOriginallyAvailableAt()): ?>
				<li>Released: <?php echo $item->getOriginallyAvailableAt('j F Y'); ?></li>
			<?php endif; ?>
			<?php if($item->getAddedAt()): ?>
				<li>Added: <?php echo $item->getAddedAt('j F Y'); ?></li>
			<?php endif; ?>
			<?php if($item->getUpdatedAt()): ?>
				<li>Updated: <?php echo $item->getUpdatedAt('j F Y'); ?></li>
			<?php endif; ?>
			<?php if($item->getLastViewedAt()): ?>
				<li>Last viewed: <?php echo $item->getLastViewedAt('j F Y'); ?></li>
			<?php endif; ?>
			<?php if($item->getViewCount()>0): ?>
				<li>Viewed: <?php printf(_n('%d time', '%d times', $item->getViewCount()), $item->getViewCount()); ?></li>
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
		
		
		<div class="plex-item-objects">
			
			<?php if($item->getNumObjectsOfType('director')>0): ?>
				<h3><?php echo _n('Director', 'Directors', $item->getNumObjectsOfType('director')); ?></h3>
				<ul>
					<?php foreach($item->getObjectsOfType('director') as $obj): ?>
						<li><?php echo $obj; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			
			<?php if($item->getNumObjectsOfType('writer')>0): ?>
				<h3><?php echo _n('Writer', 'Writers', $item->getNumObjectsOfType('writer')); ?></h3>
				<ul>
					<?php foreach($item->getObjectsOfType('writer') as $obj): ?>
						<li><?php echo $obj; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			
			<?php if($item->getNumObjectsOfType('role')>0): ?>
				<h3><?php _e('Cast'); ?></h3>
				<ul>
					<?php foreach($item->getObjectsOfType('role') as $obj): ?>
						<li><?php echo $obj; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			
		</div><!-- .plex-item-objects -->
		
		
		<div class="plex-item-main-content-half">
		
			<?php if($item->getTagline()): ?>
				<div class="plex-item-tagline"><p><?php echo $item->getTagline(); ?></p></div>
			<?php endif; ?>
			
			<?php if($item->getNumObjectsOfType('genre')>0): ?>
				<div class="plex-item-genres">
					<p><?php echo implode(' | ', $item->getObjectsOfType('genre')); ?></p>
				</div>
			<?php endif; ?>
			
			<?php if($item->getSummary()): ?>
				<div class="plex-item-summary"><p><?php echo $item->getSummary(); ?></p></div>
			<?php endif; ?>
			
			<?php if($item->getMedia()): $media = $item->getMedia(); ?>
				<div class="plex-item-media">
					
					<ul>
						<?php if($media->getNumParts()): ?>
							<li><strong><?php echo $media->getNumParts(); ?></strong> <span><?php echo _n('file', 'files', $media->getNumParts()); ?></span></li>
						<?php endif; ?>
						<?php if($media->getFilesize()): $size = convert_bytes_to_higher_order($media->getFilesize()); ?>
							<li><strong><?php echo round($size['value'],2); ?></strong> <span><?php echo $size['order']; ?></span></li>
						<?php endif; ?>
						<?php if($media->getBitrate()): ?>
							<li><strong><?php echo number_format($media->getBitrate()); ?></strong> <span>kbps</span></li>
						<?php endif; ?>
						<?php if($media->getDuration()): ?>
							<li><strong><?php echo round($media->getDuration()/60); ?></strong> <span>mins</span></li>
						<?php endif; ?>
						<?php if($media->getAspectRatio()): ?>
							<li><strong><?php echo $media->getAspectRatio(); ?></strong> <span>aspect</span></li>
						<?php endif; ?>
						<?php if($media->getAudioChannels()): ?>
							<li><strong><?php echo $media->getAudioChannels(); ?></strong> <span>channels</span></li>
						<?php endif; ?>
						<?php if($media->getAudioCodec()): ?>
							<li><strong><?php echo $media->getAudioCodec(); ?></strong> <span>audio</span></li>
						<?php endif; ?>
						<?php if($media->getVideoCodec()): ?>
							<li><strong><?php echo $media->getVideoCodec(); ?></strong> <span>video</span></li>
						<?php endif; ?>
						<?php if($media->getContainer()): ?>
							<li><strong><?php echo $media->getContainer(); ?></strong> <span>container</span></li>
						<?php endif; ?>
						<?php if($media->getVideoFrameRate()): ?>
							<li><strong><?php echo $media->getVideoFrameRate(); ?></strong> <span>framerate</span></li>
						<?php endif; ?>
						<?php if($media->getNumSubs()): ?>
							<li><strong><?php echo $media->getNumSubs(); ?></strong> <span>subs</span></li>
						<?php endif; ?>
					</ul>
					
				</div>
			<?php endif; ?>
		
		</div><!-- .plex-item-main-content -->
		
	
		<pre>
			<?php #print_r($item); ?>
		</pre>
		
	</div><!-- #plex-item-page-content -->
	
	
	
	
	
	
	<div id="plex-footer">
		<p><a href="http://l0ke.com/bl">Plex</a> + <a href="http://l0ke.com/bb">Plex Export</a></p>
	</div><!-- #plex-footer -->

</div><!-- #plex-container -->
</body>
</html>