define([	"dojo/_base/declare",
        	"dojo/_base/xhr",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"commsy/request",
        	"dojo/_base/lang",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/dnd/Source"], function(declare, xhr, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, request, lang, On, Lang, Source) {
	return declare(TogglePopupHandler, {
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "connection";
			
			this.features = [];
			this.loading_result = '';
			
			this.load = -1;
			
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
			
		loadContent: function(name, notloaded) {
			if (notloaded.indexOf('notloaded') >= 0) {
			   var content_nodes = Query("div#popup_tabcontent div.tab, div.popup_tabcontent div.tab", this.contentNode);
			   var index;
			   for (index = 0; index < content_nodes.length; ++index) {
				   var node = content_nodes[index];
				   var nodeName = DomAttr.get(node, "id");
				   if (name === nodeName) {
					    var node42 = node;
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
	    						DomClass.remove(node42, "notloaded");
								// set newcontent
	    						DomAttr.set(node42, "innerHTML", response.data);
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
			var newButtonArray = Query("input#submit_new", this.contentNode);
			if (newButtonArray.length > 0) {			
				On(Query("input#submit_new", this.contentNode)[0], "click", Lang.hitch(this, function(event) {
					this.saveNewTab();
				}));
			}

			// drag and drop
			var wishListNode = Query("ol#wishListNode", this.contentNode);
			if (wishListNode.length > 0) {
			   var wishlist = new Source(wishListNode[0]);
			}
			
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
			var aEditNode = Query("a#edit_connections", this.contentNode)[0];
			if (aEditNode) {
				On.once(aEditNode, "click", Lang.hitch(this, function(event) {
					this.setupEditMode();
				}));
			};			
		},
		
		saveNewTab: function() {
			var index;
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
		},

		saveCurrentTabs: function() {
			var index;
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
			if (contentTabsEditClass.indexOf('hidden') >= 0) {
	           DomClass.remove(contentTabsEdit, "hidden");
	           
	           // edit nachladen
			   if (contentTabsEditClass.indexOf('notloaded') >= 0) {
   				   this.setupLoading();

				   // perform ajax request
				   var fct = "popup";
				   var action = "getHTML";
				   var name = "tabs_edit_new";
				   var data = { module: this.module, id: name};
				   var args = {
						   url:			"commsy.php?cid=" + this.uri_object.cid + "&mod=ajax&fct=" + fct + "&action=" + action,
						   headers:		{
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
				           DomClass.remove(contentTabsEdit, "notloaded");
				           
						   // set newcontent
				           var contentTabsEditNew = Query("div#edit_tab_new", this.contentNode)[0];							
						   DomAttr.set(contentTabsEditNew, "innerHTML", response.data);
					   }
				   });
				   
				   this.destroyLoading();
   			   }
   			   // edit nachladen
   			   
			} else {
			   DomClass.add(contentTabsEdit,"hidden");				
			}			
			
			this.setupSpecificEdit();
		},
		
		reopen: function() {
			if(this.is_loaded === false) {
				this.setupLoading();
				
				//this.statics.togglePopups.push(this);
				
				// setup ajax request for getting html
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'popup',
						action:	'getHTML'
					},
					data: {
						module: this.module
					}
				}).then(lang.hitch(this, function(response) {
					// append html to node
					DomConstruct.place(response.data, this.contentNode, "replace");
					
					this.setupTabs();
					this.setupFeatures();
					this.setupSpecific();
					this.setupSpecificEdit();
					this.onCreate();
					
					// register close
					On(Query("a", this.contentNode)[0], "click", lang.hitch(this, function(event) {
						this.close();
						
						event.preventDefault();
					}));
					
					// register submit click
					On(Query("input.submit", this.contentNode), "click", lang.hitch(this, function(event) {
						// get custom data object
						var customObject = this.getAttrAsObject(event.target, "data-custom");
						this.onPopupSubmit(customObject);
						
						event.preventDefault();
					}));
					
					this.destroyLoading();
				}));
				
				this.is_loaded = true;
			}
			
			this.is_open = !this.is_open;
			
			if (this.is_open) {
				// close all popups before open this
				dojo.forEach(this.statics.togglePopups, lang.hitch(this, function(popup, index, arr) {
					if (popup !== this) {
						popup.close();
						popup.is_open = false;
					}
				}));
				
				/* temporary, until all widgets are migrated to current version */
				var widgetManager = this.getWidgetManager();
				widgetManager.CloseAllWidgets();
				/* ~temporary */
			}
			
			this.onTogglePopup();
		},

		/************************************************************************************
		 * Success Handling
		 ************************************************************************************/

		onPopupSubmitSuccess: function(data) {
			if ( data instanceof Object ) {
			   if ( data['action'] == "new" ) {
				  // "data":{"action":"new","id":"2fc4b5e214926118a8eb2a8de23edc15","server_name":"SERVER","portal_name":"PORTAL","message_delete":"L\u00f6schen"}
				  
				  /*
				  // add new to form - delete
			      var deleteTabs = Query("div#delete_tab", this.contentNode);
			      var position = deleteTabs.length;
			      alert(postion);
			      var lastDeleteTab = deleteTabs[position-1];
			      var newDeleteTab = DomConstruct.create('div',{
						innerHTML: '<input type="hidden" name="form_data[tabid_'+position+']" value="'+data.id+'"/><label for="'+data.id+'">'+data.server_name+'<span class="tm_bcb_next">'+data.portal_name+'</label><input id="'+data.id+'" type="text" class="size_200 mandatory" name="form_data[name_'+data.id+']" value="'+data.portal_name+'"/><input name="form_data[delete_'+data.id+']" type="checkbox" value="1"/>'+data.message_delete+''
					  });
			      DomClass.add(newDeleteTab,"input_row");
			      DomAttr.set(newDeleteTab, "id", "delete_tab");
			      DomConstruct.place(newDeleteTab,lastDeleteTab,'after');
			      
                  // add new to tabs
			      var navTabs = Query("a.pop_tab_active, a.pop_tab", this.contentNode);
			      var position2 = navTabs.length;
			      var lastNavTab = navTabs[position2-1];
			      var newNavTab = DomConstruct.create('a',{
						innerHTML: data.portal_name
					  });
			      DomClass.add(newNavTab,"pop_tab");
			      DomAttr.set(newNavTab, "href", data.id);
			      DomConstruct.place(newNavTab,lastNavTab,'after');			      
			      */
				  location.reload();
				  //this.is_open = false;
				  //this.is_loaded = false;
			      //this.reopen();
			   } else {
				  location.reload();
			   }
			} else {
				// nur zahl - save current
				location.reload();
				//this.is_open = false;
				//this.is_loaded = false;
				//this.reopen();
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