<?php
/*
 * Plex Export Isotope Theme
 * 
 * Users jQuery Istope to handle displaying items.
 * 
 */


/**
 * Sort by count array element
 **/
function isotope_sort_obj($a, $b) {
	if($a['count'] == $b['count']) return 0;
	return ($a['count'] < $b['count']) ? 1 : -1;
}


?><!DOCTYPE html "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head profile="http://www.w3.org/2005/10/profile">
	<meta charset=utf-8 />
	<title>Plex Export</title>
	<meta name="viewport" content="initial-scale=1,minimum-scale=1,maximum-scale=1" />
	<link rel="icon" type="image/x-icon" href="./assets/images/favicon.ico">

	<link rel="stylesheet" href="assets/css/style.css" type="text/css" />
	<link rel="stylesheet" media="all and (max-device-width: 480px)" href="assets/css/iphone.css">
	<link rel="stylesheet" media="all and (-webkit-min-device-pixel-ratio: 2)" href="assets/css/iphone-retina.css" />
	<link rel="stylesheet" href="assets/css/jquery.fancybox.1.3.4.css" type="text/css" />

	<script type="text/javascript" src="assets/js/jquery.1.5.1.min.js"></script>
	<script type="text/javascript" src="assets/js/jquery.fancybox.1.3.4.min.js"></script>
	<script type="text/javascript" src="assets/js/utils.js"></script>
	<script type="text/javascript" src="assets/js/jquery.isotope.min.js"></script>

	<script type="text/javascript">
	jQuery(document).ready(function($){
		
		// Add case-insensitive filter to jQuery
		$.extend($.expr[':'], {
			'containsi': function(elem, i, match, array) {
				return (elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "")) >= 0;
			}
		});
		
	});
	</script>

</head>

