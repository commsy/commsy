define([	"dojo/_base/declare",
        	"dojo/_base/xhr",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/dnd/Source"], function(declare, xhr, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang, Source) {
	return declare(TogglePopupHandler, {
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "connection";
			
			this.features = [];
			this.loading_result = '';
			
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
		
		loadContent2: function(name, notloaded) {
			var retour = '';
			if (notloaded.indexOf('notloaded') >= 0) {
				this.setupLoading();
				
				var result = this.request("popup", "getHTML", { module: this.module, id: name});
				result.then(
					      function(response){
					    	  retour = response.data;
					    	  alert('RESULT');
					    	  return retour;
					      }
					  );
		        
				// sonst ist retour leer [TBD]
				alert(this.from_php.i18n["CS_BAR_CONNECTION_PLEASE_WAIT_JS"]);
				this.destroyLoading();
			}
			
			// edit button
			this.setupSpecificEdit();
			
			return retour;
		},
		
		loadContent: function(name, notloaded) {
			if (notloaded.indexOf('notloaded') >= 0) {
			   var content_nodes = Query("div#popup_tabcontent div.tab, div.popup_tabcontent div.tab", this.contentNode);
			   var index;
			   for (index = 0; index < content_nodes.length; ++index) {
				   var node = content_nodes[index];
				   var nodeName = DomAttr.get(node, "id");
				   if (name === nodeName) {
	    				this.setupLoading();

						// perform ajax request
	    				var fct = "popup";
	    				var action = "getHTML";
	    				var data = { module: this.module, id: name};
	    				var args = {
	    						url:		"commsy.php?cid=" + this.uri_object.cid + "&mod=ajax&fct=" + fct + "&action=" + action,
	    						headers:	{
	    									"Content-Type":		"application/json; charset=utf-8",
	    									"Accept":			"application/json"
	    						},
	    						postData:	dojo.toJson(data),
	    						handleAs:	"json"
	    					};
	    				
	    				//declare.safeMixin(args, mixin);
	    				var request = xhr.post(args);

	    				// setup deferred
	    				request.then(function(response) {
	    					if(response.status === "success") {
								// only once
	    						DomAttr.remove(node, "notloaded");
								// set newcontent
	    						DomAttr.set(node, "innerHTML", response.data);
	    						// register click for room links
								dojo.forEach(Query("div.room_change_item",this.contentNode), Lang.hitch(this, function(node2, index, arr) {
									// get href
									var href1 = DomAttr.get(node2, "data-custom");
									var href2 = dojo.fromJson("{" + href1 + "}");
									var href = href2.href;
									On(node2, "click", function(event) {
										location.href = href;
									});
									
								}));
								
	    					}
	    				});

						this.destroyLoading();
	    			}
	    			
					// edit button
					this.setupSpecificEdit();
				}
			}
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
			
			//this.submit(search, { part: part });
		},
		
		setupSpecific: function() {
			// register click for edit button
			this.setupSpecificEdit();
			
			// save
			// register click for additional status button
			var editButtonArray = Query("input#submit_current", this.contentNode);
			if (editButtonArray.length > 0) {
			   On(Query("input#submit_current", this.contentNode)[0], "click", Lang.hitch(this, function(event) {
				   this.saveCurrentTabs();
			   }));
			}
			
			// save new
			// register click for additional status button
			On(Query("input#submit_new", this.contentNode)[0], "click", Lang.hitch(this, function(event) {
				this.saveNewTab();
			}));

			// drag and drop [TBD]
			//var wishListNode = Query("ol#wishListNode", this.contentNode)[0]
			//var wishlist = new Source(wishListNode);
			
			// register click for room links
			dojo.forEach(Query("div.room_change_item", this.contentNode), Lang.hitch(this, function(node, index, arr) {
				// get href
				var href = this.getAttrAsObject(node, "data-custom").href;
				
				On(node, "click", function(event) {
					location.href = href;
				});
			}));
		},
		
		setupSpecificEdit: function() {
			// register click for edit button
			var aEditNode = Query("a#edit_connections", this.contentNode)[0]
			if (aEditNode) {
				On.once(aEditNode, "click", Lang.hitch(this, function(event) {
					this.setupEditMode();
				}));
			};			
		},
		
		saveNewTab: function() {
			
			var data = [];
			var part = 'tabs';
			var action = 'saveNew';
			
			// get all form fields
			var inputNodes = Query("input", this.contentNode);
			var selectNodes = Query("select", this.contentNode);
			inputNodes.push(selectNodes[0]);
			
			// get all new_fields
			for (index = 0; index < inputNodes.length; ++index) {
	            var node = inputNodes[index];
				var nodeName = DomAttr.get(node, "name");
				var nodeKey = /new_/;
				var nodeMatch = nodeName.search(nodeKey);
				if (nodeMatch != -1) {
					var nodeList = [];
					nodeList.push(node);
					data.push({ query: nodeList } );
				}			    
			}
			
			// save new tab
			var search = {
					tabs: [],
					nodeLists: data
			};
				
			this.submit(search, { part: part, action: action });
			
			// add new to form
			//var newTabNode = DomConstruct.create('div',{
			//	innerHTML: 'HALLO DIE ENTEN'
			//});
			//var newNodeBegin = Query("div#new_tabs_for_edit", this.contentNode)[0];
			//DomConstruct.place(newTabNode,newNodeBegin,'after');
			

		},

		saveCurrentTabs: function() {
			
			var data = [];
			var part = 'tabs';
			var action = 'save';
			var inputNodes = Query("input", this.contentNode);
			
			for (index = 0; index < inputNodes.length; ++index) {
	            var node = inputNodes[index];
				var nodeName = DomAttr.get(node, "name");
				var nodeKey = /form_data/;
				var nodeMatch = nodeName.search(nodeKey);
				if (nodeMatch != -1) {
					var nodeList = [];
					nodeList.push(node);
					data.push({ query: nodeList } );
				}			    
			}

			var search = {
				tabs: [],
				nodeLists: data
			};
			
			this.submit(search, { part: part, action: action });
			
		},

		setupEditMode: function() {
			
			// tabs
			var contentTabs = Query("div#tabs", this.contentNode)[0];
			var contentTabsClass = DomAttr.get(contentTabs, "class");
			if (contentTabsClass == "hidden") {
			   DomClass.remove(contentTabs,"hidden");
			} else {
			   DomClass.add(contentTabs,"hidden");				
			}
			
			// edit
			var contentTabsEdit = Query("div#tabs_edit", this.contentNode)[0];
			var contentTabsEditClass = DomAttr.get(contentTabsEdit, "class");
			if (contentTabsEditClass == "hidden") {
               DomClass.remove(contentTabsEdit, "hidden");
			} else {
			   DomClass.add(contentTabsEdit,"hidden");				
			}			
			
			this.setupSpecificEdit();
		},
		
		/************************************************************************************
		 * Success Handling
		 ************************************************************************************/

		onPopupSubmitSuccess: function(item_id) {
			if ( item_id == 42 ) {
				// success
				location.reload();				
			} else {
				var key = /error_/;
				var match = item_id.search(key);
				if ( match != -1 ) {
				   if ( item_id == 'error_1') {
					  alert(this.from_php.i18n["CS_BAR_CONNECTION_JS_ERROR_1"]);
				   } else if ( item_id == 'error_2') {
					  alert(this.from_php.i18n["CS_BAR_CONNECTION_JS_ERROR_2"]);
				   } else if ( item_id == 'error_3' || item_id == 'error_4' || item_id == 'error_5' || item_id == 'error_6' ) {
					  alert(this.from_php.i18n["CS_BAR_CONNECTION_JS_ERROR_3"]);
				   } else {
				      alert(item_id);
				   }
				} else {
				   location.reload();
				}
			}
		},
		
		/************************************************************************************
		 * Error Handling
		 ************************************************************************************/
		onPopupSubmitError: function(response) {
			if (response.reason) {
			   alert(response.reason);
			} else if (response.code) {
			   alert(response.code);				
			} else {
			   alert('ERROR');
			}
		}		
	});
});