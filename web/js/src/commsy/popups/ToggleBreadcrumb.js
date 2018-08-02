define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"commsy/request",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/dnd/Source"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, request, On, lang, Source) {
	return declare(TogglePopupHandler, {
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "breadcrumb";
			
			this.features = [];
			
			// register click for node
			this.registerPopupClick();
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_user_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_user_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// register click for edit button
			var aEditNode = Query("a#edit_roomlist", this.contentNode)[0];
			if (aEditNode) {
				On.once(aEditNode, "click", lang.hitch(this, function(event) {
					this.setupEditMode();
				}));
			}
			
			// register click for room links
			dojo.forEach(Query("div.room_change_item", this.contentNode), lang.hitch(this, function(node, index, arr) {
				// get href
				var href = this.getAttrAsObject(node, "data-custom").href;
				
				On(node, "click", function(event) {
					location.href = href;
				});
			}));
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			// setup data to send via ajax
			var search = {
				tabs: [
				    { id: part }
				],
				nodeLists: [
				]
			};
			
			this.submit(search, { part: part });
		},
		
		setupEditMode: function() {
			var contentObjects = Query(	"div#profile_content_row_three, div#profile_content_row_four", this.contentNode);
			
			// make hidden rooms visible
			DomClass.remove(contentObjects[1], "hidden");
			
			// process each room block
			dojo.forEach(Query("div.room_block", this.contentNode), lang.hitch(this, function(blockNode, index, arr) {
				var roomAreaObjects = Query("div.breadcrumb_room_area", blockNode);
				
				// group h3-tags together
				var ref = null;
				var divNode = null;
				dojo.forEach(roomAreaObjects, lang.hitch(this, function(roomAreaObject, index, arr) {
					// save first room area
					if(index === 0) {
						ref = roomAreaObject;
						divNode = Query("div.clear", ref)[0];
					}
					
					// otherwise move its rooms to first room
					else {
						dojo.forEach(Query("div.room_change_item", roomAreaObject), function(roomAreaRoom, index, arr) {
							DomConstruct.place(roomAreaRoom, divNode, "before");
						});
						
						// remove room area
						DomConstruct.destroy(roomAreaObject);
					}
				}));
				
				/*
				 * holds the latest appearance of a room
				 * D D D D R D D R D D D D D
				 * 				/\
				 * 				||
				 */
				var latestRoomAppearance = -1;
				
				var count = 0;
				dojo.forEach(Query("div.room_change_item, div.room_dummy", ref), lang.hitch(this, function(node, index, arr) {
					// determ type
					if(DomClass.contains(node, "room_dummy")) {
						// dummy - make visible
						DomClass.remove(node, "room_dummy_no_border");
					} else {
						// room - update latest appearance
						latestRoomAppearance = index;
					}
					
					count++;
				}));
				
				var dummiesToAdd = 0;
				
				// not fully filled rows
				if(count % 4 !== 0) dummiesToAdd = 4 + 4 - count % 4;		// this is one complete row + filled last one
				
				// last row contains a room
				else if(latestRoomAppearance > count - 3) {
					dummiesToAdd = 4;
				}
				
				// add dummies
				for(var i=0; i < dummiesToAdd; i++) {
					DomConstruct.create("div", {
						className:	"room_dummy"
					}, divNode, "before");
				}
				
				// remove all h3-tags
				dojo.forEach(Query("> h3", blockNode), function(h3Node, index, arr) {
					DomConstruct.destroy(h3Node, blockNode);
				});
				
				// make h2-tags to inputs
				dojo.forEach(Query("> h2", blockNode), function(h2Node, index, arr) {
					// replace
					DomConstruct.create("input", {
						value:	DomAttr.get(h2Node, "innerHTML")
					}, h2Node, "replace");
				});
			}));
			
			// add new block area link
			var newBlockDivNode = DomConstruct.create("div", {
				className:	"roomlist_append_block"
			});
			
				var newBlockANode = DomConstruct.create("a", {
					"id":		"roomlist_append_block",
					href:		"#",
					innerHTML:	this.from_php.i18n["COMMON_NEW_BLOCK"]
				}, newBlockDivNode, "last");
			
			DomConstruct.place(	newBlockDivNode,
								Query("div#profile_content_row_three div.room_block:last-child", this.contentNode)[0],
								"after");
			
			// register click event
			On(newBlockANode, "click", lang.hitch(this, function(event) {
				this.appendNewBlock();
				
				event.preventDefault();
			}));
			
			// add save link
			var saveDivNode = DomConstruct.create("div", {
				className:	"roomlist_save"
			});
			
				var saveANode = DomConstruct.create("a", {
					"id":		"roomlist_save",
					href:		"#",
					innerHTML:	this.from_php.i18n["COMMON_SAVE_BUTTON"]
				}, saveDivNode, "last");
			
			DomConstruct.place(	saveDivNode,
								Query("div#profile_content_row_three", this.contentNode)[0],
								"last");
			
			DomConstruct.create("div", {
				className:		"clear"
			}, saveDivNode, "after");
			
			// register click event
			On(saveANode, "click", lang.hitch(this, function(event) {
				this.saveRoomList();
				// reload to exist edit mode
				window.location.reload(true);
				
				event.preventDefault();
			}));
			
			// setup sortabes
			this.setupSortables(contentObjects);
		},
		
		setupSortables: function(contentObjects) {
			// first we get all sources
			var sourceNodes = [];
			dojo.forEach(contentObjects, function(contentObject, index, arr) {
				dojo.forEach(Query("div.breadcrumb_room_area", contentObject), function(sourceNode, index, arr) {
					sourceNodes.push(sourceNode);
				});
			});
			
			// make all sources a dojo.dnd.Source and set nodes
			var sources = [];
			dojo.forEach(sourceNodes, lang.hitch(this, function(sourceNode, index, arr) {
				// register
				sources.push(new Source(sourceNode, {
					singular:	true/*,
					horizontal:	true*/
				}));
				
				// set nodes
				var roomNodes = Query("div.room_change_item, div.room_dummy", sourceNode);
				sources[index].insertNodes(false, roomNodes, Query("div.clear", sourceNode)[0]);
			}));
		},
		
		appendNewBlock: function() {
			// build main structure
			var roomBlockDiv = DomConstruct.create("div", {
				className:		"room_block"
			}, Query("div#profile_content_row_three div.roomlist_append_block", this.contentNode)[0], "before");
			
				DomConstruct.create("input", {
					value:	this.from_php.i18n["COMMON_NEW_BLOCK"]
				}, roomBlockDiv, "last");		
				
				var roomBlockAreaDiv = DomConstruct.create("div", {
					className:	"breadcrumb_room_area"
				}, roomBlockDiv, "last");
			
			// append eight dummies
			for(var i=0; i < 8; i++) {
				DomConstruct.create("div", {
					className:	"room_dummy"
				}, roomBlockAreaDiv, "last");
			}
			
			// append clearing div
			DomConstruct.create("div", {
				className:	"clear"
			}, roomBlockAreaDiv, "last");
			
			// make sortable
			this.setupSortables(new dojo.NodeList(roomBlockDiv));
		},
		
		onPopupSubmitSuccess: function(item_id) {
			this.close();
		},
		
		saveRoomList: function() {
			var data = {
				module:		"breadcrumb",
				form_data:	[]
			};
			var roomConfig = [];
			
			// prepare form data
			dojo.forEach(Query("div#profile_content_row_three div.room_block"), function(node, index, arr) {
				// get title from input
				roomConfig.push({
					type:		"title",
					value:		DomAttr.get(Query(">input", node)[0], "value")
				});
				
				// get room and spaces
				dojo.forEach(Query("div.breadcrumb_room_area div.room_change_item, div.breadcrumb_room_area div.room_dummy", node), function(roomNode) {
					// determ type
					var type = "room";
					var value = "";
					
					if(DomClass.contains(roomNode, "room_dummy")) type = "dummy";
					else value = DomAttr.get(Query("input[name='hidden_item_id']", roomNode)[0], "value");
					
					roomConfig.push({
						type:		type,
						value:		value
					});
				});
			});
			
			data.form_data.push({
				'name':		'room_config',
				'value':	roomConfig
			});
			
			// save
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'popup',
					action:	'save'
				},
				data: data
			}).then(
				lang.hitch(this, function(response) {
					this.close();
				})
			);
		}
	});
});