<body>
<div id="plex-container">
	
	<div id="plex-header">
		<h1><a href="index.html" title="Home">Plex Export</a></h1>
		<p>
			<span><?php echo _n('Library Item', 'Library Items', $library->getTotalSubItems()); ?></span>
			<strong><?php echo number_format($library->getTotalSubItems()); ?></strong>
		</p>
	</div>
	
	
	<?php foreach($library->getSections() as $section): ?>
		
		<script type="text/javascript">
		jQuery(document).ready(function($){
			
			
			
			$("#plex-section-<?php echo $section->getKey(); ?>").imagesLoaded(function(){
				
				var $list<?php echo $section->getKey(); ?> = $("#plex-section-<?php echo $section->getKey(); ?> .plex-section-items ul");
				$list<?php echo $section->getKey(); ?>.isotope({
					layoutMode: 'fitRows',
					getSortData: {
						rating: function ($elem) {
							return parseFloat($elem.data("sort-rating"));
						},
						duration: function ($elem) {
							return parseInt($elem.data("sort-duration"));
						},
						viewCount: function ($elem) {
							return parseInt($elem.data("sort-viewCount"));
						},
						filesize: function ($elem) {
							return parseInt($elem.data("sort-filesize"));
						},
						originallyAvailableAt: function ($elem) {
							return parseInt($elem.data("sort-originallyAvailableAt"));
						},
						addedAt: function ($elem) {
							return parseInt($elem.data("sort-addedAt"));
						},
						updatedAt: function ($elem) {
							return parseInt($elem.data("sort-updatedAt"));
						},
						lastViewedAt: function ($elem) {
							return parseInt($elem.data("sort-lastViewedAt"));
						}
					}
				});

				var opts<?php echo $section->getKey(); ?> = {
					sort_asc: true,
					sort_by: "",
					filters: []
				}

				$("#plex-section-<?php echo $section->getKey(); ?> .plex-section-sorts a").live("click", function(e){

					var $this = $(this),
						sortName = $this.data("sort");
					if(sortName=='') return;

					e.preventDefault();

					if(opts<?php echo $section->getKey(); ?>.sort_by == sortName) {
						opts<?php echo $section->getKey(); ?>.sort_asc = (!!opts<?php echo $section->getKey(); ?>.sort_asc) ? false : true;
						$this.data("sort-order", (opts<?php echo $section->getKey(); ?>.sort_asc) ? "asc" : "desc");
					} else {
						opts<?php echo $section->getKey(); ?>.sort_asc = ($this.data("sort-order")=="asc") ? true : false;
						opts<?php echo $section->getKey(); ?>.sort_by = sortName;
						$this.parents("ul").find("a.current").removeClass("current");
						$this.addClass("current");
					}

					$("em", $this).text( ((opts<?php echo $section->getKey(); ?>.sort_asc) ? "asc" : "desc") );

					$list<?php echo $section->getKey(); ?>.isotope({sortBy: opts<?php echo $section->getKey(); ?>.sort_by, sortAscending: opts<?php echo $section->getKey(); ?>.sort_asc});

				});



				$("#plex-section-<?php echo $section->getKey(); ?> .plex-section-filter a").live("click", function(e){
					
					var $this = $(this),
						filterKey = $this.data("filter-key"),
						filterValue = $this.data("filter-value");
					
					if(filterKey=='') return;
					e.preventDefault();
					
					updateFilters<?php echo $section->getKey(); ?>(filterKey, filterValue);
					
					$this.parents("ul").find("a.current").removeClass("current");
					$this.addClass("current");
					
				});
				
				
				
				var plex_section_<?php echo $section->getKey(); ?>_search_timeout = false;
				$("#plex-section-<?php echo $section->getKey(); ?> .plex-section-header input").live("keyup", function(e){
					e.preventDefault();
					var filterKey = 'search',
						filterValue = $(this).val().toLowerCase();
					clearTimeout(plex_section_<?php echo $section->getKey(); ?>_search_timeout);
					plex_section_<?php echo $section->getKey(); ?>_search_timeout = setTimeout(function(){
						updateFilters<?php echo $section->getKey(); ?>(filterKey, filterValue);
					}, 125);
				});
				
				
				
				function updateFilters<?php echo $section->getKey(); ?>(filterKey, filterValue) {
					
					var filters = [],
						numItems = 0,
						_text = $("#plex-section-<?php echo $section->getKey(); ?> .plex-section-header p"),
						_msg = $("#plex-section-<?php echo $section->getKey(); ?> .plex-section-message");

					if(filterValue=='' || filterValue=='*') {
						delete opts<?php echo $section->getKey(); ?>.filters[filterKey]
					} else {
						if(filterKey=='search') {
							opts<?php echo $section->getKey(); ?>.filters[filterKey] = ':containsi('+filterValue+')';
						} else {
							opts<?php echo $section->getKey(); ?>.filters[filterKey] = '.data-filter-key-'+filterKey+'-value-'+filterValue;
						}
					}
					
					for(var key in opts<?php echo $section->getKey(); ?>.filters) filters.push(opts<?php echo $section->getKey(); ?>.filters[key]);
					$list<?php echo $section->getKey(); ?>.isotope({ filter : filters.join('') });
					
					numItems = $("#plex-section-<?php echo $section->getKey(); ?> .isotope-item:not(.isotope-hidden)").size();
					
					if(numItems==0) {
						_text.text("No items in this collection");
						_msg.show();
					} else {
						_text.text(numItems+" items in this collection");
						_msg.hide();
					}
					
				}
				
				
				
				
			}); // end imagesLoaded
			
			
			
		});
		</script>
		
		
		<div class="plex-section" id="plex-section-<?php echo $section->getKey(); ?>">
			
			
			<div class="plex-section-sidebar">
				<h2><?php _e('Library'); ?></h2>
				<ul class="plex-sections-list">
					<?php foreach($library->getSections() as $menu_section): ?>
						<li>
							<a class="<?php echo $menu_section->getType(); ?> <?php if($menu_section->getKey()==$section->getKey()) echo 'current'; ?>" href="#plex-section-<?php echo $menu_section->getKey(); ?>" title="Go to this section">
								<?php echo $menu_section->getTitle(); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				
				<h2>Sort By</h2>
				<ul class="plex-generic-list plex-section-sorts">
					<li><a class="current" href="#sort" data-sort="original-order" data-sort-order="asc">Title<em>asc</em></a></li>
					<li><a href="#sort" data-sort="rating" data-sort-order="desc">Rating<em></em></a></li>
					<li><a href="#sort" data-sort="duration" data-sort-order="desc">Duration<em></em></a></li>
					<li><a href="#sort" data-sort="viewCount" data-sort-order="desc">View Count<em></em></a></li>
					<li><a href="#sort" data-sort="filesize" data-sort-order="desc">Filesize<em></em></a></li>
					<li><a href="#sort" data-sort="originallyAvailableAt" data-sort-order="desc">Release Date<em></em></a></li>
					<li><a href="#sort" data-sort="addedAt" data-sort-order="desc">Recently Added<em></em></a></li>
					<li><a href="#sort" data-sort="updatedAt" data-sort-order="desc">Last Updated<em></em></a></li>
					<li><a href="#sort" data-sort="lastViewedAt" data-sort-order="desc">Recently Viewed<em></em></a></li>
				</ul>
				
				<?php if($section->getNumValuesForSort('rating')>0): $filter_ratings = $section->getValuesForSort('rating'); ?>
					<h2>Rating</h2>
					<ul class="plex-generic-list plex-section-filter">
						<li><a class="current" href="#rating" data-filter-key="rating" data-filter-value="*">Show All</a></li>
						<?php foreach($filter_ratings as $id=>$rating): ?>
							<li><a href="#rating" data-filter-key="rating" data-filter-value="<?php echo $id; ?>"><?php echo $rating['label']; ?> <em><?php echo number_format($rating['count']); ?></em></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				
				<?php if($section->getNumValuesForSort('genre')>0): $filter_genres = $section->getValuesForSort('genre'); ?>
					<h2>Genres</h2>
					<ul class="plex-generic-list plex-section-filter">
						<li><a class="current" href="#genre" data-filter-key="genre" data-filter-value="*">Show All</a></li>
						<?php foreach($filter_genres as $id=>$genre): ?>
							<li><a href="#genre" data-filter-key="genre" data-filter-value="<?php echo $id; ?>"><?php echo $genre['label']; ?> <em><?php echo number_format($genre['count']); ?></em></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				
			</div><!-- .plex-section-sidebar -->
			
			
			<div class="plex-section-content">
				
				<div class="plex-section-header">
					<div>
						<input type="text" value="" />
						<h2><?php echo $section->getTitle(); ?></h2>
						<p><?php printf(_n('%d item in this collection', '%d items in this collection', $section->getNumItems()), $section->getNumItems()); ?></p>
					</div>
				</div><!-- .plex-section-header -->
				
				<div class="plex-section-message">
					<p>No items found</p>
				</div>
				
				<div class="plex-section-items">
					<?php if($section->getNumItems()>0): ?>
						
						<ul>
							<?php foreach($section->getItems() as $item):
								
								$classes = array('plex-item');
								
								if($item->getRating()) {
									$rating = ($item->getUserRating()>0) ? $item->getUserRating() : $item->getRating();
									$classes[] = 'data-filter-key-rating-value-'.floor( ($rating+1) / 2 );
								}
								
								if($item->getNumObjectsOfType('genre')>0) {
									foreach($item->getObjectsOfType('genre') as $id=>$obj) {
										$classes[] = 'data-filter-key-genre-value-'.$id;
									}
								}
								
								?>
								
								<li class="<?php echo implode(' ', $classes); ?>" 
								
									<?php if($item->getTitleSort()): ?>
										data-sort-title="<?php echo $item->getTitleSort(); ?>"
									<?php endif; ?>
									
									<?php if($item->getRating()): ?>
										data-sort-rating="<?php echo ($item->getUserRating()>0) ? $item->getUserRating() : $item->getRating(); ?>"
									<?php endif; ?>
									
									<?php if($item->getDuration()): ?>
										data-sort-duration="<?php echo $item->getDuration(); ?>"
									<?php endif; ?>
									
									<?php if($item->getViewCount()): ?>
										data-sort-viewCount="<?php echo $item->getViewCount(); ?>"
									<?php endif; ?>
									
									<?php if($item->getMedia() and $item->getMedia()->getFilesize()): ?>
										data-sort-filesize="<?php echo $item->getMedia()->getFilesize(); ?>"
									<?php endif; ?>
									
									<?php if($item->getOriginallyAvailableAt()): ?>
										data-sort-originallyAvailableAt="<?php echo $item->getOriginallyAvailableAt(); ?>"
									<?php endif; ?>
									
									<?php if($item->getAddedAt()): ?>
										data-sort-addedAt="<?php echo $item->getAddedAt(); ?>"
									<?php endif; ?>
									
									<?php if($item->getUpdatedAt()): ?>
										data-sort-updatedAt="<?php echo $item->getUpdatedAt(); ?>"
									<?php endif; ?>
									
									<?php if($item->getLastViewedAt()): ?>
										data-sort-lastViewedAt="<?php echo $item->getLastViewedAt(); ?>"
									<?php endif; ?>
									
									>
									
									<a href="items/<?php echo $item->getKey(); ?>.html">
										<img src="<?php echo ($this->file_exists('images/thumb_'.$item->getKey().'.jpeg')) ? 'images/thumb_'.$item->getKey().'.jpeg' : 'assets/images/default.png'; ?>" width="150" />
										<h4><?php echo $item->getTitle(); ?></h4>
									</a>
								</li>
								
							<?php endforeach; ?>
						</ul>
						
						
						
					<?php else: ?>
						<div class="plex-section-message"><p><?php _e('No items were found'); ?></p></div>
					<?php endif; ?>
				</div><!-- .plex-section-items -->
				
			</div><!-- .plex-section-content -->
			
			
		</div><!-- .plex-section -->
	<?php endforeach; ?>
	
	
	
	
	
	
	
	
	<div id="plex-footer">
		<p><a href="http://l0ke.com/bl">Plex</a> + <a href="http://l0ke.com/bb">Plex Export</a></p>
	</div><!-- #plex-footer -->

</div><!-- #plex-container -->
</body>
</html>