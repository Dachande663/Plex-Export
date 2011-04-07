jQuery(document).ready(function($){
	
	
	var _data = false,
		_canvas = $("#plex-canvas"),
		_search_from = $("#plex-search-from"),
		_search_to = $("#plex-search-to");
	
	
	loadLibraryData(function(data){
		
		_data = data;
		
		//for (var node_id in _data.nodes) break; // get first node key
		//drawFromNodeGraphFromSingleNode(node_id);
		
		var current_elements = []; // because @drewwilson can't do this himself, we have to track all items
		
		$("#plex-search-from").autoSuggest(data.arr, {
			selectedItemProp: "title",
			searchObjProps: "title",
			selectedValuesProp: "id",
			startText: "Enter movie or actor names to begin...",
			emptyText: "No matches found"
		});
		
		$("#plex-search-button").click(function(){
			drawGraphFromInputs();
		});
		
	}); // end loadLibraryData
	
	
	
	
	
	
	
	function drawGraphFromInputs(ids) {
		
		var ids = $(".as-values").val().split(",").filter(function(el){if(el=="") return false; return true;});
		
		if(ids.length==0) {
			alert("No IDs provided");
			return;
		}
		
		if(ids.length==1) {
			drawFromNodeGraphFromSingleNode(ids[0]);
			return;
		}
		
		alert("This will show routes between objects, but not yet");
		
	} // end func: drawGraphFromInputs
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	function drawFromNodeGraphFromSingleNode(node_id) {
		
		var all_nodes = _data.nodes,
			nodes = [],
			edges = [];
		
		if(!(node_id in all_nodes)) {
			alert("ID not found");
			return;
		}
		
		var node = all_nodes[node_id];
		nodes.push(node);
		
		
		$.each(node.rels, function(i,rel_id){
			nodes.push(all_nodes[rel_id]);
			edges.push({from:node_id, to:rel_id});
			var secondary_nodes = all_nodes[rel_id].rels.slice(0, 6); // only show the top x relations
			$.each(secondary_nodes, function(i, node_id2){
				if(node_id2 == node_id) return;
				nodes.push(all_nodes[node_id2]);
				edges.push({from:rel_id, to:node_id2});
			});
			
			
			
		});
		
		drawNodeGraph(nodes, edges);
		
	} // end func: drawFromNodeGraphFromSingleNode
	
	
	
	
	
	
	
	
	
	
	
	function drawNodeGraph(nodes, edges) {
		
		_canvas.html('');
		
		var g = new Graph(),
			renderer,
			layouter,
			width = _canvas.width(),
			height = _canvas.height(),
			render_node = function(paper, node) {
				var width = 12 + 5 * node.label.length,
					border_color = (node.type=='item') ? '#66B0FF' : '#FF9729',
					bg_color = (node.type=='item') ? '#99CAFF' : '#FFB05C',
					bg = paper.rect(node.point[0]-width/2, node.point[1]-3, width, 20).attr({"fill":bg_color, "r":"9px", "stroke-width":"1px", "stroke":border_color}),
					txt = paper.text(node.point[0], node.point[1]+6, node.label),
					link = paper.rect().attr(bg.getBBox()).attr({
						fill: "#000",
						opacity: 0
					});
				link.dblclick(function(){
					drawFromNodeGraphFromSingleNode(this.id);
				});
				link.id = node.id;
				return paper.set().push(bg, txt, link);
			};
		
		$.each(nodes, function(i,node){
			g.addNode(node.id, {label:node.title, type:node.type, render:render_node});
		});
		
		$.each(edges, function(i,edge){
			g.addEdge(edge.from, edge.to,  {stroke:"#999"});
		});
		
		var layouter = new Graph.Layout.Spring(g);
		renderer = new Graph.Renderer.Raphael(_canvas.attr("id"), g, width, height);
		
	} // end func: drawNodeGraph
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	function loadLibraryData(callback) {
		
		$.get("data.csv", function(csvstr, status){
			
			if(status != "success") {
				alert("Could not open data file");
				return;
			}
			
			var rows = csvstr.split("\n"),
				nodes = {},
				arr = [],
				count = {item:0, person:0};
				rows.shift();
			
			for(var i = 0, len = rows.length, row, node; i < len; i++) {
				row = rows[i].split("\t");
				
				node = {
					id: row[1],
					type: (row[0]=='i') ? 'item' : 'person',
					title: row[2],
					rels: row.slice(3)
				}
				
				count[node.type]++;
				nodes[row[1]] = node;
				arr.push(node);
				
			}
			
			callback.apply(this, [{
				num_people: count.person,
				num_items: count.item,
				nodes: nodes,
				arr: arr
			}]);
			
		});
		
		
	} // end func: loadLibraryData
	
	
});