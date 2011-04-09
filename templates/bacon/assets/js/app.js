jQuery(document).ready(function($){
	
	
	var _data = false,
		_canvas = $("#plex-canvas"),
		_search_from = $("#plex-search-from"),
		_search_to = $("#plex-search-to");
	
	
	loadLibraryData(function(data){
		
		_data = data;
		
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
		
		drawNodeGraphFromMultipleNodes(ids, true);
		
	} // end func: drawGraphFromInputs
	
	
	
	
	
	
	
	function drawNodeGraphFromMultipleNodes(node_ids_raw, single_path) {
		
		var single_path = (!!single_path) ? true : false;
		
		var all_nodes = _data.nodes,
			node_ids = [],
			num_node_ids = 0,
			nodes = [],
			edges = [],
			scan_count = 0;
		
		$.each(node_ids_raw, function(i,node_id){
			if(!(node_id in all_nodes)) return;
			node_ids.push(node_id);
			num_node_ids++;
		});
		
		if(num_node_ids==0) {
			alert("No valid IDs given");
			return;
		}
		
		// two options for linking more than 2 nodes
			// 1. chain e.g. #1 -> #2 -> #3
			// 2. cycle e.g. #1 -> #2 -> #3 + #1 -> #3
		
		if(single_path) {
			
			for(var i=1,previous_node,current_node,path,path2; i<num_node_ids; i++) {
				previous_node = node_ids[i-1];
				current_node = node_ids[i];
				path = getNodePathBetweenTwoNodes(previous_node, current_node);
				if(path.status != "success") continue;
				nodes = nodes.concat(path.nodes);
				edges = edges.concat(path.edges);
				scan_count += path.scans;
			}
			
		} else {
			
			var remaining_node_ids = node_ids;
			for(var i=0; i<num_node_ids; i++) {
				var node_id = remaining_node_ids.shift();
				for(var j=0,path; j<remaining_node_ids.length; j++) {
					path = getNodePathBetweenTwoNodes(node_id, remaining_node_ids[j]);
					if(path.status != "success") continue;
					nodes = nodes.concat(path.nodes);
					edges = edges.concat(path.edges);
					scan_count += path.scans;
				}
			}
			
		} // end if else: single_path
		
		//console.log(scan_count);
		
		var existing_edges = {},
			actual_edges = [];
		
		$.each(edges, function(i,edge){
			if(edge.from+edge.to in existing_edges || edge.to+edge.from in existing_edges) return;
			existing_edges[edge.from+edge.to] = true;
			actual_edges.push(edge);
		});
		
		drawNodeGraph(nodes, actual_edges);
		
	} // end func: drawNodeGraphFromMultipleNodes
	
	
	
	
	
	
	
	
	
	
	
	
	function getNodePathBetweenTwoNodes(source_node_id, target_node_id) {
		
		if(source_node_id == target_node_id) {
			return {status:"success", nodes:[_data.nodes[source_node_id]], edges:[], scans:1}; // self-reference
		}
		
		var all_nodes = _data.nodes,
			nodes = [],
			edges = [],
			nodes_to_search = all_nodes[source_node_id].rels,
			num_nodes_to_search = nodes_to_search.length,
			all_parents = {},
			scan_count = 0,
			scans_remaining = 100000;
		
		$.each(all_nodes[source_node_id].rels, function(i,node_id){
			all_parents[node_id] = source_node_id;
		});
		
		for(var i=0,current_node_id; i<num_nodes_to_search && scans_remaining>0; i++) {
			
			current_node_id = nodes_to_search[i];
			scan_count++;
			scans_remaining--;
			
			if(current_node_id == target_node_id) {
				var parent_node_id = current_node_id;
				while(parent_node_id != source_node_id) {
					nodes.push(all_nodes[parent_node_id]);
					edges.push({from:parent_node_id, to:all_parents[parent_node_id]});
					parent_node_id = all_parents[parent_node_id];
				}
				nodes.push(all_nodes[parent_node_id]);
				return {status:"success", nodes:nodes, edges:edges, scans:scan_count};
				break;
			}
			
			nodes_to_search = nodes_to_search.concat(all_nodes[current_node_id].rels);
			num_nodes_to_search += all_nodes[current_node_id].rels.length;
			
			$.each(all_nodes[current_node_id].rels, function(i,node_id){
				if(node_id in all_parents) return;
				all_parents[node_id] = current_node_id;
			});
			
		}
		
		return {status:"error", msg:"exhausted scan limit"};
		
	} // end func: getNodePathBetweenTwoNodes
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
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