var PLEX = {

	sections: {},
	total_items: 0,
	current_section: false,
	current_item: false,
	previous_item_id: 0,
	next_item_id: 0,
	current_sort_key: "title",
	current_sort_order: "asc",
	current_genre: "all",
	show_all_genres: false,
	data_loaded: false,
	filter_timeout: false,
	filter_delay: 350,
	popup_visible: false,
	lazyload_threshold: 200,


	load_data: function(aData) {
		if(!aData || !aData.status || aData.status!='success' || aData.num_sections==0) return false;
		$.each(aData.sections, function(section_key, section_data){
			PLEX.sections[section_key] = section_data;
		});
		PLEX.total_items = aData.total_items;
		PLEX.section_display_order = aData.section_display_order;
		PLEX.data_loaded = true;
	}, // end func: load_data


	display_sections_list: function() {
		$.each(PLEX.section_display_order, function(i,key){
			var section = PLEX.sections[key];
			PLEX._sections_list.append('<li data-section="'+section.key+'" class="'+section.type+'"><em>'+number_format(section.num_items)+'</em><span>'+section.title+'</span></li>');
		});
	}, // end func: display_sections_list


	display_section: function(section_id) {
		var section_id = parseInt(section_id);

		if(section_id != PLEX.current_section.key) {
			PLEX.current_sort_key = "title";
			PLEX.current_sort_order = "asc";
			PLEX.current_genre = "all";
			PLEX.show_all_genres = false;
			$("li", PLEX._sorts_list).removeClass("current");
			$("li em", PLEX._sorts_list).remove();
			$("li[data-sort="+PLEX.current_sort_key+"]").addClass("current").append("<em>"+PLEX.current_sort_order+"</em>");
		}

		PLEX.current_section = PLEX.sections[section_id];
		window.location.hash = PLEX.current_section.key;

		$("li", PLEX._sections_list).removeClass("current");
		$("li[data-section="+section_id+"]").addClass("current");
		PLEX._section_title.text(PLEX.current_section.title);

		PLEX.display_items();
		PLEX.display_genre_list(PLEX.current_section.genres);

	}, // end func: display_section


	display_genre_list: function(genres) {

		if(genres.length > 0) {

			var num_to_show_before_hiding = 5;
			var count = num_hidden = 0;
			var list_html = '<li data-genre="all"><em>'+PLEX.current_section.num_items+'</em>All</li>';

			$.each(genres, function(i, genre){
				count++;
				if(count <= num_to_show_before_hiding) {
					list_html += '<li data-genre="'+genre.genre+'" class="genre_shown"><em>'+genre.count+'</em>'+genre.genre+'</li>';
				} else {
					num_hidden++;
					list_html += '<li data-genre="'+genre.genre+'" class="genre_hidden"><em>'+genre.count+'</em>'+genre.genre+'</li>';
				}
			});

			if(num_hidden>0) {
				list_html += '<li id="genre_show_all">Show '+num_hidden+' more...</li>';
				list_html += '<li id="genre_hide_all">Show fewer...</li>';
			}

			PLEX._genre_list.html(list_html);

			if(PLEX.show_all_genres) {
				$("#genre_show_all").hide();
				$(".genre_hidden").show();
			} else {
				$("#genre_hide_all").hide();
				$(".genre_hidden").hide();
			}

			$("li", PLEX._genre_list).removeClass("current");
			$("li[data-genre="+PLEX.current_genre+"]").addClass("current");

			PLEX._genre_list_section.show();
		} else {
			PLEX._genre_list_section.hide();
		}

	}, // end func: display_genre_list


	display_items: function(items) {

		var items = PLEX.current_section.items

		if(PLEX._section_filter.val()!="") {
			items = PLEX.filter_items_by_term(items, PLEX._section_filter.val());
		}

		if(PLEX.current_genre != "all") {
			items = PLEX.filter_items_by_genre(items, PLEX.current_genre);
		}

		PLEX._item_list.html("");
		var num_items = 0;

		$.each(PLEX.current_section.sorts[PLEX.current_sort_key+"_"+PLEX.current_sort_order], function(i, key){
			if(typeof items[key] == "undefined") return;
			var item = items[key];
			var thumb = (item.thumb==false)?"assets/images/default.png":item.thumb;
			PLEX._item_list.append('<li data-item="'+item.key+'" class="item"><img src="assets/images/default.png" data-src="'+thumb+'" width="150" /><h4>'+item.title+'</h4></li>');
			num_items++;
		});

		if(num_items==0) {
			PLEX._section_meta.text("No items in this collection");
			PLEX._item_list_status.html("<p>There are no items to display in this collection.</p>").show();
		} else {
			PLEX._item_list_status.hide();
			PLEX._section_meta.text(number_format(num_items)+" "+inflect(num_items,"item")+" in this collection");
		}

		$(document).trigger("scroll");
	}, // end func: display_items


	filter_items_by_term: function(all_items, term) {
		var term = term.toLowerCase();
		if(term=="") {
			return all_items;
		}
		var items_to_show = {};
		$.each(all_items, function(key, item){
			var title = item.title.toLowerCase();
			if(title.indexOf(term) === -1) return;
			items_to_show[key] = item;
		});
		return items_to_show;
	}, // end func: filter_items_by_term


	filter_items_by_genre: function(all_items, genre) {
		if(genre == "all") return all_items;
		var items_to_show = {};
		$.each(all_items, function(key, item){
			if($.inArray(genre, item.genre) === -1) return;
			items_to_show[key] = item;
		});
		return items_to_show;
	}, // end func: filter_items_by_genre


	change_sort: function(arg_new_sort_key) {

		var new_sort_key = "title";
		switch(arg_new_sort_key) {
			case "release": new_sort_key = "release"; break;
			case "rating": new_sort_key = "rating"; break;
		}

		if(new_sort_key == PLEX.current_sort_key) {
			PLEX.current_sort_order = (PLEX.current_sort_order=="desc")?"asc":"desc";
		} else {
			PLEX.current_sort_key = new_sort_key;
		}

		$("li", PLEX._sorts_list).removeClass("current");
		$("li em", PLEX._sorts_list).remove();
		$("li[data-sort="+PLEX.current_sort_key+"]", PLEX._sorts_list).addClass("current").append("<em>"+PLEX.current_sort_order+"</em>");

		PLEX.display_section(PLEX.current_section.key);

	}, // end func: change_sort


	change_genre: function(genre) {
		if(typeof genre == "undefined" || genre == PLEX.current_genre) return;
		PLEX.current_genre = genre;
		PLEX.display_section(PLEX.current_section.key);
	}, // end func: change_genre


	display_item: function(item_id) {
		var item_id = parseInt(item_id);
		PLEX.current_item = PLEX.current_section.items[item_id];
		window.location.hash = PLEX.current_section.key+"/"+PLEX.current_item.key;
		var popup_html = PLEX.generate_item_content();
		PLEX._popup_overlay.fadeIn().height($(document).height());
		PLEX._popup_container
			.html(popup_html)
			.css({
				top: $(window).scrollTop() + ($(window).height()-PLEX._popup_container.height())/2,
				left: ($(window).width()-PLEX._popup_container.width())/2
			})
			.fadeIn();
	}, // end func: display_item


	generate_item_content: function() {

		var popup_header = '<div id="popup-header"><p class="right"><span class="popup-close">Close</span></p><p>Library &raquo; '+PLEX.current_section.title+' &raquo; '+PLEX.current_item.title+'</p></div>';

		var _current_item = $("li[data-item="+PLEX.current_item.key+"]", PLEX._item_list);
		var previous_item_id = parseInt(_current_item.prev().attr("data-item"));
		var next_item_id = parseInt(_current_item.next().attr("data-item"));


		PLEX.previous_item_id = (previous_item_id>0)?previous_item_id:0;
		PLEX.next_item_id = (next_item_id>0)?next_item_id:0;


		var popup_footer = '<div id="popup-footer">';
		if(next_item_id>0) popup_footer += '<span class="right" data-item="'+PLEX.current_section.items[next_item_id].key+'">'+PLEX.current_section.items[next_item_id].title+' &raquo;</span>';
		if(previous_item_id>0) popup_footer += '<span data-item="'+PLEX.current_section.items[previous_item_id].key+'">&laquo; '+PLEX.current_section.items[previous_item_id].title+'</span></div>';
		popup_footer += '<div class="clear"></div></div>';

		var _img = $("img", _current_item);
		var img_height = "";
		if(_img.attr("data-src")!=undefined) {
			_img.attr("src", _img.attr("data-src")).removeAttr("data-src");
		} else {
			img_height = _img.height();
		}
		var img_thumb = _img.attr("src");

		var popup_sidebar_meta = '<ul>';
		if(PLEX.current_item.duration > 0) {
			var minutes = Math.round(PLEX.current_item.duration/60000);
			popup_sidebar_meta += '<li>Duration: '+minutes+' '+inflect(minutes,'minute')+'</li>';
		}
		if(PLEX.current_item.studio != false) popup_sidebar_meta += '<li>Studio: '+PLEX.current_item.studio+'</li>';
		if(PLEX.current_item.release_year != false) popup_sidebar_meta += '<li>Released: '+PLEX.current_item.release_year+'</li>';
		if(PLEX.current_item.content_rating != false) popup_sidebar_meta += '<li>Rated: '+PLEX.current_item.content_rating+'</li>';
		if(PLEX.current_item.num_seasons > 0) popup_sidebar_meta += '<li>Seasons: '+PLEX.current_item.num_seasons+'</li>';
		if(PLEX.current_item.num_episodes > 0) popup_sidebar_meta += '<li>Episodes: '+PLEX.current_item.num_episodes+'</li>';
		if(PLEX.current_item.view_count > 0) popup_sidebar_meta += '<li>Watched: '+PLEX.current_item.view_count+' '+inflect(PLEX.current_item.view_count,'time')+'</li>';

		popup_sidebar_meta += '</ul>';
		var popup_sidebar = '<div id="popup-sidebar"><img src="'+img_thumb+'" width="150" height="'+img_height+'" />'+popup_sidebar_meta+'</div>';


		var rating_tag = '';
		if(PLEX.current_item.user_rating != false) {
			var rating = PLEX.current_item.user_rating;
			var rating_source = 'user';
		} else if(PLEX.current_item.rating != false) {
			var rating = PLEX.current_item.rating;
			var rating_source = 'plex';
		}
		if(rating) {
			var rating_class = "rating_"+Math.round(rating)/2*10;
			rating_tag = '<span class="rating rating_'+rating_source+' '+rating_class+'"></span>';
		}

		var popup_content = '<div id="popup-content">'+rating_tag+'<h3>'+PLEX.current_item.title+'</h3>';

		if(PLEX.current_item.tagline != false) popup_content += '<h4>'+PLEX.current_item.tagline+'</h4>';
		if(PLEX.current_item.summary != false) popup_content += '<div id="popup-summary"><p>'+PLEX.current_item.summary+'</p></div>';

		if(
			PLEX.current_item.director && PLEX.current_item.director.length > 0 ||
			typeof PLEX.current_item.genre !="undefined" && PLEX.current_item.genre.length > 0 ||
			PLEX.current_item.role && PLEX.current_item.role.length > 0 ||
			PLEX.current_item.media
		) {
			popup_content += '<ul id="popup-content-meta">';
			if(PLEX.current_item.director) popup_content += '<li><strong>Directed by:</strong> '+PLEX.current_item.director.join(", ")+'</li>';
			if(PLEX.current_item.role) popup_content += '<li><strong>Starring:</strong> '+PLEX.current_item.role.join(", ")+'</li>';
			if(PLEX.current_item.genre) popup_content += '<li><strong>Genre:</strong> '+PLEX.current_item.genre.join(", ")+'</li>';
			if(PLEX.current_item.media) {
				var media = PLEX.current_item.media;
				popup_content += '<li><strong>Video:</strong> codec: '+media.video_codec+', framerate: '+media.video_framerate+ ((media.video_resolution != undefined && media.video_resolution>0)?', vert: '+media.video_resolution:'') + ((media.aspect_ratio != undefined && media.aspect_ratio>0)?', aspect ratio: '+media.aspect_ratio:'') +'</li>';
				popup_content += '<li><strong>Audio:</strong> codec: '+media.audio_codec+', channels: '+media.audio_channels+'</li>';
				if(media.total_size != false) popup_content += '<li><strong>File:</strong> '+hl_bytes_to_human(media.total_size)+' @ '+media.bitrate+'bps</li>';
			}
			popup_content += '</ul>';
		}

		popup_content += '</div>';

		return popup_header + '<div id="popup-outer"><div id="popup-inner">' + popup_sidebar + popup_content + '<div class="clear"></div></div>' + popup_footer + '</div>';

	}, // end func: generate_item_content


	hide_item: function() {
		PLEX.popup_visible = false;
		window.location.hash = PLEX.current_section.key;
		PLEX._popup_overlay.fadeOut();
		PLEX._popup_container.fadeOut();
	}, // end func: hide_item


	lazy_load_images: function() {
		var window_top = $(document).scrollTop() - PLEX.lazyload_threshold;
		var window_bottom = window_top + $(window).height() + PLEX.lazyload_threshold;
		$("img[data-src]", PLEX._item_list).each(function(){
			var item_top = $(this).position().top;
			if( item_top < window_top || item_top > window_bottom) return;
			$(this).attr("src", $(this).attr("data-src")).removeAttr("data-src");
		});
	}, // end func: lazy_load_images


	run: function() {

		if(!PLEX.data_loaded) {
			$.getJSON("plex-data/data.js", [], function(data){
				PLEX.load_data(data);
				return PLEX.run();
			});
			return;
		}

		PLEX._total_items = $("#total_items");
		PLEX._sections_list = $("#plex_section_list");
		PLEX._sorts_list = $("#plex_sort_list");
		PLEX._genre_list_section = $("#plex_genre_list_section").hide();
		PLEX._genre_list = $("#plex_genre_list");
		PLEX._section_title = $("#section-header h2");
		PLEX._section_meta = $("#section-header p");
		PLEX._section_filter = $("#section-header input");
		PLEX._item_list_status = $("#item-list-status");
		PLEX._item_list = $("#item-list ul");
		PLEX._popup_overlay = $("#popup-overlay");
		PLEX._popup_container = $("#popup-container");

		PLEX._total_items.text(number_format(PLEX.total_items));
		PLEX.display_sections_list();

		$("li", PLEX._sections_list).click(function(){
			PLEX.display_section($(this).attr("data-section"));
		});

		$("li", PLEX._sorts_list).click(function(){
			PLEX.change_sort($(this).attr('data-sort'));
		});

		$("li", PLEX._genre_list).live("click", function(){
			PLEX.change_genre($(this).attr('data-genre'));
		});

		$("#genre_show_all").live("click", function(){
			PLEX.show_all_genres = true;
			$(".genre_hidden").show();
			$("#genre_show_all").hide();
			$("#genre_hide_all").show();
		});

		$("#genre_hide_all").live("click", function(){
			PLEX.show_all_genres = false;
			$(".genre_hidden").hide();
			$("#genre_hide_all").hide();
			$("#genre_show_all").show();
		});

		PLEX._section_filter.keyup(function(){
			PLEX.display_section(PLEX.current_section.key);
		});

		$(document).bind("scroll", function(){
			PLEX.lazy_load_images();
		}).trigger("scroll");

		$("li", PLEX._item_list).live("click", function(){
			PLEX.display_item($(this).attr("data-item"));
		});

		$("#popup-footer span").live("click", function(){
			PLEX.display_item($(this).attr("data-item"));
		});

		PLEX._popup_overlay.click(function(){
			PLEX.hide_item();
		});

		$(document).keyup(function(event) {
			if(event.shiftKey || event.metaKey || event.altKey || event.ctrlKey) return;
			switch(event.which) {
				case 27: // esc
				case 88: // x
					PLEX.hide_item();
					break;
				case 75: // k
					if(PLEX.previous_item_id>0) {
						PLEX.display_item(PLEX.previous_item_id);
					}
					break;
				case 74: // j
					if(PLEX.next_item_id>0) {
						PLEX.display_item(PLEX.next_item_id);
					} else if(!PLEX.popup_visible) { // Show first item if none others
						var first_item = parseInt($(":first", PLEX._item_list).attr("data-item"));
						if(first_item>0) PLEX.display_item(first_item);
					}
					break;
			}
		});

		$(".popup-close").live("click", function(){
			PLEX.hide_item();
		});

		$("#toggle_sidebar").click(function(){
			$("#sidebar").toggle();
			return false;
		});

		var hash = window.location.hash;
		if(hash!="") {
			var regex = new RegExp("#([0-9]+)/?([0-9]+)?/?");
			var m = regex.exec(hash);
			var m1 = parseInt(m[1]);
			var m2 = parseInt(m[2]);
			if(m1>0) PLEX.display_section(m1);
			if(m2>0) PLEX.display_item(m2);
		} else {
			$("li:first", PLEX._sections_list).click();
		}

	}, // end func: run


}; // end class: PLEX
