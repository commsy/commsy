define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/dnd/Source"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang, Source) {
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
			On.once(Query("a#edit_roomlist", this.contentNode)[0], "click", Lang.hitch(this, function(event) {
				this.setupEditMode();
			}));
			
			// register click for room links
			Query("div.room_change_item", this.contentNode).forEach(Lang.hitch(this, function(node, index, arr) {
				// get href
				var href = this.getAttrAsObject(node, "data-custom").href;
				
				On(node, "click", function(event) {
					location.href = href;
				});
			}));
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			// add ckeditor data to hidden div
			this.featureHandles["editor"].forEach(function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;
				
				DomAttr.set(Query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});
			
			// setup data to send via ajax
			var search = {
				tabs: [
				    { id: part }
				],
				nodeLists: [
				]
			};
			
			this.submit(search, { part: part });
			
			/*
			 * var handle = event.data.handle;
			var target = jQuery(event.target);
			
			// submit picture
			var form_objects = jQuery('form#logo_upload, form#bg_upload');
			
			var all = 0;
			form_objects.each(function(index) {
				if(jQuery(this).find('input[type="file"]').attr('value') !== '') {
					all++;
				}
			});
			
			if(all == 0) {
		
				thishandle.saveConfiguration(event);
				
				
			}
			
			var index = 0;
			form_objects.each(function() {
				if(jQuery(this).find('input[type="file"]').attr('value') !== '') {
					handle.uploadRoomPicture(jQuery(this), index, all, handle.saveConfiguration, event);
					index++;
				}
			});
			 */
		},
		
		setupEditMode: function() {
			var contentObjects = Query(	"div#profile_content_row_three, div#profile_content_row_four", this.contentNode);
			
			// make hidden rooms visible
			DomClass.remove(contentObjects[1], "hidden");
			
			// process each room block
			Query("div.room_block", this.contentNode).forEach(Lang.hitch(this, function(blockNode, index, arr) {
				var roomAreaObjects = Query("div.breadcrumb_room_area", blockNode);
				
				// group h3-tags together
				var ref = null;
				var divNode = null;
				roomAreaObjects.forEach(Lang.hitch(this, function(roomAreaObject, index, arr) {
					// save first room area
					if(index === 0) {
						ref = roomAreaObject;
						divNode = Query("div.clear", ref)[0];
					}
					
					// otherwise move its rooms to first room
					else {
						Query("div.room_change_item", roomAreaObject).forEach(function(roomAreaRoom, index, arr) {
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
				Query("div.room_change_item, div.room_dummy", ref).forEach(Lang.hitch(this, function(node, index, arr) {
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
				Query("> h3", blockNode).forEach(function(h3Node, index, arr) {
					DomConstruct.destroy(h3Node, blockNode);
				});
				
				// make h2-tags to inputs
				Query("> h2", blockNode).forEach(function(h2Node, index, arr) {
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
			On(newBlockANode, "click", Lang.hitch(this, function(event) {
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
			On(saveANode, "click", Lang.hitch(this, function(event) {
				this.saveRoomList();
				
				event.preventDefault();
			}));
			
			// setup sortabes
			this.setupSortables(contentObjects);
		},
		
		setupSortables: function(contentObjects) {
			// first we get all sources
			var sourceNodes = [];
			contentObjects.forEach(function(contentObject, index, arr) {
				Query("div.breadcrumb_room_area", contentObject).forEach(function(sourceNode, index, arr) {
					sourceNodes.push(sourceNode);
				});
			});
			
			// make all sources a dojo.dnd.Source and set nodes
			var sources = [];
			sourceNodes.forEach(Lang.hitch(this, function(sourceNode, index, arr) {
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
			Query("div#profile_content_row_three div.room_block").forEach(function(node, index, arr) {
				// get title from input
				roomConfig.push({
					type:		"title",
					value:		DomAttr.get(Query(">input", node)[0], "value")
				});
				
				// get room and spaces
				Query("div.breadcrumb_room_area div.room_change_item, div.breadcrumb_room_area div.room_dummy", node).forEach(function(roomNode) {
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
			this.AJAXRequest("popup", "save", data, Lang.hitch(this, function(response) {
				this.close();
			}));
		}
	});
});

/*
		
		sortableOnStop: function(event, ui) {
			// process each room area
	    	jQuery('div.breadcrumb_room_area').each(function() {
	    		// get number of elements in this area
	    		var num_elements = jQuery(this).children('a.room_change_item, div.room_dummy').length;

	    		// fill with dummies if elements missing
	    		if(num_elements % 4 !== 0) {
	    			for(var i = 0; i < 4 - (num_elements % 4); i++) {
	    				jQuery(this).find('div.clear').before(jQuery('<div/>', {'class': 'room_dummy'}));
	    			}
	    		}

	    		// ensure one empty row below the last room in area
	    		/*
				 * holds the latest appearance of a room
				 * D D D D R D D R D D D D D
				 * 				/\
				 * 				||
				 *//*
				var latest_room_appearance = -1;

				jQuery(this).find('a.room_change_item, div.room_dummy').each(function(index) {
					// determ type
					if(jQuery(this).hasClass('room_change_item')) {
						// room
						// update latest appearance
						latest_room_appearance = index;
					}
				});

				if(latest_room_appearance > -1) {
					var num_dummies_after_last_room = num_elements - latest_room_appearance - 1;

					if(num_dummies_after_last_room <= 3) {
						// add a row of dummies
						for(var i = 0; i < 4; i++) {
		    				jQuery(this).find('div.clear').before(jQuery('<div/>', {'class': 'room_dummy'}));
		    			}
					} else if(num_dummies_after_last_room >= 5) {
						// get new latest room appearance
						var new_latest_room_appearance = -1;
						jQuery(this).find('a.room_change_item, div.room_dummy').each(function(index) {
							// determ type
							if(jQuery(this).hasClass('room_change_item')) {
								// room
								// update latest appearance
								new_latest_room_appearance = index;
							}
						});

						// determe number to delete
						var num_delete = num_elements - new_latest_room_appearance - 1 - 4 - ((num_elements - new_latest_room_appearance - 1 - 4) % 4);

						// remove a row of dummies
						for(var i = 0; i < num_delete; i++) {
		    				jQuery(this).find('div.clear').prev().remove();
		    			}
					}
				}
	    	});
		},

		
*/