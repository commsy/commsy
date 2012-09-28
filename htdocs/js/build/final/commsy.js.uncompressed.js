require({cache:{
'commsy/tree':function(){
define("commsy/tree", [	"dojo/_base/declare",
        	"dojo/dom-construct",
        	"dojo/io-query",
        	"commsy/base",
        	"dojo/on",
        	"dojo/_base/lang",
        	"cbtree/Tree",
        	"dojo/query",
        	"cbtree/models/ForestStoreModel",
        	"dojo/data/ItemFileWriteStore",
        	"cbtree/CheckBox",
        	"cbtree/models/StoreModel-API"], function(declare, domConstruct, ioQuery, BaseClass, On, lang, Tree, Query, ForestStoreModel, ItemFileWriteStore, CheckBox, DndSource) {
	return declare(BaseClass, {
		followUrl:			true,
		autoExpandLevel:	2,
		checkboxes:			false,
		expanded:			false,
		item_id:			null,
		tree:				null,
		store:				null,
		model:				null,
		room_id:			null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setupTree: function(node, callback, autoInit) {
			autoInit = autoInit || false;
			
			if (!autoInit) {
				var triggerNode = Query("a[href='tags_tab']")[0];
				
				if (triggerNode) {
					On(triggerNode, "click", lang.hitch(this, function(event) {
						this.initDo(node, callback);
					}));
				}
			} else {
				this.initDo(node, callback);
			}
		},
		
		initDo: function(node, callback) {
			callback = callback || function() {};
			
			// get results from ajax call
			this.AJAXRequest('tagtree', 'getTreeData', { item_id: this.item_id, room_id: this.room_id }, lang.hitch(this, function(results) {
				
				results = this.sanitizeResults(results);
				
				this.store = new ItemFileWriteStore({
					data: {
						identifier:		"item_id",
						label:			"title",
						items:			results
					}
				});
				
				// create model
				this.model = this.createModel();
				
				// create tree
				this.tree = this.createTree();
				
				domConstruct.empty(node);
				this.tree.placeAt(node);
				
				// auto expand
				if (this.expanded === true) {
					this.autoExpandToLevel(this.tree, 0, true);
				}
				
				callback(this);
			}));
		},
		
		createModel: function() {
			return new ForestStoreModel({
				store:			this.store,
				checkedAttr:	"match",
				checkedStrict:	false
			});
		},
		
		createTree: function() {
			return new Tree({
				//autoExpand:			this.expanded,		// do not use the tree's routine for this, causing very long loading times in IE8
				model:				this.model,
				showRoot:			false,
				persist:			false,
				checkBoxes:			this.checkboxes,
				onClick:			lang.hitch(this, function(item, node, evt) {
					// follow item url
					if(this.followUrl) {
						if (this.uri_object.mod == "home") {
							this.replaceOrSetURIParam('mod', "search");
						}
						
						location.href = 'commsy.php?' + ioQuery.objectToQuery(this.replaceOrSetURIParam('seltag', item.item_id));
					} else {
						// if click doesn't come from checkbox
						if(evt.target.nodeName !== "INPUT") {
							if(this.model.getChecked(item) === true) {
								this.model.setChecked(item, false);
							} else {
								this.model.setChecked(item, true);
							}
						}
					}
				}),
				widget: {
					type:			CheckBox,
					args: {
						multiState:		true
					},
					mixin:		function(args) {
						args["value"]	= this.item.item_id[0];
						args["name"]	= "form_data[tags]";
					}
				}
			});
		},
		
		autoExpandToLevel: function(tree, maxLevel, expandAll) {
			this.expandHelper(tree, tree.rootNode, 0, maxLevel, expandAll);
		},
		
		getNumItems: function(tree) {
			return this.numHelper(tree.rootNode.item);
		},
		
		/************************************************************************************
		 *** Helper Functions	
		 ************************************************************************************/
		expandHelper: function(tree, node, level, maxLevel, expandAll) {
			var children = node.getChildren();
			
			dojo.forEach(children, lang.hitch(this, function(childrenNode, index, arr) {
				if (expandAll || level < maxLevel) {
					tree._expandNode(childrenNode);
				} else {
					tree._collapseNode(childrenNode);
				}
				
				this.expandHelper(tree, childrenNode, level+1, maxLevel, expandAll);
			}));
		},
		
		numHelper: function(item) {
			var children = item.children;
			var numChildrenItems = 0;
			dojo.forEach(children, lang.hitch(this, function(childrenItem, index, arr) {
				numChildrenItems += this.numHelper(childrenItem);
			}));
			
			return numChildrenItems + 1;
		},
		
		iterateCallback: function(rootItem, callbackFunction) {
			// callback for item itself
			callbackFunction(rootItem);
			
			// get all children
			var children = this.tree.model.getChildren(rootItem, lang.hitch(this, function(children) {
				// recursive call
				dojo.forEach(children, lang.hitch(this, function(child, index, arr) {
					this.iterateCallback(child, callbackFunction);
				}));
			}));
		},
		
		sanitizeResults: function(results) {
			dojo.forEach(results, lang.hitch(this, function(result, index, arr) {
				if (result.children) {
					if (result.children.length === 0) {
						delete result.children;
					} else {
						result.children = this.sanitizeResults(result.children);
					}
				}
				
				results[index] = result;
			}));
			
			return results;
		},
		
		buildPath: function(tagId, path) {
			path = path || [];
			
			// add item to path
			path.push(tagId);
			
			// get item and parent
			var item = this.model.fetchItem({item_id: tagId});
			var parent = this.store.getParents(item)[0];
			if (parent) {
				// continue with parent
				return this.buildPath(this.store.getIdentity(parent), path);
			} else {
				// parent is root
				path.push(this.tree.rootNode.item.id);
				return path.reverse();
			}
		}
	});
});
},
'commsy/popups/ToggleClipboard':function(){
define("commsy/popups/ToggleClipboard", [	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(TogglePopupHandler, {
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "clipboard";
			
			this.features = [ ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_clipboard_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_clipboard_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// setup clipboard functions
			require(["commsy/Clipboard"], Lang.hitch(this, function(Clipboard) {
				var clipboard = new Clipboard();
				clipboard.init(this.cid, this.from_php.template.tpl_path);
			}));
		},
		
		onPopupSubmit: function(customObject) {
			// this popup will not be submitted
		},
		
		onPopupSubmitSuccess: function(item_id) {
		}
	});
});
},
'commsy/ClickPopupHandler':function(){
define("commsy/ClickPopupHandler", [	"dojo/_base/declare",
        	"commsy/PopupHandler",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dom-class",
        	"dijit/Tooltip",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style"], function(declare, PopupHandler, on, lang, query, dom_class, Tooltip, dom_attr, domConstruct, domStyle) {
	return declare(PopupHandler, {
		triggerNode:			null,
		item_id:				null,
		ref_iid:				null,
		ticks:					0,
		ajaxHTMLSource:			"rubric_popup",

		constructor: function(args) {
			this.fct = "rubric_popup";
			this.initData = {};
		},
		
		setInitData: function(object) {
			this.initData = object;
		},

		registerPopupClick: function() {
			on(this.triggerNode, "click", lang.hitch(this, function(event) {
				this.open();
				event.preventDefault();
			}));
		},
		
		open: function() {
			if(this.is_open === false) {
				this.is_open = true;

				this.setupLoading();
				
				var data = { module: this.module, iid: this.item_id, ref_iid: this.ref_iid, editType: this.editType, version_id: this.version_id, contextId: this.contextId, date_new: this.date_new };
				declare.safeMixin(data, this.initData);

				// setup ajax request for getting html
				this.AJAXRequest(this.ajaxHTMLSource, "getHTML", data, lang.hitch(this, function(html) {
					// append html to body
					domConstruct.place(html, query("body")[0], "first");

					this.contentNode = query("div#popup_wrapper")[0];
					this.scrollToNodeAnimated(this.contentNode);

					this.setupTabs();
					this.setupFeatures();
					this.setupSpecific();
					this.setupAutoSave();
					this.onCreate();

					// register close
					on(query("a#popup_close, input#popup_button_abort", this.contentNode), "click", lang.hitch(this, function(event) {
						this.close();

						event.preventDefault();
					}));

					// register submit clicks
					on(query("input.submit", this.contentNode), "click", lang.hitch(this, function(event) {
						// setup loading
						this.setupLoading();

						// get custom data object
						var customObject = this.getAttrAsObject(event.target, "data-custom");
						this.onPopupSubmit(customObject);

						event.preventDefault();
					}));

					this.is_open = !this.is_open;

					this.destroyLoading();
				}));
			}
		},

		setupAutoSave: function() {
			var mode = this.from_php.autosave.mode;
			var limit = this.from_php.autosave.limit;

			if(mode > 0) {
				// autosave is enabled
				require(["dojox/timing", "dojox/string/sprintf"], lang.hitch(this, function() {
					var timer = new dojox.timing.Timer(1000);

					if(mode == 2) {
						// show countdown
						var timerDiv = domConstruct.create("div", {
							className:	"autosave",
							innerHTML:	"00:00:00"
						}, query("div#crt_actions_area", this.contentNode)[0], "first");
					}

					timer.onTick = lang.hitch(this, function() {
						this.ticks++;

						if(this.ticks === limit) {
							// get custom data object
							var customObject = this.getAttrAsObject(query("input.submit", this.contentNode)[0], "data-custom");
							this.onPopupSubmit(customObject);
						}

						if(mode == 2) {
							// update countdown
							var timeLeft = limit - this.ticks;

							// hours
							var hoursLeft = Math.floor(timeLeft / 3600);
							timeLeft -= hoursLeft * 3600;

							// minutes
							var minutesLeft = Math.floor(timeLeft / 60);
							timeLeft -= minutesLeft * 60;

							// seconds
							var secondsLeft = timeLeft;

							var display = dojox.string.sprintf("%02u:%02u:%02u", hoursLeft, minutesLeft, secondsLeft);
							dom_attr.set(timerDiv, "innerHTML", display);
						}
					});

					timer.start();
				}));
			}
		},

		close: function() {
			this.inherited(arguments);
			
			// destroy editors
			if(this.featureHandles["editor"]) {
				dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
					editor.destroy();
				});
			}

			// destroy datepicker
			if(this.featureHandles["calendar"]) {
				dojo.forEach(this.featureHandles["calendar"], function(calendar, index, arr) {
					calendar.destroy();
				});
			}

			// remove from dom
			domConstruct.destroy(this.contentNode);

			// destroy Loading
			this.destroyLoading();

			this.is_open = false;
		}
	});
});
},
'commsy/popups/ToggleWidgets':function(){
define("commsy/popups/ToggleWidgets", [	"dojo/_base/declare",
        	"commsy/WidgetPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, WidgetPopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(WidgetPopupHandler, {
		constructor: function(button_node, content_node) {
			// parent constructor is called automatically
			this.module = "widgets";
			
			this.features = [ ];
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_widgets_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_widgets_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// add some widgets hardcoded
			var widgetArray = [
			    "widgets/WidgetsNewEntries",
			    "widgets/WidgetsReleasedEntries",
			    "widgets/WidgetsRssTicker"/*,
			    "widgets/WidgetsExtensions"*/
			];
			
			this.loadWidgetsManual(widgetArray).then(
				Lang.hitch(this, function(results) {
					// place widgets
					dojo.forEach(results, Lang.hitch(this, function(result, index, arr) {
						if (index < 2) {
							result[1].handle.placeAt(Query("div.widgetAreaLeft", this.contentNode)[0]);
						} else {
							result[1].handle.placeAt(Query("div.widgetAreaRight", this.contentNode)[0]);
						}
					}));
				})
			);
		}
	});
});
},
'commsy/Assessment':function(){
define("commsy/Assessment", [	"dojo/_base/declare",
        	"commsy/base",
        	"dijit/TooltipDialog",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom-class",
        	"dojo/dom-style",
        	"dojo/_base/lang",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, TooltipDialog, DomAttr, Query, On, DomClass, DomStyle, Lang) {
	return declare(BaseClass, {
		display:	false,
		anim:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			// setup vote function
			if(node) {
				if(DomClass.contains(node, "rateable")) {
					var starImageNodes = Query("img", node);
					
					var oldStatus = [];
					dojo.forEach(starImageNodes, Lang.hitch(this, function(starImageNode, index, arr) {
						// store old status
						oldStatus[index] = DomAttr.get(starImageNode, "src");
						
						// register mouseover
						On(starImageNode, "mouseover", Lang.hitch(this, function(event) {
							// set all stars up to the hovered one to full stars
							DomAttr.set(starImageNode, "src", this.from_php.template.tpl_path + "img/star_selected.gif");
							dojo.forEach(new dojo.NodeList(starImageNode).prevAll(), Lang.hitch(this, function(node, index, arr) {
								DomAttr.set(node, "src", this.from_php.template.tpl_path + "img/star_selected.gif");
							}));
						}));
						
						// register click
						On(starImageNode, "click", Lang.hitch(this, function(event) {
							// perform ajax call to register vote
							var data = {
								item_id:	this.uri_object.iid,
								vote:		index + 1
							};
							
							this.AJAXRequest("assessment", "vote", data, function(response) {
								// TODO: implement without reload
								location.reload();
							});
						}));
						
						// register mouseout
						On(starImageNode, "mouseout", Lang.hitch(this, function(event) {
							// set all stars to there previous state
							dojo.forEach(new dojo.NodeList(node).children(), function(node, index, arr) {
								DomAttr.set(node, "src", oldStatus[index]);
							});
						}));
					}));
				}
				
				// register delete function
				var deleteNode = Query("a#assessment_delete_own")[0];
				if(deleteNode) {
					On(deleteNode, "click", Lang.hitch(this, function(event) {
						var data = {
							item_id:	this.uri_object.iid
						};
						
						// perform request
						this.AJAXRequest("assessment", "deleteOwn", data, function(response) {
							// TODO: implement without reload
							location.reload();
						});
					}));
				}
			}
		}
	});
});
},
'commsy/popups/TogglePortfolio':function(){
define("commsy/popups/TogglePortfolio", [	"dojo/_base/declare",
        	"commsy/WidgetPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/parser",
        	"dojo/_base/lang"], function(declare, WidgetPopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Parser, Lang) {
	return declare(WidgetPopupHandler, {
		constructor: function(button_node, content_node) {
			// parent constructor is called automatically
			this.module = "portfolio";
			
			this.features = [ ];
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_portfolio_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_portfolio_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// add some widgets hardcoded
			var widgetArray = [
			    "widgets/Portfolio"
			];
			
			this.loadWidgetsManual(widgetArray).then(
				Lang.hitch(this, function(results) {
					// place widgets
					dojo.forEach(results, Lang.hitch(this, function(result, index, arr) {
						result[1].handle.placeAt(Query("div.portfolioArea", this.contentNode)[0]);
						dojo.parser.parse(this.contentNode);
						result[1].handle.afterParse();
					}));
				})
			);
		}
	});
});
},
'cbtree/models/StoreModel-API':function(){
//
// Copyright (c) 2010-2012, Peter Jekel
// All rights reserved.
//
//	The Checkbox Tree (cbtree), also known as the 'Dijit Tree with Multi State Checkboxes'
//	is released under to following three licenses:
//
//	1 - BSD 2-Clause							 (http://thejekels.com/cbtree/LICENSE)
//	2 - The "New" BSD License			 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L13)
//	3 - The Academic Free License	 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L43)
//
//	In case of doubt, the BSD 2-Clause license takes precedence.
//
define("cbtree/models/StoreModel-API", [
	"dojo/_base/array",
	"dojo/_base/lang",
	"dojo/has",
	"./TreeStoreModel"
], function (array, lang, has, TreeStoreModel) {

	// Add cbTree model API to the available features list 
	has.add("cbtree-storeModel-API", true);

	lang.extend(TreeStoreModel, {

		// =======================================================================
		// Private Methods related to checked states

		_checkOrUncheck: function (/*String|Object*/ query, /*Boolean*/ newState, /*Callback*/ onComplete, 
																/*Context*/ scope, /*Boolean*/ storeOnly) {
			// summary:
			//		Check or uncheck the checked state of all store items that match the
			//		query and have a checked state.
			//		This method is called by either the public methods 'check' or 'uncheck'
			//		providing an easy way to programmatically alter the checked state of a
			//		set of store items associated with the tree nodes.
			//
			// query:
			//		A query object or string. If query is a string the label attribute of
			//		the store is used as the query attribute and the query string assigned
			//		as the associated value.
			// newState:
			//		New state to be applied to the store items.
			// onComplete:
			//		If an onComplete callback function is provided, the callback function
			//		will be called just once, after the last storeItem has been updated as: 
			//		onComplete(matches, updates).
			// scope:
			//		If a scope object is provided, the function onComplete will be invoked
			//		in the context of the scope object. In the body of the callback function,
			//		the value of the "this" keyword will be the scope object. If no scope is
			//		is provided, onComplete will be called in the context of tree.model.
			// storeOnly:
			//		See fetchItemsWithChecked() 
			// tag:
			//		private

			var matches = 0,
					updates = 0;

			this.fetchItemsWithChecked(query, function (storeItems) {
				array.forEach(storeItems, function (storeItem) {
					if (this.store.getValue(storeItem, this.checkedAttr) != newState) {
						this._ItemCheckedSetter(storeItem, newState);
						updates += 1; 
					}
					matches += 1;
				}, this)
				if (onComplete) {
					onComplete.call((scope ? scope : this), matches, updates);
				}
			}, this, storeOnly);
		},

		// =======================================================================
		// Data store item getters and setters
		
		_ItemCheckedGetter: function (/*dojo.data.Item*/ storeItem) {
			// summary:
			//		Get the current checked state from the data store for the specified item.
			//		This is the hook for getItemAttr(item,"checked")
			// description:
			//		Get the current checked state from the dojo.data store. The checked state
			//		in the store can be: 'mixed', true, false or undefined. Undefined in this
			//		context means no checked identifier (checkedAttr) was found in the store
			// storeItem:
			//		The item in the dojo.data.store whose checked state is returned.
			// tag:
			//		private
			// example:
			//		var currState = model.get(item,"checked");

			return this.getChecked(storeItem);
		},

	 _ItemCheckedSetter: function (/*dojo.data.Item*/ storeItem, /*Boolean*/ newState) {
			// summary:
			//		Update the checked state for the store item and the associated parents
			//		and children, if any. This is the hook for setItemAttr(item,"checked",value).
			// description:
			//		Update the checked state for a single store item and the associated
			//		parent(s) and children, if any. This method is called from the tree if
			//		the user checked/unchecked a checkbox. The parent and child tree nodes
			//		are updated to maintain consistency if 'checkedStrict' is set to true.
			//	storeItem:
			//		The item in the dojo.data.store whose checked state needs updating.
			//	newState:
			//		The new checked state: 'mixed', true or false
			// tags:
			//		private
			//	example:
			//		model.set(item,"checked",newState);
			
			this.setChecked(storeItem, newState);
		},

		_ItemIdentityGetter: function (storeItem){
			// summary:
			//		Provide the hook for getItemAttr(storeItem,"identity") calls. The 
			//		getItemAttr() interface is the preferred method over the legacy
			//		getIdentity() method.
			// storeItem:
			//		The store or root item whose identity is returned.
			// tag:
			//		private

			if (this.store.isItem(storeItem)) {			
				return this.store.getIdentity(storeItem);	// Object
			} else {
				if (storeItem === this.root){
					return this.root.id;
				}
			}
			throw new TypeError(this.moduleName+"::getIdentity(): invalid item specified.");
		},

		_ItemIdentitySetter: function (storeItem, value){
			// summary:
			//		Hook for setItemAttr(storeItem,"identity",value) calls. However, changing 
			//		the identity of a store item is NOT allowed.
			// tags:
			//		private
			throw new Error(this.moduleName+"::setItemAttr(): Identity attribute cannot be changed");
		},

		_ItemLabelGetter: function (storeItem){
			// summary:
			//		Provide the hook for getItemAttr(storeItem,"label") calls. The getItemAttr()
			//		interface is the preferred method over the legacy getLabel() method.
			// storeItem:
			//		The store item whose label is returned.
			// tag:
			//		private

			if (storeItem !== this.root){
				if (this.labelAttr){
					return this.store.getValue(storeItem,this.labelAttr);	// String
				}else{
					return this.store.getLabel(storeItem);	// String
				}
			}
			return this.root.label;
		},

		_ItemLabelSetter: function (storeItem, value){
			// summary:
			//		Hook for setItemAttr(storeItem,"label",value) calls. However, changing
			//		the label value is only allowed if the label attribute isn't the same
			//		as the store identity attribute.
			// storeItem:
			//		The store item whose label is being set.
			// value:
			//		New label value.
			// tags:
			//		private
			
			var labelAttr = this.get("labelAttr");

			if (labelAttr){
				if (labelAttr != this.store.getIdentifierAttr()){
					return this.store.setValue(storeItem, labelAttr, value);
				}
				throw new Error(this.moduleName+"::setItemAttr(): Label attribute {"+labelAttr+"} cannot be changed");
			}
		},

		_ItemParentsGetter: function (storeItem) {
			// summary:
			// storeItem:
			//		The store item whose parent(s) are returned.
			return this.getParents(storeItem);
		},

		getItemAttr: function (/*dojo.data.Item*/ storeItem , /*String*/ attribute){
			// summary:
			//		Provide the getter capabilities for store items thru the model. 
			//		The getItemAttr() method strictly operates on store items not
			//		the model itself.
			// storeItem:
			//		The store item whose property to get.
			// attribute:
			//		Name of property to get
			// tag:
			//		public
			
			var attr = (attribute == this.checkedAttr ? "checked" : attribute);

			if (this.isItem(storeItem) || storeItem === this.root) {
				var func = this._getFuncNames("Item", attr);
				if (lang.isFunction(this[func.get])) {
					return this[func.get](storeItem);
				} else {
					if (storeItem === this.root && this.hasFakeRoot) {
						return this.root[attr];
					}
					return this.store.getValue(storeItem, attr)
				}
			}
			throw new Error(this.moduleName+"::getItemAttr(): argument is not a valid store item.");
		},

		setItemAttr: function (/*dojo.data.item*/ storeItem, /*String*/ attribute, /*anytype*/ value) {
			// summary:
			//		Provide the setter capabilities for store items thru the model.
			//		The setItemAttr() method strictly operates on store items not
			//		the model itself.
			// storeItem:
			//		The store item whose property is to be set.
			// attribute:
			//		Property name to set.
			// value:
			//		Value to be applied.
			// tag:
			//		public
			
			if (this._writeEnabled) {
				var attr = (attribute == this.checkedAttr ? "checked" : attribute);
				if (this.isItem(storeItem)) {
					var func = this._getFuncNames("Item", attr);
					if (lang.isFunction(this[func.set])) {
						return this[func.set](storeItem,value);
					} else {
						return this.store.setValue(storeItem, attr, value);
					}
				} else {
					throw new Error(this.moduleName+"::setItemAttr(): argument is not a valid store item.");
				}
			} else {
				throw new Error(this.moduleName+"::setItemAttr(): store is not write enabled.");
			}
		},
		 
		// =======================================================================
		// Inspecting nad validating items

		fetchItem: function (/*String|Object*/ args, /*String?*/ identAttr){
			// summary:
			//		Get the store item that matches args. Parameter args is either an
			//		object or a string.
			// args:
			//		An object or string used to query the store. If args is a string its
			//		value is assigned to the store identifier property in the query.
			// identAttr:
			//		Optional attribute name. If specified, the attribute in args to be
			//		used as the identifier otherwise the default store identifier is
			//		used.
			// tag:
			//		public
			
			var identifier = this.store.getIdentifierAttr();
			var idQuery		= this._anyToQuery(args, identAttr);

			if (idQuery){
				if (idQuery[identifier] != this.root.id){
					return this.store.itemExist(idQuery);
				}
				return this.root;
			}
		},
		
		fetchItemsWithChecked: function (/*String|Object*/ query, /*Callback?*/ onComplete, /*Context?*/ scope, 
																			/*Boolean?*/ storeOnly) {
			// summary:
			//		Get the list of store items that match the query and have a checked 
			//		state, that is, a checkedAttr property.
			// description:
			//		Get the list of store items that match the query and have a checked
			//		state. This method provides a simplified interface to the data stores
			//		fetch() method.
			//	 query:
			//		A query object or string. If query is a string the identifier attribute
			//		of the store is used as the query attribute and the string assigned as
			//		the associated value.
			//	onComplete:
			//		 User specified callback method which is called on completion with an
			//		array of store items that matched the query argument. Method onComplete
			//		is called as: onComplete(storeItems) in the context of scope if scope
			//		is specified otherwise in the active context (this).
			//	scope:
			//		If a scope object is provided, the function onComplete will be invoked
			//		in the context of the scope object. In the body of the callback function,
			//		the value of the "this" keyword will be the scope object. If no scope 
			//		object is provided, onComplete will be called in the context of tree.model.
			// storeOnly:
			//		Indicates if the fetch operation should be limited to the in-memory store
			//		only. Some stores may fetch data from a back-end server when perfroming a
			//		deep search. However, when querying attributes, some attributes may only
			//		be available in the in-memory store such is the case with a FileStore 
			//		having custom attributes. (See FileStore.fetch() for additional details).
			// tag:
			//		public
			
			var storeQuery = this._anyToQuery( query, null );
			var storeItems = [];
			var storeOnly  = (storeOnly !== undefined) ? storeOnly : true;
			var scope      = scope || this;
			
			if (lang.isObject(storeQuery)){
				this.store.fetch({	
					query: storeQuery,
					//	Make sure ALL items are searched, not just top level items.
					queryOptions: { deep: true, storeOnly: storeOnly },
					onItem: function (storeItem, request) {
						// Make sure the item has the appropriate attribute so we don't inadvertently
						// start adding checked state properties unless 'checkedAll' is true.
						if (this.store.hasAttribute(storeItem, this.checkedAttr)) {
							storeItems.push(storeItem);
						} else {
							// If the checked attribute is missing it can be an indication the item
							// has not been rendered yet in any tree. Therefore check if it should
							// have the attribute and, if so, create it and apply the default state.
							if (this.checkedAll) {
								this.setChecked(storeItem, this.checkedState);
								storeItems.push(storeItem);
							}
						}
					},
					onComplete: function () {
						if (onComplete) {
							onComplete.call(scope, storeItems);
						}
					},
					onError: this.onError,
					scope: this
				});
			} else {
				throw new Error(this.moduleName+"::fetchItemsWithChecked(): query must be of type object.");
			}
		},

		isRootItem: function (/*AnyType*/ something){
			// summary:
			//		Returns true if 'something' is a top level item in the store otherwise false.
			// item:
			//		A valid dojo.data.store item.
			// tag:
			//		public
			
			if (something !== this.root){
				return this.store.isRootItem(something);
			}
			return true;
		},

		// =======================================================================
		// Write interface

		addReference: function (/*dojo.data.item*/ childItem, /*dojo.data.item*/ parentItem, /*String?*/ childrenAttr){
			// summary:
			//		Add an existing item to the parentItem by reference.
			// childItem:
			//		Child item to be added to the parents childrens list by reference.
			// parentItem:
			//		Parent item.
			// childrenAttr:
			//		Property name of the parentItem identifying the childrens list to
			//		which the reference is added.
			// tag:
			//		public

			var listAttr = childrenAttr || this.childrenAttrs[0];
			if (this.store.addReference(childItem, parentItem, listAttr)){
				this._updateCheckedParent(childItem);
			}
		},

		attachToRoot: function (/*dojo.data.item*/ storeItem){
			// summary:
			//		Promote a store item to a top level item.
			// storeItem:
			//		A valid dojo.data.store item.
			// tag:
			//		public
			
			if (storeItem !== this.root){
				this.store.attachToRoot(storeItem);
			}
		},
		
		check: function (/*Object|String*/ query, /*Callback*/ onComplete, /*Context*/ scope, /*Boolean?*/ storeOnly) {
			// summary:
			//		Check all store items that match the query and have a checked state.
			// description:
			//		See description _checkOrUncheck()
			//	example:
			//		model.check({ name: "John" }); 
			//	| model.check("John", myCallback, this);
			// tag:
			//		public
			
			// If in strict checked mode the store is already loaded and therefore no
			// need to fetch the store again.
			if (this.checkedStrict) {
				storeOnly = true;
			}
			this._checkOrUncheck(query, true, onComplete, scope, storeOnly);
		},
		
		detachFromRoot: function (/*dojo.data.item*/ storeItem) {
			// summary:
			//		Detach item from the root by removing it from the stores top level item
			//		list
			// storeItem:
			//		A valid dojo.data.store item.
			// tag:
			//		public
			
			if (storeItem !== this.root){
				this.store.detachFromRoot(storeItem);
			}
		},

		newReferenceItem: function (/*dojo.dnd.Item*/ args, /*dojo.data.item*/ parent, /*int?*/ insertIndex){
			// summary:
			//		Create a new top level item and add it as a child to the parent.
			// description:
			//		In contrast to the newItem() method, this method ALWAYS creates the
			//		new item as a top level item regardsless if a parent is specified or
			//		not.
			// args:
			//		A javascript object defining the initial content of the item as a set
			//		of JavaScript 'property name: value' pairs.
			// parent:
			//		Optional, a valid store item that will serve as the parent of the new
			//		item. (see also newItem())
			// insertIndex:
			//		If specified the location in the parents list of child items.
			// tag:
			//		public
			
			var newItem;

			newItem = this.newItem(args, parent, insertIndex);
			if (newItem) {
				this.store.attachToRoot(newItem); // Make newItem a top level item.
			}
			return newItem;
		},

		removeReference: function (/*dojo.data.item*/ childItem, /*dojo.data.item*/ parentItem, /*String?*/ childrenAttr){
			// summary:
			//		Remove a child reference from its parent. Only the references are
			//		removed, the childItem is not delete.
			// childItem:
			//		Child item to be removed from parents children list.
			// parentItem:
			//		Parent item.
			// childrenAttr:
			// tag:
			//		public
			
			var listAttr = childrenAttr || this.childrenAttrs[0];
			if (this.store.removeReference(childItem, parentItem, listAttr)){
				// If any children are left get the first and update the parent checked state.
				this.getChildren(parentItem, lang.hitch(this,
					function (children){
						if (children.length) {
							this._updateCheckedParent(children[0]);
						}
					})
				); /* end getChildren() */
			}
		},
		
		uncheck: function (/*Object|String*/ query, /*Callback*/ onComplete, /*Context*/ scope, /*Boolean?*/ storeOnly) {
			// summary:
			//		Uncheck all store items that match the query and have a checked state.
			// description:
			//		See description _checkOrUncheck()
			//	example:
			//		uncheck({ name: "John" });
			//	| uncheck("John", myCallback, this);
			// tag:
			//		public
			
			// If in strict checked mode the store is already loaded and therefore no
			// need to fetch the store again.
			if (this.checkedStrict) {
				storeOnly = true;
			}
			this._checkOrUncheck(query, false, onComplete, scope, storeOnly);
		},

		// =======================================================================
		// Misc Private Methods

		_anyToQuery: function (/*String|Object*/ args, /*String?*/ attribute){
			// summary:
			// args:
			//		 Query object, if args is a string it value is assigned to the store
			//		identifier property in the query.
			// attribute:
			//		Optional attribute name.	If specified, the attribute in args to be
			//		used as its identifier. If an external item is dropped on the tree,
			//		the new item may no have the same identifier property as all store
			//		items do.
			// tag:
			//		private

			var identAttr = this.store.getIdentifierAttr();
					
			if (identAttr){
				var objAttr = attribute ? attribute : identAttr,
						query = {};
				if (lang.isString(args)) {
					query[identAttr] = args;
					return query;
				} 
				if (lang.isObject(args)){
					lang.mixin( query, args );
					if (args[objAttr]) {
						query[identAttr] = args[objAttr]
					}
					return query;
				}
			}
			return null;
		},

		_getFuncNames: function (/*String*/ prefix, /*String*/ name) {
			// summary:
			//		Helper function for the get() and set() methods. Returns the function names
			//		in lowerCamelCase for the get and set functions associated with the 'name'
			//		property.
			// name:
			//		Attribute name.
			// tags:
			//		private

			if (lang.isString(name)) {
				var cc = name.replace(/^[a-z]|-[a-zA-Z]/g, function (c){ return c.charAt(c.length-1).toUpperCase(); });
				var fncSet = { set: "_"+prefix+cc+"Setter", get: "_"+prefix+cc+"Getter" };
				return fncSet;
			}
			throw new Error(this.moduleName+"::_getFuncNames(): get"+prefix+"/set"+prefix+" attribute name must be of type string.");
		}

	});	/* end lang.extend() */

});	/* end define() */

},
'dijit/tree/TreeStoreModel':function(){
define("dijit/tree/TreeStoreModel", [
	"dojo/_base/array", // array.filter array.forEach array.indexOf array.some
	"dojo/aspect", // aspect.after
	"dojo/_base/declare", // declare
	"dojo/_base/lang" // lang.hitch
], function(array, aspect, declare, lang){

	// module:
	//		dijit/tree/TreeStoreModel

	return declare("dijit.tree.TreeStoreModel", null, {
		// summary:
		//		Implements dijit/Tree/model connecting to a dojo.data store with a single
		//		root item.  Any methods passed into the constructor will override
		//		the ones defined here.

		// store: dojo/data/api/Read
		//		Underlying store
		store: null,

		// childrenAttrs: String[]
		//		One or more attribute names (attributes in the dojo.data item) that specify that item's children
		childrenAttrs: ["children"],

		// newItemIdAttr: String
		//		Name of attribute in the Object passed to newItem() that specifies the id.
		//
		//		If newItemIdAttr is set then it's used when newItem() is called to see if an
		//		item with the same id already exists, and if so just links to the old item
		//		(so that the old item ends up with two parents).
		//
		//		Setting this to null or "" will make every drop create a new item.
		newItemIdAttr: "id",

		// labelAttr: String
		//		If specified, get label for tree node from this attribute, rather
		//		than by calling store.getLabel()
		labelAttr: "",

		// root: [readonly] dojo/data/Item
		//		Pointer to the root item (read only, not a parameter)
		root: null,

		// query: anything
		//		Specifies datastore query to return the root item for the tree.
		//		Must only return a single item.   Alternately can just pass in pointer
		//		to root item.
		// example:
		//	|	{id:'ROOT'}
		query: null,

		// deferItemLoadingUntilExpand: Boolean
		//		Setting this to true will cause the TreeStoreModel to defer calling loadItem on nodes
		//		until they are expanded. This allows for lazying loading where only one
		//		loadItem (and generally one network call, consequently) per expansion
		//		(rather than one for each child).
		//		This relies on partial loading of the children items; each children item of a
		//		fully loaded item should contain the label and info about having children.
		deferItemLoadingUntilExpand: false,

		constructor: function(/* Object */ args){
			// summary:
			//		Passed the arguments listed above (store, etc)
			// tags:
			//		private

			lang.mixin(this, args);

			this.connects = [];

			var store = this.store;
			if(!store.getFeatures()['dojo.data.api.Identity']){
				throw new Error("dijit.tree.TreeStoreModel: store must support dojo.data.Identity");
			}

			// if the store supports Notification, subscribe to the notification events
			if(store.getFeatures()['dojo.data.api.Notification']){
				this.connects = this.connects.concat([
					aspect.after(store, "onNew", lang.hitch(this, "onNewItem"), true),
					aspect.after(store, "onDelete", lang.hitch(this, "onDeleteItem"), true),
					aspect.after(store, "onSet", lang.hitch(this, "onSetItem"), true)
				]);
			}
		},

		destroy: function(){
			var h;
			while(h = this.connects.pop()){ h.remove(); }
			// TODO: should cancel any in-progress processing of getRoot(), getChildren()
		},

		// =======================================================================
		// Methods for traversing hierarchy

		getRoot: function(onItem, onError){
			// summary:
			//		Calls onItem with the root item for the tree, possibly a fabricated item.
			//		Calls onError on error.
			if(this.root){
				onItem(this.root);
			}else{
				this.store.fetch({
					query: this.query,
					onComplete: lang.hitch(this, function(items){
						if(items.length != 1){
							throw new Error("dijit.tree.TreeStoreModel: root query returned " + items.length +
								" items, but must return exactly one");
						}
						this.root = items[0];
						onItem(this.root);
					}),
					onError: onError
				});
			}
		},

		mayHaveChildren: function(/*dojo/data/Item*/ item){
			// summary:
			//		Tells if an item has or may have children.  Implementing logic here
			//		avoids showing +/- expando icon for nodes that we know don't have children.
			//		(For efficiency reasons we may not want to check if an element actually
			//		has children until user clicks the expando node)
			return array.some(this.childrenAttrs, function(attr){
				return this.store.hasAttribute(item, attr);
			}, this);
		},

		getChildren: function(/*dojo/data/Item*/ parentItem, /*function(items)*/ onComplete, /*function*/ onError){
			// summary:
			//		Calls onComplete() with array of child items of given parent item, all loaded.

			var store = this.store;
			if(!store.isItemLoaded(parentItem)){
				// The parent is not loaded yet, we must be in deferItemLoadingUntilExpand
				// mode, so we will load it and just return the children (without loading each
				// child item)
				var getChildren = lang.hitch(this, arguments.callee);
				store.loadItem({
					item: parentItem,
					onItem: function(parentItem){
						getChildren(parentItem, onComplete, onError);
					},
					onError: onError
				});
				return;
			}
			// get children of specified item
			var childItems = [];
			for(var i=0; i<this.childrenAttrs.length; i++){
				var vals = store.getValues(parentItem, this.childrenAttrs[i]);
				childItems = childItems.concat(vals);
			}

			// count how many items need to be loaded
			var _waitCount = 0;
			if(!this.deferItemLoadingUntilExpand){
				array.forEach(childItems, function(item){ if(!store.isItemLoaded(item)){ _waitCount++; } });
			}

			if(_waitCount == 0){
				// all items are already loaded (or we aren't loading them).  proceed...
				onComplete(childItems);
			}else{
				// still waiting for some or all of the items to load
				array.forEach(childItems, function(item, idx){
					if(!store.isItemLoaded(item)){
						store.loadItem({
							item: item,
							onItem: function(item){
								childItems[idx] = item;
								if(--_waitCount == 0){
									// all nodes have been loaded, send them to the tree
									onComplete(childItems);
								}
							},
							onError: onError
						});
					}
				});
			}
		},

		// =======================================================================
		// Inspecting items

		isItem: function(/* anything */ something){
			return this.store.isItem(something);	// Boolean
		},

		fetchItemByIdentity: function(/* object */ keywordArgs){
			this.store.fetchItemByIdentity(keywordArgs);
		},

		getIdentity: function(/* item */ item){
			return this.store.getIdentity(item);	// Object
		},

		getLabel: function(/*dojo/data/Item*/ item){
			// summary:
			//		Get the label for an item
			if(this.labelAttr){
				return this.store.getValue(item,this.labelAttr);	// String
			}else{
				return this.store.getLabel(item);	// String
			}
		},

		// =======================================================================
		// Write interface

		newItem: function(/* dijit/tree/dndSource.__Item */ args, /*dojo/data/api/Item*/ parent, /*int?*/ insertIndex){
			// summary:
			//		Creates a new item.   See `dojo/data/api/Write` for details on args.
			//		Used in drag & drop when item from external source dropped onto tree.
			// description:
			//		Developers will need to override this method if new items get added
			//		to parents with multiple children attributes, in order to define which
			//		children attribute points to the new item.

			var pInfo = {parent: parent, attribute: this.childrenAttrs[0]}, LnewItem;

			if(this.newItemIdAttr && args[this.newItemIdAttr]){
				// Maybe there's already a corresponding item in the store; if so, reuse it.
				this.fetchItemByIdentity({identity: args[this.newItemIdAttr], scope: this, onItem: function(item){
					if(item){
						// There's already a matching item in store, use it
						this.pasteItem(item, null, parent, true, insertIndex);
					}else{
						// Create new item in the tree, based on the drag source.
						LnewItem=this.store.newItem(args, pInfo);
						if(LnewItem && (insertIndex!=undefined)){
							// Move new item to desired position
							this.pasteItem(LnewItem, parent, parent, false, insertIndex);
						}
					}
				}});
			}else{
				// [as far as we know] there is no id so we must assume this is a new item
				LnewItem=this.store.newItem(args, pInfo);
				if(LnewItem && (insertIndex!=undefined)){
					// Move new item to desired position
					this.pasteItem(LnewItem, parent, parent, false, insertIndex);
				}
			}
		},

		pasteItem: function(/*Item*/ childItem, /*Item*/ oldParentItem, /*Item*/ newParentItem, /*Boolean*/ bCopy, /*int?*/ insertIndex){
			// summary:
			//		Move or copy an item from one parent item to another.
			//		Used in drag & drop
			var store = this.store,
				parentAttr = this.childrenAttrs[0];	// name of "children" attr in parent item

			// remove child from source item, and record the attribute that child occurred in
			if(oldParentItem){
				array.forEach(this.childrenAttrs, function(attr){
					if(store.containsValue(oldParentItem, attr, childItem)){
						if(!bCopy){
							var values = array.filter(store.getValues(oldParentItem, attr), function(x){
								return x != childItem;
							});
							store.setValues(oldParentItem, attr, values);
						}
						parentAttr = attr;
					}
				});
			}

			// modify target item's children attribute to include this item
			if(newParentItem){
				if(typeof insertIndex == "number"){
					// call slice() to avoid modifying the original array, confusing the data store
					var childItems = store.getValues(newParentItem, parentAttr).slice();
					childItems.splice(insertIndex, 0, childItem);
					store.setValues(newParentItem, parentAttr, childItems);
				}else{
					store.setValues(newParentItem, parentAttr,
						store.getValues(newParentItem, parentAttr).concat(childItem));
				}
			}
		},

		// =======================================================================
		// Callbacks

		onChange: function(/*dojo/data/Item*/ /*===== item =====*/){
			// summary:
			//		Callback whenever an item has changed, so that Tree
			//		can update the label, icon, etc.   Note that changes
			//		to an item's children or parent(s) will trigger an
			//		onChildrenChange() so you can ignore those changes here.
			// tags:
			//		callback
		},

		onChildrenChange: function(/*===== parent, newChildrenList =====*/){
			// summary:
			//		Callback to do notifications about new, updated, or deleted items.
			// parent: dojo/data/Item
			// newChildrenList: dojo/data/Item[]
			// tags:
			//		callback
		},

		onDelete: function(/*dojo/data/Item*/ /*===== item =====*/){
			// summary:
			//		Callback when an item has been deleted.
			// description:
			//		Note that there will also be an onChildrenChange() callback for the parent
			//		of this item.
			// tags:
			//		callback
		},

		// =======================================================================
		// Events from data store

		onNewItem: function(/* dojo/data/Item */ item, /* Object */ parentInfo){
			// summary:
			//		Handler for when new items appear in the store, either from a drop operation
			//		or some other way.   Updates the tree view (if necessary).
			// description:
			//		If the new item is a child of an existing item,
			//		calls onChildrenChange() with the new list of children
			//		for that existing item.
			//
			// tags:
			//		extension

			// We only care about the new item if it has a parent that corresponds to a TreeNode
			// we are currently displaying
			if(!parentInfo){
				return;
			}

			// Call onChildrenChange() on parent (ie, existing) item with new list of children
			// In the common case, the new list of children is simply parentInfo.newValue or
			// [ parentInfo.newValue ], although if items in the store has multiple
			// child attributes (see `childrenAttr`), then it's a superset of parentInfo.newValue,
			// so call getChildren() to be sure to get right answer.
			this.getChildren(parentInfo.item, lang.hitch(this, function(children){
				this.onChildrenChange(parentInfo.item, children);
			}));
		},

		onDeleteItem: function(/*Object*/ item){
			// summary:
			//		Handler for delete notifications from underlying store
			this.onDelete(item);
		},

		onSetItem: function(item, attribute /*===== , oldValue, newValue =====*/){
			// summary:
			//		Updates the tree view according to changes in the data store.
			// description:
			//		Handles updates to an item's children by calling onChildrenChange(), and
			//		other updates to an item by calling onChange().
			//
			//		See `onNewItem` for more details on handling updates to an item's children.
			// item: Item
			// attribute: attribute-name-string
			// oldValue: Object|Array
			// newValue: Object|Array
			// tags:
			//		extension

			if(array.indexOf(this.childrenAttrs, attribute) != -1){
				// item's children list changed
				this.getChildren(item, lang.hitch(this, function(children){
					// See comments in onNewItem() about calling getChildren()
					this.onChildrenChange(item, children);
				}));
			}else{
				// item's label/icon/etc. changed.
				this.onChange(item);
			}
		}
	});
});

},
'dojo/dnd/Selector':function(){
define("dojo/dnd/Selector", [
	"../_base/array", "../_base/declare", "../_base/event", "../_base/kernel", "../_base/lang",
	"../dom", "../dom-construct", "../mouse", "../_base/NodeList", "../on", "../touch", "./common", "./Container"
], function(array, declare, event, kernel, lang, dom, domConstruct, mouse, NodeList, on, touch, dnd, Container){

// module:
//		dojo/dnd/Selector

/*
	Container item states:
		""			- an item is not selected
		"Selected"	- an item is selected
		"Anchor"	- an item is selected, and is an anchor for a "shift" selection
*/

/*=====
var __SelectorArgs = declare([Container.__ContainerArgs], {
	// singular: Boolean
	//		allows selection of only one element, if true
	singular: false,

	// autoSync: Boolean
	//		autosynchronizes the source with its list of DnD nodes,
	autoSync: false
});
=====*/

var Selector = declare("dojo.dnd.Selector", Container, {
	// summary:
	//		a Selector object, which knows how to select its children

	/*=====
	// selection: Set<String>
	//		The set of id's that are currently selected, such that this.selection[id] == 1
	//		if the node w/that id is selected.  Can iterate over selected node's id's like:
	//	|		for(var id in this.selection)
	selection: {},
	=====*/

	constructor: function(node, params){
		// summary:
		//		constructor of the Selector
		// node: Node||String
		//		node or node's id to build the selector on
		// params: __SelectorArgs?
		//		a dictionary of parameters
		if(!params){ params = {}; }
		this.singular = params.singular;
		this.autoSync = params.autoSync;
		// class-specific variables
		this.selection = {};
		this.anchor = null;
		this.simpleSelection = false;
		// set up events
		this.events.push(
			on(this.node, touch.press, lang.hitch(this, "onMouseDown")),
			on(this.node, touch.release, lang.hitch(this, "onMouseUp"))
		);
	},

	// object attributes (for markup)
	singular: false,	// is singular property

	// methods
	getSelectedNodes: function(){
		// summary:
		//		returns a list (an array) of selected nodes
		var t = new NodeList();
		var e = dnd._empty;
		for(var i in this.selection){
			if(i in e){ continue; }
			t.push(dom.byId(i));
		}
		return t;	// NodeList
	},
	selectNone: function(){
		// summary:
		//		unselects all items
		return this._removeSelection()._removeAnchor();	// self
	},
	selectAll: function(){
		// summary:
		//		selects all items
		this.forInItems(function(data, id){
			this._addItemClass(dom.byId(id), "Selected");
			this.selection[id] = 1;
		}, this);
		return this._removeAnchor();	// self
	},
	deleteSelectedNodes: function(){
		// summary:
		//		deletes all selected items
		var e = dnd._empty;
		for(var i in this.selection){
			if(i in e){ continue; }
			var n = dom.byId(i);
			this.delItem(i);
			domConstruct.destroy(n);
		}
		this.anchor = null;
		this.selection = {};
		return this;	// self
	},
	forInSelectedItems: function(/*Function*/ f, /*Object?*/ o){
		// summary:
		//		iterates over selected items;
		//		see `dojo/dnd/Container.forInItems()` for details
		o = o || kernel.global;
		var s = this.selection, e = dnd._empty;
		for(var i in s){
			if(i in e){ continue; }
			f.call(o, this.getItem(i), i, this);
		}
	},
	sync: function(){
		// summary:
		//		sync up the node list with the data map

		Selector.superclass.sync.call(this);

		// fix the anchor
		if(this.anchor){
			if(!this.getItem(this.anchor.id)){
				this.anchor = null;
			}
		}

		// fix the selection
		var t = [], e = dnd._empty;
		for(var i in this.selection){
			if(i in e){ continue; }
			if(!this.getItem(i)){
				t.push(i);
			}
		}
		array.forEach(t, function(i){
			delete this.selection[i];
		}, this);

		return this;	// self
	},
	insertNodes: function(addSelected, data, before, anchor){
		// summary:
		//		inserts new data items (see `dojo/dnd/Container.insertNodes()` method for details)
		// addSelected: Boolean
		//		all new nodes will be added to selected items, if true, no selection change otherwise
		// data: Array
		//		a list of data items, which should be processed by the creator function
		// before: Boolean
		//		insert before the anchor, if true, and after the anchor otherwise
		// anchor: Node
		//		the anchor node to be used as a point of insertion
		var oldCreator = this._normalizedCreator;
		this._normalizedCreator = function(item, hint){
			var t = oldCreator.call(this, item, hint);
			if(addSelected){
				if(!this.anchor){
					this.anchor = t.node;
					this._removeItemClass(t.node, "Selected");
					this._addItemClass(this.anchor, "Anchor");
				}else if(this.anchor != t.node){
					this._removeItemClass(t.node, "Anchor");
					this._addItemClass(t.node, "Selected");
				}
				this.selection[t.node.id] = 1;
			}else{
				this._removeItemClass(t.node, "Selected");
				this._removeItemClass(t.node, "Anchor");
			}
			return t;
		};
		Selector.superclass.insertNodes.call(this, data, before, anchor);
		this._normalizedCreator = oldCreator;
		return this;	// self
	},
	destroy: function(){
		// summary:
		//		prepares the object to be garbage-collected
		Selector.superclass.destroy.call(this);
		this.selection = this.anchor = null;
	},

	// mouse events
	onMouseDown: function(e){
		// summary:
		//		event processor for onmousedown
		// e: Event
		//		mouse event
		if(this.autoSync){ this.sync(); }
		if(!this.current){ return; }
		if(!this.singular && !dnd.getCopyKeyState(e) && !e.shiftKey && (this.current.id in this.selection)){
			this.simpleSelection = true;
			if(mouse.isLeft(e)){
				// accept the left button and stop the event
				// for IE we don't stop event when multiple buttons are pressed
				event.stop(e);
			}
			return;
		}
		if(!this.singular && e.shiftKey){
			if(!dnd.getCopyKeyState(e)){
				this._removeSelection();
			}
			var c = this.getAllNodes();
			if(c.length){
				if(!this.anchor){
					this.anchor = c[0];
					this._addItemClass(this.anchor, "Anchor");
				}
				this.selection[this.anchor.id] = 1;
				if(this.anchor != this.current){
					var i = 0, node;
					for(; i < c.length; ++i){
						node = c[i];
						if(node == this.anchor || node == this.current){ break; }
					}
					for(++i; i < c.length; ++i){
						node = c[i];
						if(node == this.anchor || node == this.current){ break; }
						this._addItemClass(node, "Selected");
						this.selection[node.id] = 1;
					}
					this._addItemClass(this.current, "Selected");
					this.selection[this.current.id] = 1;
				}
			}
		}else{
			if(this.singular){
				if(this.anchor == this.current){
					if(dnd.getCopyKeyState(e)){
						this.selectNone();
					}
				}else{
					this.selectNone();
					this.anchor = this.current;
					this._addItemClass(this.anchor, "Anchor");
					this.selection[this.current.id] = 1;
				}
			}else{
				if(dnd.getCopyKeyState(e)){
					if(this.anchor == this.current){
						delete this.selection[this.anchor.id];
						this._removeAnchor();
					}else{
						if(this.current.id in this.selection){
							this._removeItemClass(this.current, "Selected");
							delete this.selection[this.current.id];
						}else{
							if(this.anchor){
								this._removeItemClass(this.anchor, "Anchor");
								this._addItemClass(this.anchor, "Selected");
							}
							this.anchor = this.current;
							this._addItemClass(this.current, "Anchor");
							this.selection[this.current.id] = 1;
						}
					}
				}else{
					if(!(this.current.id in this.selection)){
						this.selectNone();
						this.anchor = this.current;
						this._addItemClass(this.current, "Anchor");
						this.selection[this.current.id] = 1;
					}
				}
			}
		}
		event.stop(e);
	},
	onMouseUp: function(/*===== e =====*/){
		// summary:
		//		event processor for onmouseup
		// e: Event
		//		mouse event
		if(!this.simpleSelection){ return; }
		this.simpleSelection = false;
		this.selectNone();
		if(this.current){
			this.anchor = this.current;
			this._addItemClass(this.anchor, "Anchor");
			this.selection[this.current.id] = 1;
		}
	},
	onMouseMove: function(/*===== e =====*/){
		// summary:
		//		event processor for onmousemove
		// e: Event
		//		mouse event
		this.simpleSelection = false;
	},

	// utilities
	onOverEvent: function(){
		// summary:
		//		this function is called once, when mouse is over our container
		this.onmousemoveEvent = on(this.node, touch.move, lang.hitch(this, "onMouseMove"));
	},
	onOutEvent: function(){
		// summary:
		//		this function is called once, when mouse is out of our container
		if(this.onmousemoveEvent){
			this.onmousemoveEvent.remove();
			delete this.onmousemoveEvent;
		}
	},
	_removeSelection: function(){
		// summary:
		//		unselects all items
		var e = dnd._empty;
		for(var i in this.selection){
			if(i in e){ continue; }
			var node = dom.byId(i);
			if(node){ this._removeItemClass(node, "Selected"); }
		}
		this.selection = {};
		return this;	// self
	},
	_removeAnchor: function(){
		if(this.anchor){
			this._removeItemClass(this.anchor, "Anchor");
			this.anchor = null;
		}
		return this;	// self
	}
});

return Selector;

});

},
'cbtree/models/TreeStoreModel':function(){
//
// Copyright (c) 2010-2012, Peter Jekel
// All rights reserved.
//
//	The Checkbox Tree (cbtree), also known as the 'Dijit Tree with Multi State Checkboxes'
//	is released under to following three licenses:
//
//	1 - BSD 2-Clause							 (http://thejekels.com/cbtree/LICENSE)
//	2 - The "New" BSD License			 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L13)
//	3 - The Academic Free License	 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L43)
//
//	In case of doubt, the BSD 2-Clause license takes precedence.
//
define("cbtree/models/TreeStoreModel", [
	"dojo/_base/array",	  // array.filter array.forEach array.indexOf array.some
	"dojo/_base/declare", // declare
	"dojo/_base/lang",		// lang.hitch
	"dojo/aspect",				// aspect.after
	"dojo/has",						// has.add
	"dojo/json",					// json.stringify
	"dojo/Stateful",			// get() and set()
	"./ItemWriteStoreEX"	// ItemFileWriteStore extensions.	
], function(array, declare, lang, aspect, has, json, Stateful, ItemWriteStoreEX){

		// module:
		//		cbtree/models/TreeStoreModel
		// summary:
		//		Implements cbtree.models.model connecting to a dojo.data store with a
		//		single root item.

	return declare([Stateful], {

		//==============================
		// Parameters to constructor

		// checkedAll: Boolean
		//		If true, every store item will receive a 'checked' state property regard-
		//		less if the 'checked' attribute is specified in the dojo.data.store
		checkedAll: true,

		// checkedState: Boolean
		//		The default state applied to every store item unless otherwise specified
		//		in the dojo.data.store (see also: checkedAttr)
		checkedState: false,

		// checkedRoot: Boolean
		//		If true, the root node will receive a checked state eventhough it's not
		//		a true entry in the store. This attribute is independent of the showRoot
		//		attribute of the tree itself. If the tree attribute 'showRoot' is set to
		//		false the checked state for the root will not show either.	
		checkedRoot: false,

		// checkedStrict: Boolean
		//		If true, a strict parent-child relation is maintained. For example, 
		//		if all children are checked the parent will automatically recieve the
		//		same checked state or if any of the children are unchecked the parent
		//		will, depending if multi state is enabled, recieve either a mixed or
		//		unchecked state. 
		//		If set to true it overwrites deferItemLoadingUntilExpand.
		checkedStrict: true,

		// checkedAttr: String
		//		The attribute name (property of the store item) that holds the 'checked'
		//		state. On load it specifies the store items initial checked state.	 For
		//		example: { name:'Egypt', type:'country', checked: true } If a store item
		//		has no 'checked' attribute specified it will depend on the model property
		//		'checkedAll' if one will be created automatically and if so, its initial
		//		state will be set as specified by 'checkedState'. 
		checkedAttr: "checked",
		
		// childrenAttrs: String[]
		//		One or more attribute names (attributes in the dojo.data item) that specify
		//		that item's children
		childrenAttrs: ["children"],

		// deferItemLoadingUntilExpand: Boolean
		//		Setting this to true will cause the TreeStoreModel to defer calling loadItem
		//		on nodes until they are expanded. This allows for lazying loading where only
		//		one loadItem (and generally one network call, consequently) per expansion
		//		(rather than one for each child).
		//		This relies on partial loading of the children items; each children item of a
		//		fully loaded item should contain the label and info about having children.
		deferItemLoadingUntilExpand: false,

		// enabledAttr: String (1.8)
		//		The attribute name (property of the store item) that holds the 'enabled'
		//		state of the checkbox or alternative widget. 
		//		Note: Eventhough it is referred to as the 'enabled' state the tree will 
		//		only use this property to enable/disable the 'ReadOnly' property of a
		//		checkbox. This because disabling a widget may exclude it from HTTP POST
		//		operations.
		enabledAttr:"",
		
		// excludeChildrenAttrs: String[]
		//		If multiple childrenAttrs have been specified excludeChildrenAttrs determines
		//		which of those childrenAttrs are excluded from: a) getting a checked state.
		//		b) compiling the composite state of a parent item.
		excludeChildrenAttrs: null,
		
		// iconAttr: String
		//		If specified, get the icon from an item using this attribute name.
		iconAttr: "",

		// labelAttr: String
		//		If specified, get label for tree node from this attribute, rather
		//		than by calling store.getLabel()
		labelAttr: "",

		// multiState: Boolean
		//		Determines if the checked state needs to be maintained as multi state or
		//		or as a dual state. ({"mixed",true,false} vs {true,false}).
		multiState: true,

		// newItemIdAttr: String
		//		Name of attribute in the Object passed to newItem() that specifies the id.
		//
		//		If newItemIdAttr is set then it's used when newItem() is called to see if an
		//		item with the same id already exists, and if so just links to the old item
		//		(so that the old item ends up with two parents).
		//
		//		Setting this to null or "" will make every drop create a new item.
		newItemIdAttr: "id",

		// normalize: Boolean
		//		When true, the checked state of any non branch checkbox is normalized, that
		//		is, true or false. When normalization is enabled checkboxes associated with
		//		tree leafs can never have a mixed state.
		normalize: true,
		
		// query: String
		//		Specifies the set of children of the root item.
		// example:
		//		{type:'continent'}
		query: null,
		
		// store: dojo.data.Store
		//		Underlying store
		store: null,

		// End Parameters to constructor
		//==============================
		
		moduleName: "cbTree/models/TreeStoreModel",

		// hasFakeRoot: Boolean
		//		Indicates if the model has a fabricated root item. (this is not a constructor 
		//		parameter).	Typically set by models like the ForestStoreModel.
		hasFakeRoot: false,

		 // root: [readonly] dojo.data.item
		//		Pointer to the root item (read only, not a parameter)
		root: null,

		// _checkedChildrenAttrs: string[]
		//		The list of childrenAttrs to be included in any of the checked state operations.
		//		Only store items which are a member of any of the _checkedChildrenAttrs will get
		//		a checked state and are included in compiling the composite parent state.
		//		_checkedChildrenAttrs is defined as (childrenAttrs - excludeChildrenAttrs)
		_checkedChildrenAttrs: null,

		// _queryAttrs: String[]
		//		A list of attribute names included in the query. The list is used to determine
		//		if a re-query of the store is required after a property of a store item has
		//		changed value.
		_queryAttrs: [],

		// _validateStore: Boolean
		_validateStore: true,

		// _validating: [private] Number
		//		If not equal to zero it indicates store validation is on going.
		_validating: 0,

		constructor: function(/* Object */ args){
			// summary:
			//		Passed the arguments listed above (store, etc)
			// tags:
			//		private

			declare.safeMixin(this, args);

			this.connects = [];

			var store = this.store;

			if(!store.getFeatures()['dojo.data.api.Identity']){
				throw new Error(this.moduleName+"constructor(): store must support dojo.data.Identity");
			}

			has.add("tree-model-getChecked", 1);
			if (!store.getFeatures()['dojo.data.api.Write']){
				console.warn(this.moduleName+"::constructor(): store is not write enabled.");
				this._writeEnabled = false;
			} else {
				has.add("tree-model-setChecked", 1);
				this._writeEnabled = true;
			}

			// if the store supports Notification, subscribe to the notification events
			if(store.getFeatures()['dojo.data.api.Notification']){
				this.connects = this.connects.concat([
					aspect.after(store, "onLoad", lang.hitch(this, "onStoreLoaded"), true),
					aspect.after(store, "onNew", lang.hitch(this, "onNewItem"), true),
					aspect.after(store, "onDelete", lang.hitch(this, "onDeleteItem"), true),
					aspect.after(store, "onSet", lang.hitch(this, "onSetItem"), true),
					aspect.after(store, "onRoot", lang.hitch(this, "onRootChange"), true)
				]);
			}
			this._checkedChildrenAttrs = this._diffArrays( this.childrenAttrs, this.excludeChildrenAttrs );
			// Compose a list of attribute names included in the store query.
			if (this.query) {
				var attr;
				for (attr in this.query) {
					this._queryAttrs.push(attr);
				}
			}
		},

		destroy: function(){
			var h;
			while(h = this.connects.pop()){ h.remove(); }
			// TODO: should cancel any in-progress processing of getRoot(), getChildren()

			this.store = null;
		},

		// =======================================================================
		// Model getters and setters (See dojo/Stateful)

		_checkedStrictSetter: function (value){
			// summary:
			//		Hook for the set("checkedStrict",value) calls. Note: A full store
			//		re-evaluation is only kicked off when the current value is false 
			//		and the new value is true.
			// value:
			//		New value applied to 'checkedStrict'. Any value is converted to a boolean.
			// tag:
			//		private

			value = value ? true : false;
			if (this.checkedStrict !== value) {
				this.checkedStrict = value;
				if (this.checkedStrict) {
					this.getRoot( lang.hitch(this, function (rootItem) {
							this.getChildren(rootItem, lang.hitch(this, function(children) {
									this._validateChildren(rootItem, children);
								}))
						}))
				}
			}
			return this.checkedStrict;
		},

		_enabledAttrSetter: function (/*String*/ value) {
			// summary:
			//		Set the enabledAttr property. This method is the hook for set("enabledAttr", ...)
			//		The enabledAttr value can only be set once during the model instantiation.
			// value:
			//		New enabledAttr value.
			// tags:
			//		private

			if (lang.isString(value)) {
				if (this.enabledAttr !== value) {
					throw new Error(this.moduleName+"::set(): enabledAttr property is read-only.");
				}
			} else {
				throw new Error(this.moduleName+"::set(): enabledAttr value must be a string");
			}
			return this.enabledAttr;
		},
		
		_labelAttrGetter: function() {
			// summary:
			//		Return the label attribute associated with the store, if available.
			//		This method is the hook for get("labelAttr");
			// tag:
			//		private

			return this.getLabelAttr();
		},

		_labelAttrSetter: function (/*String*/ value) {
			// summary:
			//		Set the labelAttr property. This method is the hook for set("labelAttr", ...)
			// value:
			//		New labelAttr value.
			// tags:
			//		private

			return this.setLabelAttr(value);
		},

		_querySetter: function (value) {
			// summary:
			//		Hook for the set("query",value) calls.
			// value:
			//		New query object.
			// tag:
			//		private

			if (lang.isObject(value)){
				if (this.query !== value){
					this.query = value;
					this._requeryTop();
				}
				return this.query;
			} else {
				throw new Error(this.moduleName+"::set(): query argument must be of type object");
			}
		},

		// =======================================================================
		// Methods for traversing hierarchy

		getChildren: function(/*dojo.data.item*/ parentItem, /*Function*/ onComplete, /*Function*/ onError, 
													 /*String[]?*/ childrenLists ){
			// summary:
			//		 Calls onComplete() with array of child items of given parent item,
			//		all loaded.
			// parentItem:
			//		dojo.data.item.
			// onComplete:
			//		Callback function, called on completion with an array of child items
			//		as the argument.
			// onError:
			//		Callback function, called in case an error occurred.
			// childrenLists:
			//		Specifies the childrens list(s) from which the children are retrieved.
			//		If ommitted, childrenAttrs is used instead returning all children.
			// tags:
			//		public
			
			var store = this.store;
			var scope = this;
			
			if(!store.isItemLoaded(parentItem)){
				// The parent is not loaded yet, we must be in deferItemLoadingUntilExpand
				// mode, so we will load it and just return the children (without loading each
				// child item)
				var getChildren = lang.hitch(this, arguments.callee);
				store.loadItem( scope._mixinFetch( 
					{	
						item: parentItem,
						onItem: function(parentItem) {
											getChildren(parentItem, onComplete, onError);
										},
						onError: onError
					})
				);
				return;
			}
			// get children of specified item
			var childItems = [], vals, i;
			if (!childrenLists) {
				for(i=0; i<this.childrenAttrs.length; i++){
					vals = store.getValues(parentItem, this.childrenAttrs[i]);
					childItems = childItems.concat(vals);
				}
			} 
			else // Get children from specfied list(s) only.
			{
				var lists = lang.isArray(childrenLists) ? childrenLists : [childrenLists];
				for(i=0; i<lists.length; i++){
					vals = store.getValues(parentItem, lists[i]);
					childItems = childItems.concat(vals);
				}
			}
			// count how many items need to be loaded
			var _waitCount = 0;
			if(!this.deferItemLoadingUntilExpand){
				array.forEach(childItems, function(item){ if(!store.isItemLoaded(item)){ _waitCount++; } });
			}

			if(_waitCount == 0){
				// all items are already loaded (or we aren't loading them).	proceed...
				onComplete(childItems);
			}else{
				// still waiting for some or all of the items to load
				array.forEach(childItems, function(item, idx){
					if (!store.isItemLoaded(item)) {
						store.loadItem( scope._mixinFetch( 
							{
								item: item,
								onItem: function(item){
									childItems[idx] = item;
									if(--_waitCount == 0){
										// all nodes have been loaded, send them to the tree
										onComplete(childItems);
									}
								},
								onError: onError
							} )
						);
					}
				});
			}
		},

		getParents: function (/*dojo.data.item*/ storeItem) {
			// summary:
			//		Get the parent(s) of a store item.	
			// storeItem:
			//		The dojo.data.item whose parent(s) will be returned.
			// tags:
			//		private
			if (storeItem) {
				return this.store.getParents(storeItem);
			}
		},

		getRoot: function(/*Function*/ onItem, /*Function*/ onError){
			// summary:
			//		Calls onItem with the root item for the tree, possibly a fabricated item.
			//		Calls onError on error.
			// onItem:
			//		Function called with the root item for the tree.
			// onError:
			//		Function called in case an error occurred.
			
			if(this.root){
				onItem(this.root);
			}else{
				this.store.fetch( this._mixinFetch( 
					{
						query: this.query,
						onComplete: lang.hitch(this, function(items){
							if(items.length != 1){
								throw new Error(this.moduleName + ": query " + json.stringify(this.query) + " returned " + items.length +
									 " items, but must return exactly one item");
							}
							this.root = items[0];
							onItem(this.root);
						}),
						onError: onError
					})
				);
			}
		},

		mayHaveChildren: function(/*dojo.data.item*/ item){
			// summary:
			//		Tells if an item has or may have children.	Implementing logic here
			//		avoids showing +/- expando icon for nodes that we know don't have children.
			//		(For efficiency reasons we may not want to check if an element actually
			//		has children until user clicks the expando node)
			// item:
			//		dojo.data.item.
			// tags:
			//		public

			return array.some(this.childrenAttrs, function(attr){
				return this.store.hasAttribute(item, attr);
			}, this);
		},

		// =======================================================================
		// Private Checked state handling
		
		_getCompositeState: function (/*dojo.data.item[]*/ children) {
			// summary:
			//		Compile the composite state based on the checked state of a group
			//		of children.	If any child has a mixed state, the composite state
			//		will always be mixed, on the other hand, if none of the children
			//		has a checked state the composite state will be undefined.
			// children: 
			//		Array of dojo.data items
			// tags:
			//		private
			
			var hasChecked	 = false,
					hasUnchecked = false,
					isMixed			= false,
					newState,
					state;

			array.some(children, function (child) {
				state = this.getChecked(child);
				isMixed |= (state == "mixed");
				switch(state) {	// ignore 'undefined' state
					case true:
						hasChecked = true;
						break;
					case false: 
						hasUnchecked = true;
						break;
				}
				return isMixed;
			}, this);
			// At least one checked/unchecked required to change parent state.
			if (isMixed || hasChecked || hasUnchecked) {
				isMixed |= !(hasChecked ^ hasUnchecked);
				newState = (isMixed ? "mixed" : hasChecked ? true: false);
			}
			return newState;
		},
		
		_normalizeState: function (/*dojo.data.item*/ storeItem, /*Boolean|String*/ state) {
			// summary:
			//		Normalize the checked state value so we don't store an invalid state
			//		for a store item.
			//	storeItem:
			//		The store item whose checked state is normalized.
			//	state:
			//		The checked state: 'mixed', true or false.
			// tags:
			//		private
			
			if (typeof state == "boolean") {
				return state;
			}
			if (this.multiState && state == "mixed") {
				if (this.normalize && !this.mayHaveChildren(storeItem)){
						return true;
				}
				return state;
			}
			return state ? true : false;
		},

		_setChecked: function (/*dojo.data.item*/ storeItem, /*Boolean|String*/ newState) {
			// summary:
			//		Set/update the checked state on the dojo.data store. Returns true if
			//		the checked state changed otherwise false.
			// description:
			//		Set/update the checked state on the dojo.data.store.	Retreive the
			//		current checked state	and validate if an update is required, this 
			//		will keep store updates to a minimum. If the current checked state
			//		is undefined (ie: no checked attribute specified in the store) the 
			//		'checkedAll' attribute is tested to see if a checked state needs to
			//		be created.	In case of the root node the 'checkedRoot' attribute
			//		is checked.
			//
			//		NOTE: The store.setValue() method will add the attribute for the
			//					item if none exists.	 
			//
			//	storeItem:
			//		The item in the dojo.data.store whose checked state is updated.
			//	newState:
			//		The new checked state: 'mixed', true or false.
			//	tag:
			//		private

			var forceUpdate = false,
					normState;

			normState		= this._normalizeState(storeItem, newState);
			forceUpdate = (normState != newState);
			if (this.store.isItem(storeItem)) {
				var currState = this.store.getValue(storeItem, this.checkedAttr);
				if ((currState !== undefined || this.checkedAll) && (currState != normState || forceUpdate)) {
					this.store.setValue(storeItem, this.checkedAttr, normState);
					return true;
				}
			} 
			else // Test for fabricated root.
			{
				if (storeItem === this.root && this.hasFakeRoot) {
					if (this.checkedRoot && ((this.root[this.checkedAttr] != normState) || forceUpdate)) {
						this.root[this.checkedAttr] = normState;
						this.onChange(storeItem, this.checkedAttr, normState);
						return true;
					}
				} else {
					throw new TypeError(this.moduleName+"::_setChecked(): invalid item specified.");
				}
			}
			return false;
		},
		
		_updateCheckedChild: function (/*dojo.data.item*/ storeItem, /*Boolean*/ newState) {
			//	summary:
			//		Set the parent (the storeItem) and all childrens states to true/false.
			//	description:
			//		If a parent checked state changed, all child and grandchild states are
			//		updated to reflect the change. For example, if the parent state is set
			//		to true, all child and grandchild states will receive that same 'true'
			//		state.
			//
			//	storeItem:
			//		The parent store item whose child/grandchild states require updating.
			//	newState:
			//		The new checked state.
			//	tag:
			//		private

			// Set the (maybe) parent first. The order in which any child checked states
			// are set is important to optimize _updateCheckedParent() performance.
			this._setChecked(storeItem, newState);

			if (this.mayHaveChildren(storeItem)) {
				this.getChildren(storeItem, lang.hitch(this, 
						function (children) {
							array.forEach(children, function (child) {
									this._updateCheckedChild(child, newState);
								}, 
							this 
							);
						}
					), // end hitch()
					this.onError,
					this._checkedChildrenAttrs); // end getChildren()
			}
		},

		_updateCheckedParent: function (/*dojo.data.item*/ storeItem, /*Boolean*/ forceUpdate) {
			//	summary:
			//		Update the parent checked state according to the state of all its
			//		children checked states.
			//	storeItem:
			//		The store item (child) whose parent state requires updating.
			//	forceUpdate:
			//		Force an update of the parent(s) regardless of the current checked
			//		state of the child.
			//	tag:
			//		private
			
			if (!this.checkedStrict || !storeItem) {
				return;
			}
			var parents		= this.getParents(storeItem),
					childState = this.getChecked(storeItem),
					newState;

			array.forEach(parents, function (parentItem) {
				// Test if the storeItem is actually a child in the context of this model.
				// The child may have been added to a different childrens list in another
				// model.
				if( this.isChildOf(parentItem, storeItem)) {
					// Only process a parent update if the current child state differs from
					// its parent otherwise the parent is already up-to-date.
					if ((childState !== this.getChecked(parentItem)) || forceUpdate) {
						this.getChildren(parentItem, lang.hitch(this,
							function (children) {
								newState = this._getCompositeState(children);
								if(newState !== undefined) {
									this._setChecked(parentItem, newState);
								}
							}),
							this.onError,
							this._checkedChildrenAttrs); /* end getChildren() */
					}
				}						
			}, this); /* end forEach() */
		},

		_validateChildren: function ( parent, children, childrenLists) {
			// summary:
			//		Validate/normalize the parent(s) checked state in the dojo.data store.
			// description:
			//		All parent checked states are set to the appropriate state according to
			//		the actual state(s) of their children. This will potentionally overwrite
			//		whatever was specified for the parent in the dojo.data store. This will
			//		garantee the tree is in a consistent state after startup. 
			//	parent:
			//		The parent item of children.
			//	children:
			//		Either the tree root or a list of child children
			//	childrenLists:
			//		Array of list attributes to be included in the validation. See definition
			//		of _checkedChildrenAttrs for details.
			//	tag:
			//		private

			var children,	currState, newState;
			this._validating += 1;

			children	= lang.isArray(children) ? children : [children];
			array.forEach(children, 
				function (child) {
					if (this.mayHaveChildren(child)) {
						this.getChildren( child, lang.hitch(this, function(children) {
								this._validateChildren( child, children, childrenLists);
							}),	
							this.onError, 
							childrenLists);
					} else {
						currState = this.getChecked(child);
						if (currState && typeof currState !== "boolean") {
							child[this.checkedAttr] = [this._normalizeState(child, currState)];
						}
					}
				}, 
				this
			);
			newState	= this._getCompositeState(children);
			currState = this.getChecked(parent);

			if (currState !== undefined && newState !== undefined) {
				this._setChecked(parent, newState);
			}

			// If the validation count drops to zero we're done.
			this._validating -= 1;
			if (!this._validating) {
				this.store.setValidated(true);
				this.onDataValidated();
			}
		},

		// =======================================================================
		// Checked and Enabled state

		getChecked: function (/*dojo.data.item*/ storeItem) {
			// summary:
			//		Get the current checked state from the data store for the specified item.
			// description:
			//		Get the current checked state from the dojo.data store. The checked state
			//		in the store can be: 'mixed', true, false or undefined. Undefined in this
			//		context means no checked identifier (checkedAttr) was found in the store
			//		Depending on the checked attributes as specified above the following will
			//		take place:
			//
			//		a)	If the current checked state is undefined and the checked attribute
			//				'checkedAll' or 'checkedRoot' is true one will be created and the
			//				default state 'checkedState' will be applied.
			//		b)	If the current state is undefined and 'checkedAll' is false the state
			//				undefined remains unchanged and is returned. This will prevent a tree
			//				node from creating a checkbox or other widget.
			//
			// storeItem:
			//		The item in the dojo.data.store whose checked state is returned.
			// tag:
			//		private

			var checked;
			
			if (this.excludeChildrenAttrs) {
				if (this.isMemberOf(storeItem, this.excludeChildrenAttrs)) {
					return;
				}
			}
			if (this.store.isItem(storeItem)) {
				checked = this.store.getValue(storeItem, this.checkedAttr);
				if (checked === undefined)
				{
					if (this.checkedAll) {
						this._setChecked(storeItem, this.checkedState);
						return this.checkedState;
					}
				}
			} 
			else // Test for fabricated root.
			{
				if (storeItem === this.root && this.hasFakeRoot) {
					if (this.checkedRoot) {
						return this.root[this.checkedAttr];
					}
				} else {
					throw new TypeError(this.moduleName+"::getChecked(): invalid item specified.");
				}
			}
			return checked;	// the current checked state (true/false or undefined)
		},

		getEnabled: function (/*item*/ item) {
			// summary:
			//		Returns the current 'enabled' state of an item as a boolean.
			// item:
			//		Store or root item
			// tag:
			//		Public
			var enabled = true;
			
			if (this.enabledAttr) {
				if (this.store.isItem(item)) {			
					enabled = this.store.getValue(item, this.enabledAttr);
				} else {
					if (item === this.root) {
						enabled = item[this.enabledAttr];
					} else {
						throw new TypeError(this.moduleName+"::getEnabled(): invalid item specified.");
					}
				}
			}
			return (enabled === undefined) || Boolean(enabled);
		},

		getItemState: function (/*item*/ item) {
			// summary:
			//		Returns the state of a item, the state is an object with two properies:
			//		'checked' and 'enabled'.
			// item:
			//		The store or root item.
			// tag:
			//		Public
			return { checked: this.getChecked(item), 
								enabled: this.getEnabled(item) };
		},

		setChecked: function (/*dojo.data.item*/ storeItem, /*Boolean*/ newState) {
			// summary:
			//		Update the checked state for the store item and the associated parents
			//		and children, if any.
			// description:
			//		Update the checked state for a single store item and the associated
			//		parent(s) and children, if any. This method is called from the tree if
			//		the user checked/unchecked a checkbox. The parent and child tree nodes
			//		are updated to maintain consistency if 'checkedStrict' is set to true.
			//	storeItem:
			//		The item in the dojo.data.store whose checked state needs updating.
			//	newState:
			//		The new checked state: 'mixed', true or false
			// tags:
			//		private
			
			if (!this.checkedStrict) {
				this._setChecked(storeItem, newState);		// Just update the checked state
			} else {
				this._updateCheckedChild(storeItem, newState); // Update children and parent(s).
			}
		},

		setEnabled: function (/*item*/ item, /*Boolean*/ value) {
			// summary:
			//		Sets the new 'enabled' state of an item.
			// item:
			//		Store or root item
			// tag:
			//		Public
			if (this.enabledAttr) {
				if (this.store.isItem(item)) {			
					return this.store.setValue(item, this.enabledAttr, Boolean(value));
				} else {
					if (item === this.root) {
						return this.root[this.enabledAttr] = Boolean(value);
					} else {
						throw new TypeError(this.moduleName+"::setEnabled(): invalid item specified.");
					}
				}				
			}
		},

		validateData: function () {
			// summary:
			//		Validate/normalize the parent-child checked state relationship. If the
			//		attribute 'checkedStrict' is true this method is called as part of the
			//		post creation of the Tree instance.	First we try a forced synchronous
			//		load of the Json dataObject dramatically improving the startup time.
			//	tag:
			//		private
		
			if (this.checkedStrict) {
				// In case multiple models operate on the same store, the store may have
				// already been validated.
				if (!this.store.isValidated()) {
					// Force a store load.
					this.store.loadStore( {
						onComplete: function (count) {
													if (has("tree-model-setChecked")) {
														if (this._validateStore) {
															this.getRoot( lang.hitch(this, function (rootItem) {
																	this.getChildren(rootItem, lang.hitch(this, function(children) {
																			this._validateChildren(rootItem, children, this._checkedChildrenAttrs);
																		}), this.onError)
																}), this.onError)
														}
													} else {
														console.warn(this.moduleName+"::validateData(): store is not write enabled.");
													}
												}, 
						onError: function (err) {}, 
						scope: this
					});
				} 
				else	// Store already validated.
				{
					if (this.hasFakeRoot) {
						// Make sure the fabricated root gets updated.
						this.getChildren(this.root, lang.hitch(this, function (children){
							this._updateCheckedParent(children[0], true);
						}), this.onError, this._checkedChildrenAttrs);
					}
					this.onDataValidated();
				}
			} 
			else 
			{
				this.store.setValidated(false);
			}
		},

		// =======================================================================
		// Inspecting items

		fetchItemByIdentity: function(/*object*/ keywordArgs){
			// summary:
			//		Fetch a store item by identity
			this.store.fetchItemByIdentity(keywordArgs);
		},

		getIcon: function(/*item*/ item){
			// summary:
			//		Get the icon for item from the store if the iconAttr property of the
			//		model is set.
			// item:
			//		A valid dojo.data.store item.
			
			if (this.iconAttr) {
				return this.store.getValue(item, this.iconAttr);
			}
		},

		getIdentity: function(/*item*/ item){
			return this.store.getIdentity(item);	// Object
		},

		getLabel: function(/*dojo.data.item*/ item){
			// summary:
			//		Get the label for an item
			if(this.labelAttr){
				return this.store.getValue(item,this.labelAttr);	// String
			}else{
				this.setLabelAttr(this.getLabelAttr());
				return this.store.getLabel(item);	// String
			}
		},
	
		isItem: function(/*anything*/ something){
			return this.store.isItem(something);	// Boolean
		},

		isTreeRootChild: function (/*dojo.data.item*/ item) {
			// summary:
			//		Returns true if the item is a tree root child.
			if (this.root) {
				return this.isChildOf(this.root, item);
			}
		},
		
		isChildOf: function (/*dojo.data.item*/ parent,/*dojo.data.item*/ item) {
			// summary:
			//		Returns true if item is a child of parent in the context of this model
			//		otherwise false. 
			//
			//		Note: An item may have been added as a child by another model with
			//					a different set of 'childrenAttrs'. Therefore, item may be a
			//					valid child in the other model it does not quarentee it is a
			//					valid child in the context of this model.
			var i;
			for(i=0; i<this.childrenAttrs.length; i++) {
				if (array.indexOf(parent[this.childrenAttrs[i]],item) !== -1) {
					return true;
				}
			}
			return false;
		},

		isMemberOf: function (/*dojo.data.item*/ item, /*string|string[]*/childrenLists ) {
			// summary:
			//		Returns true if the item is a member of any of the childrenLists.
			//		(See isChildOf() note)
			if (this.isItem(item)) {
				var parents	= this.getParents(item);
				var lists		= childrenLists ? (lang.isArray(childrenLists) ? childrenLists : [childrenLists]) : [];
				var isMember = false;
				var i;
				array.some(parents, function(parent){
						for (i=0; i<lists.length; i++) {
							if (array.indexOf(parent[lists[i]],item) != -1) {
								return (isMember = true);
							}
						}
					}, this);
			}
			return isMember;
		},
		
		// =======================================================================
		// Write interface

		deleteItem: function (/*dojo.data.Item*/ storeItem){
			// summary:
			//		Delete a store item.
			// storeItem:
			//		The store item to be delete.
			// tag:
			//		public
			
			return this.store.deleteItem(storeItem);
		},

		newItem: function (/*dojo.dnd.Item*/ args, /*dojo.data.item*/ parent, /*int?*/ insertIndex, /*String?*/ childrenAttr){
			// summary:
			//		Creates a new item.	 See `dojo.data.api.Write` for details on args.
			//		Used in drag & drop when item from external source dropped onto tree
			//		or can be called programmatically.
			//
			//		NOTE: Whenever a parent is specified the underlaying store method
			//					newItem() will NOT create args as a top level item a.k.a a
			//					root item.
			// args:
			//		A javascript object defining the initial content of the item as a set
			//		of JavaScript 'property name: value' pairs.
			// parent:
			//		Optional, a valid store item that will serve as the parent of the new
			//		item.	 If ommitted,	the new item is automatically created as a top
			//		level item in the store. (see also: newReferenceItem())
			// insertIndex:
			//		If specified the location in the parents list of child items.
			// childrenAttr:
			//		If specified the childrens list attribute to which the new item will
			//		be added.	 If ommitted, the first entry in the models childrenAttrs
			//		property is used.
			
			var pInfo = {parent: parent, attribute: (childrenAttr ? childrenAttr : this.childrenAttrs[0])},
					newItem;

			this._mapIdentifierAttr(args, false);
			try {
				newItem = this.store.itemExist(args);	 // Write store extension...
				if (newItem) {
					this.pasteItem(newItem, null, parent, true, insertIndex);
				} else {
					newItem = this.store.newItem(args, pInfo);
					if (newItem && (insertIndex!=undefined)){
						// Move new item to desired position
						this.pasteItem(newItem, parent, parent, false, insertIndex);
					}
				}
			} catch(err) {
				throw new Error(this.moduleName+"::newItem(): " + err);
			} 
			return newItem;
		},

		pasteItem: function(/*Item*/ childItem, /*Item*/ oldParentItem, /*Item*/ newParentItem, /*Boolean*/ bCopy, 
												 /*int?*/ insertIndex, /*String?*/ childrenAttr){
			// summary:
			//		Move or copy an item from one parent item to another.
			//		Used in drag & drop
			var parentAttr = childrenAttr ? childrenAttr : this.childrenAttrs[0],	// name of "children" attr in parent item
					store = this.store,
					firstChild;
				
			// remove child from source item, and record the attribute that child occurred in
			if(oldParentItem){
				array.forEach(this.childrenAttrs, function(attr){
					if(store.containsValue(oldParentItem, attr, childItem)){
						if(!bCopy){
							store.removeReference(childItem, oldParentItem, attr);
						}
						parentAttr = attr;
					}
				}, this);
			}
			// modify target item's children attribute to include this item
			if(newParentItem){
				store.addReference(childItem, newParentItem, parentAttr, insertIndex);
			}
		},

		// =======================================================================
		// Label Attribute 

		getLabelAttr: function () {
			// summary:
			//		Returns the labelAttr property.
			// tags:
			//		public
			if (!this.labelAttr) {
				var labels = this.store.getLabelAttributes();
				if (labels) {
					this.setLabelAttr(labels[0]);
				}
			}
			return this.labelAttr;
		},

		setLabelAttr: function (/*String*/ newValue) {
			// summary:
			//		Set the labelAttr property.
			// newValue:
			//		New labelAttr newValue.
			// tags:
			//		public
			if (lang.isString(newValue) && newValue.length) {
				if (this.labelAttr !== newValue) {
					var oldValue	 = this.labelAttr;
					this.labelAttr = newValue;
					// Signal the event.
					this.onLabelChange(oldValue, newValue);
				}
				return this.labelAttr;
			}
		},

		// =======================================================================
		// Callbacks

		onChange: function(/*===== item, attribute, newValue =====*/){
			// summary:
			//		Callback whenever an item has changed, so that Tree
			//		can update the label, icon, etc.	 Note that changes
			//		to an item's children or parent(s) will trigger an
			//		onChildrenChange() so you can ignore those changes here.
			// tags:
			//		callback
		},

		onChildrenChange: function(/*===== parent, newChildrenList =====*/){
			// summary:
			//		Callback to do notifications about new, updated, or deleted items.
			// parent: dojo.data.item
			// newChildrenList: dojo.data.item[]
			// tags:
			//		callback
		},

		onDataValidated: function(){
			// summary:
			//		Callback when store validation completion. Only called if strict
			//		parent-child relationship is enabled.
			// tag:
			//		callback
		},

		onDelete: function(/*===== item =====*/){
			// summary:
			//		Callback when an item has been deleted.
			// description:
			//		Note that there will also be an onChildrenChange() callback for the parent
			//		of this item.
			// tags:
			//		callback
//			this.store.save();
		},

		onLabelChange: function (/*===== oldValue, newValue =====*/){
			// summary:
			//		Callback when label attribute property changed.
			// tags:
			//		callback
		},

		// =======================================================================
		// Events from data store

		onNewItem: function(/* dojo.data.item */ item, /* Object */ parentInfo){
			// summary:
			//		Handler for when new items appear in the store, either from a drop operation
			//		or some other way.	 Updates the tree view (if necessary).
			// description:
			//		If the new item is a child of an existing item,
			//		calls onChildrenChange() with the new list of children
			//		for that existing item.
			//
			// tags:
			//		extension
			
			// Call onChildrenChange() on parent (ie, existing) item with new list of children
			// In the common case, the new list of children is simply parentInfo.newValue or
			// [ parentInfo.newValue ], although if items in the store has multiple
			// child attributes (see `childrenAttr`), then it's a superset of parentInfo.newValue,
			// so call getChildren() to be sure to get right answer.
			if(parentInfo) {
				this.getChildren(parentInfo.item, lang.hitch(this, function(children){
					this.onChildrenChange(parentInfo.item, children);
				}));
			}
			this._updateCheckedParent(item, true);
		},

		onDeleteItem: function (/*dojo.data.item*/ storeItem){
			// summary:
			//		Handler for delete notifications from the store.
			// storeItem:
			//		The store item that was deleted.

			this.onDelete(storeItem);
		},

		onError: function (/*Object*/ err) {
			// summary:
			//		Callback when an error occurred.
			// tags:
			//		callback
			console.error(this, err);
		},

		onSetItem: function (/*dojo.data.item*/ storeItem, /*string*/ attribute, /*AnyType*/ oldValue, 
													/*AnyType*/ newValue){
			// summary:
			//		Updates the tree view according to changes in the data store.
			// description:
			//		Handles updates to a store item's children by calling onChildrenChange(), and
			//		other updates to a store item by calling onChange().
			// storeItem: 
			//		Store item
			// attribute: 
			//		attribute-name-string
			// oldValue:
			//		Old attribute value
			// newValue:
			//		New attribute value.
			// tags:
			//		extension

			if (array.indexOf(this.childrenAttrs, attribute) != -1){
				// Store item's children list changed
				this.getChildren(storeItem, lang.hitch(this, function (children){
					// See comments in onNewItem() about calling getChildren()
					if (children[0]) {
						this._updateCheckedParent(children[0], true);
					} else {
						// If no children left, set the default checked state.
						this._setChecked( storeItem, this.checkedState);
					}
					this.onChildrenChange(storeItem, children);
				}));
			}else{
				// If the attribute is the attribute associated with the checked state
				// go update the store items parent.
				if (attribute == this.checkedAttr) {
					this._updateCheckedParent(storeItem, false);
				}
				this.onChange(storeItem, attribute, newValue);
			}
		},

		onStoreLoaded: function( count ) {
			// summary:
			//		Update the current labelAttr property by fetching it from the store.
			// tag:
			//		callback

			this.getLabelAttr();
		},

		onRootChange: function (/*dojo.data.item*/ storeItem, /*String*/ action) {
			// summary:
			//		Handler for any changes to the stores top level items.
			// description:
			//		Users can extend this method to modify a new element that's being
			//		added to the root of the tree, for example to make sure the new item
			//		matches the tree root query. Remember, even though the item is added
			//		as a top level item in the store it does not quarentee it will match
			//		your tree query unless your query is simply the store identifier.
			//		Therefore, in case of a store root detach event (evt.detach=true) we
			//		only require if the item is a known child of the tree root.
			// storeItem:
			//		The store item that was attached to, or detached from, the root.
			// action:
			//		String detailing the type of event: "new", "delete", "attach" or 
			//		"detach"
			// tag:
			//		callback
		},

		// =======================================================================
		// Misc helper functions

		_mapIdentifierAttr: function (/*Object*/ args, /*Boolean?*/ delMappedAttr) {
			// summary:
			//		Map the 'newItemIdAttr' property of a new item to the store identifier
			//		attribute. Return true if the mapping was made.
			// description:
			//		If a store has an identifier attribute defined each new item MUST have
			//		at least that same attribute defined otherwise the store will reject
			//		the item to be inserted. This method handles the conversion from the
			//		'newItemIdAttr' to the store required identifier attribute.
			// args:
			//		Object defining the new item properties.
			// delMappedAttr:
			//		If true, it determines when a mapping was made, if the mapped attribute
			//		is to be removed from the new item properties.
			// tags:
			//		private
			
			var identifierAttr = this.store.getIdentifierAttr();
			
			if (identifierAttr) {
				if (!args[identifierAttr] && (this.newItemIdAttr && args[this.newItemIdAttr])) {
					args[identifierAttr] = args[this.newItemIdAttr];
					if (delMappedAttr) {
						delete args[this.newItemIdAttr];
					}
					return true;
				}
			}
			// Check if checked state needs adding.
			if (this.checkedAll && args[this.checkedAttr] === undefined) {
				args[this.checkedAttr] = this.checkedState;
			}
			return false;
		},
		
		_diffArrays: function (/*array*/ orgArray, /*array*/ elements) {
			// summary:
			//		Returns a new array which is 'orgArray' with all 'elements' removed.
			// tags:
			//		private

			var elemList = elements ? (lang.isArray(elements) ? elements : [elements]) :[];
			var newArray = orgArray.slice(0);
			var index, i;
			
			if (newArray.length && elemList.length) {
				for(i=0; i<elemList.length; i++) {
					index = array.indexOf(newArray, elemList[i]);
					if (index != -1) {
						newArray.splice(index,1);
					}
				}
			}
			return newArray;
		},
		
		_mixinFetch: function (/*object*/ fetchArgs ) {
			// summary:
			//		Any model that inherits from this model (TreeStoreModel) and requires
			//		additional parameters to be passed in a store fetch(), loadStore() or
			//		loadItem() call must overwrite this method.
			return fetchArgs;
		}

	});
});

},
'dijit/tree/ForestStoreModel':function(){
define("dijit/tree/ForestStoreModel", [
	"dojo/_base/array", // array.indexOf array.some
	"dojo/_base/declare", // declare
	"dojo/_base/kernel", // global
	"dojo/_base/lang", // lang.hitch
	"./TreeStoreModel"
], function(array, declare, kernel, lang, TreeStoreModel){

// module:
//		dijit/tree/ForestStoreModel

return declare("dijit.tree.ForestStoreModel", TreeStoreModel, {
	// summary:
	//		Interface between a dijit.Tree and a dojo.data store that doesn't have a root item,
	//		a.k.a. a store that has multiple "top level" items.
	//
	// description:
	//		Use this class to wrap a dojo.data store, making all the items matching the specified query
	//		appear as children of a fabricated "root item".  If no query is specified then all the
	//		items returned by fetch() on the underlying store become children of the root item.
	//		This class allows dijit.Tree to assume a single root item, even if the store doesn't have one.
	//
	//		When using this class the developer must override a number of methods according to their app and
	//		data, including:
	//
	//		- onNewRootItem
	//		- onAddToRoot
	//		- onLeaveRoot
	//		- onNewItem
	//		- onSetItem

	// Parameters to constructor

	// rootId: String
	//		ID of fabricated root item
	rootId: "$root$",

	// rootLabel: String
	//		Label of fabricated root item
	rootLabel: "ROOT",

	// query: String
	//		Specifies the set of children of the root item.
	// example:
	//	|	{type:'continent'}
	query: null,

	// End of parameters to constructor

	constructor: function(params){
		// summary:
		//		Sets up variables, etc.
		// tags:
		//		private

		// Make dummy root item
		this.root = {
			store: this,
			root: true,
			id: params.rootId,
			label: params.rootLabel,
			children: params.rootChildren	// optional param
		};
	},

	// =======================================================================
	// Methods for traversing hierarchy

	mayHaveChildren: function(/*dojo/data/Item*/ item){
		// summary:
		//		Tells if an item has or may have children.  Implementing logic here
		//		avoids showing +/- expando icon for nodes that we know don't have children.
		//		(For efficiency reasons we may not want to check if an element actually
		//		has children until user clicks the expando node)
		// tags:
		//		extension
		return item === this.root || this.inherited(arguments);
	},

	getChildren: function(/*dojo/data/Item*/ parentItem, /*function(items)*/ callback, /*function*/ onError){
		// summary:
		//		Calls onComplete() with array of child items of given parent item, all loaded.
		if(parentItem === this.root){
			if(this.root.children){
				// already loaded, just return
				callback(this.root.children);
			}else{
				this.store.fetch({
					query: this.query,
					onComplete: lang.hitch(this, function(items){
						this.root.children = items;
						callback(items);
					}),
					onError: onError
				});
			}
		}else{
			this.inherited(arguments);
		}
	},

	// =======================================================================
	// Inspecting items

	isItem: function(/* anything */ something){
		return (something === this.root) ? true : this.inherited(arguments);
	},

	fetchItemByIdentity: function(/* object */ keywordArgs){
		if(keywordArgs.identity == this.root.id){
			var scope = keywordArgs.scope || kernel.global;
			if(keywordArgs.onItem){
				keywordArgs.onItem.call(scope, this.root);
			}
		}else{
			this.inherited(arguments);
		}
	},

	getIdentity: function(/* item */ item){
		return (item === this.root) ? this.root.id : this.inherited(arguments);
	},

	getLabel: function(/* item */ item){
		return	(item === this.root) ? this.root.label : this.inherited(arguments);
	},

	// =======================================================================
	// Write interface

	newItem: function(/* dijit/tree/dndSource.__Item */ args, /*Item*/ parent, /*int?*/ insertIndex){
		// summary:
		//		Creates a new item.   See dojo/data/api/Write for details on args.
		//		Used in drag & drop when item from external source dropped onto tree.
		if(parent === this.root){
			this.onNewRootItem(args);
			return this.store.newItem(args);
		}else{
			return this.inherited(arguments);
		}
	},

	onNewRootItem: function(/* dijit/tree/dndSource.__Item */ /*===== args =====*/){
		// summary:
		//		User can override this method to modify a new element that's being
		//		added to the root of the tree, for example to add a flag like root=true
	},

	pasteItem: function(/*Item*/ childItem, /*Item*/ oldParentItem, /*Item*/ newParentItem, /*Boolean*/ bCopy, /*int?*/ insertIndex){
		// summary:
		//		Move or copy an item from one parent item to another.
		//		Used in drag & drop
		if(oldParentItem === this.root){
			if(!bCopy){
				// It's onLeaveRoot()'s responsibility to modify the item so it no longer matches
				// this.query... thus triggering an onChildrenChange() event to notify the Tree
				// that this element is no longer a child of the root node
				this.onLeaveRoot(childItem);
			}
		}
		this.inherited(arguments, [childItem,
			oldParentItem === this.root ? null : oldParentItem,
			newParentItem === this.root ? null : newParentItem,
			bCopy,
			insertIndex
		]);
		if(newParentItem === this.root){
			// It's onAddToRoot()'s responsibility to modify the item so it matches
			// this.query... thus triggering an onChildrenChange() event to notify the Tree
			// that this element is now a child of the root node
			this.onAddToRoot(childItem);
		}
	},

	// =======================================================================
	// Handling for top level children

	onAddToRoot: function(/* item */ item){
		// summary:
		//		Called when item added to root of tree; user must override this method
		//		to modify the item so that it matches the query for top level items
		// example:
		//	|	store.setValue(item, "root", true);
		// tags:
		//		extension
		console.log(this, ": item ", item, " added to root");
	},

	onLeaveRoot: function(/* item */ item){
		// summary:
		//		Called when item removed from root of tree; user must override this method
		//		to modify the item so it doesn't match the query for top level items
		// example:
		//	|	store.unsetAttribute(item, "root");
		// tags:
		//		extension
		console.log(this, ": item ", item, " removed from root");
	},

	// =======================================================================
	// Events from data store

	_requeryTop: function(){
		// reruns the query for the children of the root node,
		// sending out an onSet notification if those children have changed
		var oldChildren = this.root.children || [];
		this.store.fetch({
			query: this.query,
			onComplete: lang.hitch(this, function(newChildren){
				this.root.children = newChildren;

				// If the list of children or the order of children has changed...
				if(oldChildren.length != newChildren.length ||
					array.some(oldChildren, function(item, idx){ return newChildren[idx] != item;})){
					this.onChildrenChange(this.root, newChildren);
				}
			})
		});
	},

	onNewItem: function(/* dojo/data/api/Item */ item, /* Object */ parentInfo){
		// summary:
		//		Handler for when new items appear in the store.  Developers should override this
		//		method to be more efficient based on their app/data.
		// description:
		//		Note that the default implementation requeries the top level items every time
		//		a new item is created, since any new item could be a top level item (even in
		//		addition to being a child of another item, since items can have multiple parents).
		//
		//		If developers can detect which items are possible top level items (based on the item and the
		//		parentInfo parameters), they should override this method to only call _requeryTop() for top
		//		level items.  Often all top level items have parentInfo==null, but
		//		that will depend on which store you use and what your data is like.
		// tags:
		//		extension
		this._requeryTop();

		this.inherited(arguments);
	},

	onDeleteItem: function(/*Object*/ item){
		// summary:
		//		Handler for delete notifications from underlying store

		// check if this was a child of root, and if so send notification that root's children
		// have changed
		if(array.indexOf(this.root.children, item) != -1){
			this._requeryTop();
		}

		this.inherited(arguments);
	},

	onSetItem: function(/* item */ item,
					/* attribute-name-string */ attribute,
					/* Object|Array */ oldValue,
					/* Object|Array */ newValue){
		// summary:
		//		Updates the tree view according to changes to an item in the data store.
		//		Developers should override this method to be more efficient based on their app/data.
		// description:
		//		Handles updates to an item's children by calling onChildrenChange(), and
		//		other updates to an item by calling onChange().
		//
		//		Also, any change to any item re-executes the query for the tree's top-level items,
		//		since this modified item may have started/stopped matching the query for top level items.
		//
		//		If possible, developers should override this function to only call _requeryTop() when
		//		the change to the item has caused it to stop/start being a top level item in the tree.
		// tags:
		//		extension

		this._requeryTop();
		this.inherited(arguments);
	}

});

});

},
'commsy/Search':function(){
define("commsy/Search", [	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom-attr"], function(declare, BaseClass, lang, Query, On, DomAttr) {
	return declare(BaseClass, {		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.threshold = 3;
			this.used = false;
			this.matches = [];
			this.ajaxRequests = [];
		},
		
		setup: function(node) {
			// register handler
			On(node, "keyup", lang.hitch(this, function(event) {
				this.onKeyUp(event);
			}));
			
			On(node, "click", lang.hitch(this, function(event) {
				this.onClick(event);
			}));
		},
		
		onKeyUp: function(event) {
			//var char = String.fromCharCode(event.keyCode).toLowerCase();
			
			// set suggestion to typed text
			DomAttr.set(Query("input#search_suggestion")[0], "value", event.target.value);
			
			// only update if threshold is met
			if(event.target.value.length === this.threshold) {
				// abort all running ajax requests
				dojo.forEach(this.ajaxRequests, function(request, index, arr) {
					request.cancel();
				});
				
				// send ajax request
				var request = this.AJAXRequest("search", "getAutocompleteSuggestions", { search_text: event.target.value.toLowerCase() },
					lang.hitch(this, function(words) {
						// update matches
						this.matches = words;
						
						// autosuggest
						this.autoSuggest(DomAttr.get(Query("input#search_input")[0], "value"));
					}),
					
					lang.hitch(this, function(err) {
						console.log(err);
					}),
					false);
				
				// store this request in array
				this.ajaxRequests.push(request);
				
			} else if(event.target.value.length > this.threshold) {
				// autosuggest
				this.autoSuggest(event.target.value);
			}
		},
		
		onClick: function(event) {
			if(this.used === false) {
				// initial use
				DomAttr.set(event.target, "value", "");
				DomAttr.set(Query("input#search_suggestion")[0], "value", "");
				this.used = true;
			}	
		},
		
		autoSuggest: function(userInput) {
			var length = 33;
			var suggestion = "";
			
			// find new suggestion
			dojo.forEach(this.matches, function(match, index, arr) {
				// current input needs to match the beginning of match
				if(userInput.toLowerCase() === match.substr(0, userInput.length)) {
					// match needs to be longer than userInput
					if(match.length > userInput.length) {
						// find shortest
						if(match.length < length) {
							length = match.length;
							suggestion = match;
						}
					}
				}
			});
			
			// set suggestion - take first characters from user input
			DomAttr.set(Query("input#search_suggestion")[0], "value", userInput + suggestion.substr(userInput.length));
		}
	});
});
},
'commsy/AjaxActions':function(){
define("commsy/AjaxActions", [	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/_base/fx",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style"], function(declare, BaseClass, On, Lang, FX, Query, DomClass, DomAttr, DomConstruct, DomStyle) {
	return declare(BaseClass, {
		constructor: function(args) {
			args = args || {};
			declare.safeMixin(this, args);
		},
		
		setup: function(nodeList) {
			// get custom attribute data for all nodes
			dojo.forEach(nodeList, Lang.hitch(this, function(node, index, arr) {
				var customObject = this.getAttrAsObject(node, "data-custom");
				
				// register click
				On(node, "click", Lang.hitch(this, function(event) {
					// call function if exist
					if(this[customObject.action]) Lang.hitch(this, this[customObject.action](customObject));
				}));
			}));
		},
		
		addToClipboard: function(customObject) {
			var itemId = customObject.iid;
			
			// send ajax requets
			this.AJAXRequest("actions", "addToClipboard", { itemId: itemId }, Lang.hitch(this, function(response) {
				// item was added, update number of items in clipboard
				var ClipboardButtonNode = Query("a#tm_clipboard")[0];
				
				if(ClipboardButtonNode) {
					var spanNode = Query("span#tm_clipboard_copies")[0];
					
					if(!spanNode) {
						// create span
						spanNode = DomConstruct.create("span", {
							"id":		"tm_clipboard_copies",
							innerHTML:	0
						}, ClipboardButtonNode, "after");
					}
					
					// increase count
					DomAttr.set(spanNode, "innerHTML", parseInt(DomAttr.get(spanNode, "innerHTML")) + 1);
				}
			}));
		},
		
		versionMakeNew: function(customObject) {
			var itemId = customObject.iid;
			var versionID = customObject.vid;

			// send ajax requets
			this.AJAXRequest("actions", "versionMakeNew", { itemId: itemId, versionID: versionID }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		},
		
		exportToWordpress: function(customObject) {
			var itemId = customObject.iid;

			// send ajax requets
			this.AJAXRequest("actions", "exportToWordpress", { itemId: itemId }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		},
		
		exportToWiki: function(customObject) {
			var itemId = customObject.iid;

			// send ajax requets
			this.AJAXRequest("actions", "exportToWiki", { itemId: itemId }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		}
		
		/*closeParticipation: function(customObject) {
			this.button_close_participation_room = new dijit.form.Button({
				label:		"Teilnahme beenden in diesem Raum",
				onClick:	Lang.hitch(this, function(event) {
					this.onPopupSubmit({
	                   part: "user_configuration",
	                   action: "close_participation_room",
	                });
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			this.button_delete_participation_room = new dijit.form.Button({
				label:		"Teilnahme l&ouml;schen in diesem Raum",
				onClick:	Lang.hitch(this, function(event) {
					this.onPopupSubmit({
	                   part: "user_configuration",
	                   action: "delete_participation_room",
	                });
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			this.button_close_participation_portal = new dijit.form.Button({
				label:		"Teilnahme beenden im Portal",
				onClick:	Lang.hitch(this, function(event) {
					this.onPopupSubmit({
	                   part: "user_configuration",
	                   action: "close_participation_portal",
	                });
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			this.button_delete_participation_portal = new dijit.form.Button({
				label:		"Teilnahme l&ouml;schen im Portal",
				onClick:	Lang.hitch(this, function(event) {
					this.onPopupSubmit({
	                   part: "user_configuration",
	                   action: "delete_participation_portal",
	                });
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			this.button_cancel = new dijit.form.Button({
				label:		"Abbrechen",
				onClick:	Lang.hitch(this, function(event) {
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});

			this.dialog = new dijit.Dialog({
				title:		"Mitgliedschaft beenden",
				content: 	"Sie haben zwei M&ouml;glichkeiten:<br/>Wenn Sie <b>Teilnahme beenden in diesem Raum</b> w&auml;hlen, wird ihre Kennung f&uuml;r diesen Raum gesperrt." +
							"Sie haben dann keinen Zutritt mehr zu dem Raum Blog. Ihre Beitr&auml;ge bleiben aber erhalten. Falls Sie bestimmte Eintr&auml;ge l&ouml;schen wollen," +
							"tun Sie das bitte, bevor Sie ihre Teilnahme beenden. Ihr Zugang kann bei Bedarf wieder freigeschaltet werden.<br/><br/>" +
							"Wenn Sie <b>Teilnahme l&ouml;schen in diesem Raum</b> w&auml;hlen, werden s&auml;mtliche ihrer Beitr&auml;ge und ihre Kennung in dem Raum gel&ouml;scht." +
							"Sie k&ouml;nnen danach den Raum nicht mehr betreten. Achtung: Dies kann nicht r&uuml;ckg&auml;ngig gemacht werden.<br/><br/>" +
                            "Alternativ k&ouml;nnen Sie f&uuml;r das gesamte CommSy Portal und alle R&auml;ume:" +
                            "<ul><li><b>Teilnahme beenden</b> oder</li><li><b>Teilnahme l&ouml;schen</b>."
			});
			console.log(this.dialog.containerNode);
			dojo.place(this.button_close_participation_room.domNode, this.dialog.containerNode);
			dojo.place(this.button_delete_participation_room.domNode, this.dialog.containerNode);
			dojo.place(this.button_close_participation_portal.domNode, this.dialog.containerNode);
			dojo.place(this.button_delete_participation_portal.domNode, this.dialog.containerNode);
			dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
			this.dialog.show();
		}*/
		
	});
});
},
'dojo/dnd/Manager':function(){
define("dojo/dnd/Manager", [
	"../_base/array",  "../_base/declare", "../_base/event", "../_base/lang", "../_base/window",
	"../dom-class", "../Evented", "../has", "../keys", "../on", "../topic", "../touch",
	"./common", "./autoscroll", "./Avatar"
], function(array, declare, event, lang, win, domClass, Evented, has, keys, on, topic, touch,
	dnd, autoscroll, Avatar){

// module:
//		dojo/dnd/Manager

var Manager = declare("dojo.dnd.Manager", [Evented], {
	// summary:
	//		the manager of DnD operations (usually a singleton)
	constructor: function(){
		this.avatar  = null;
		this.source = null;
		this.nodes = [];
		this.copy  = true;
		this.target = null;
		this.canDropFlag = false;
		this.events = [];
	},

	// avatar's offset from the mouse
	OFFSET_X: has("touch") ? 0 : 16,
	OFFSET_Y: has("touch") ? -64 : 16,

	// methods
	overSource: function(source){
		// summary:
		//		called when a source detected a mouse-over condition
		// source: Object
		//		the reporter
		if(this.avatar){
			this.target = (source && source.targetState != "Disabled") ? source : null;
			this.canDropFlag = Boolean(this.target);
			this.avatar.update();
		}
		topic.publish("/dnd/source/over", source);
	},
	outSource: function(source){
		// summary:
		//		called when a source detected a mouse-out condition
		// source: Object
		//		the reporter
		if(this.avatar){
			if(this.target == source){
				this.target = null;
				this.canDropFlag = false;
				this.avatar.update();
				topic.publish("/dnd/source/over", null);
			}
		}else{
			topic.publish("/dnd/source/over", null);
		}
	},
	startDrag: function(source, nodes, copy){
		// summary:
		//		called to initiate the DnD operation
		// source: Object
		//		the source which provides items
		// nodes: Array
		//		the list of transferred items
		// copy: Boolean
		//		copy items, if true, move items otherwise

		// Tell autoscroll that a drag is starting
		autoscroll.autoScrollStart(win.doc);

		this.source = source;
		this.nodes  = nodes;
		this.copy   = Boolean(copy); // normalizing to true boolean
		this.avatar = this.makeAvatar();
		win.body().appendChild(this.avatar.node);
		topic.publish("/dnd/start", source, nodes, this.copy);
		this.events = [
			on(win.doc, touch.move, lang.hitch(this, "onMouseMove")),
			on(win.doc, touch.release,   lang.hitch(this, "onMouseUp")),
			on(win.doc, "keydown",   lang.hitch(this, "onKeyDown")),
			on(win.doc, "keyup",     lang.hitch(this, "onKeyUp")),
			// cancel text selection and text dragging
			on(win.doc, "dragstart",   event.stop),
			on(win.body(), "selectstart", event.stop)
		];
		var c = "dojoDnd" + (copy ? "Copy" : "Move");
		domClass.add(win.body(), c);
	},
	canDrop: function(flag){
		// summary:
		//		called to notify if the current target can accept items
		var canDropFlag = Boolean(this.target && flag);
		if(this.canDropFlag != canDropFlag){
			this.canDropFlag = canDropFlag;
			this.avatar.update();
		}
	},
	stopDrag: function(){
		// summary:
		//		stop the DnD in progress
		domClass.remove(win.body(), ["dojoDndCopy", "dojoDndMove"]);
		array.forEach(this.events, function(handle){ handle.remove(); });
		this.events = [];
		this.avatar.destroy();
		this.avatar = null;
		this.source = this.target = null;
		this.nodes = [];
	},
	makeAvatar: function(){
		// summary:
		//		makes the avatar; it is separate to be overwritten dynamically, if needed
		return new Avatar(this);
	},
	updateAvatar: function(){
		// summary:
		//		updates the avatar; it is separate to be overwritten dynamically, if needed
		this.avatar.update();
	},

	// mouse event processors
	onMouseMove: function(e){
		// summary:
		//		event processor for onmousemove
		// e: Event
		//		mouse event
		var a = this.avatar;
		if(a){
			autoscroll.autoScrollNodes(e);
			//autoscroll.autoScroll(e);
			var s = a.node.style;
			s.left = (e.pageX + this.OFFSET_X) + "px";
			s.top  = (e.pageY + this.OFFSET_Y) + "px";
			var copy = Boolean(this.source.copyState(dnd.getCopyKeyState(e)));
			if(this.copy != copy){
				this._setCopyStatus(copy);
			}
		}
		if(has("touch")){
			// Prevent page from scrolling so that user can drag instead.
			e.preventDefault();
		}
	},
	onMouseUp: function(e){
		// summary:
		//		event processor for onmouseup
		// e: Event
		//		mouse event
		if(this.avatar){
			if(this.target && this.canDropFlag){
				var copy = Boolean(this.source.copyState(dnd.getCopyKeyState(e)));
				topic.publish("/dnd/drop/before", this.source, this.nodes, copy, this.target, e);
				topic.publish("/dnd/drop", this.source, this.nodes, copy, this.target, e);
			}else{
				topic.publish("/dnd/cancel");
			}
			this.stopDrag();
		}
	},

	// keyboard event processors
	onKeyDown: function(e){
		// summary:
		//		event processor for onkeydown:
		//		watching for CTRL for copy/move status, watching for ESCAPE to cancel the drag
		// e: Event
		//		keyboard event
		if(this.avatar){
			switch(e.keyCode){
				case keys.CTRL:
					var copy = Boolean(this.source.copyState(true));
					if(this.copy != copy){
						this._setCopyStatus(copy);
					}
					break;
				case keys.ESCAPE:
					topic.publish("/dnd/cancel");
					this.stopDrag();
					break;
			}
		}
	},
	onKeyUp: function(e){
		// summary:
		//		event processor for onkeyup, watching for CTRL for copy/move status
		// e: Event
		//		keyboard event
		if(this.avatar && e.keyCode == keys.CTRL){
			var copy = Boolean(this.source.copyState(false));
			if(this.copy != copy){
				this._setCopyStatus(copy);
			}
		}
	},

	// utilities
	_setCopyStatus: function(copy){
		// summary:
		//		changes the copy status
		// copy: Boolean
		//		the copy status
		this.copy = copy;
		this.source._markDndStatus(this.copy);
		this.updateAvatar();
		domClass.replace(win.body(),
			"dojoDnd" + (this.copy ? "Copy" : "Move"),
			"dojoDnd" + (this.copy ? "Move" : "Copy"));
	}
});

// dnd._manager:
//		The manager singleton variable. Can be overwritten if needed.
dnd._manager = null;

Manager.manager = dnd.manager = function(){
	// summary:
	//		Returns the current DnD manager.  Creates one if it is not created yet.
	if(!dnd._manager){
		dnd._manager = new Manager();
	}
	return dnd._manager;	// Object
};

return Manager;
});

},
'dijit/form/ToggleButton':function(){
define("dijit/form/ToggleButton", [
	"dojo/_base/declare", // declare
	"dojo/_base/kernel", // kernel.deprecated
	"./Button",
	"./_ToggleButtonMixin"
], function(declare, kernel, Button, _ToggleButtonMixin){

	// module:
	//		dijit/form/ToggleButton


	return declare("dijit.form.ToggleButton", [Button, _ToggleButtonMixin], {
		// summary:
		//		A templated button widget that can be in two states (checked or not).
		//		Can be base class for things like tabs or checkbox or radio buttons.

		baseClass: "dijitToggleButton",

		setChecked: function(/*Boolean*/ checked){
			// summary:
			//		Deprecated.  Use set('checked', true/false) instead.
			kernel.deprecated("setChecked("+checked+") is deprecated. Use set('checked',"+checked+") instead.", "", "2.0");
			this.set('checked', checked);
		}
	});
});

},
'commsy/main':function(){
require([	"dojo/_base/declare",
         	"commsy/base",
         	"dojo/_base/lang"], function(declare, BaseClass, Lang) {
	var Controller = declare(BaseClass, {
		constructor: function(args) {
			
		},
		
		init: function() {
			require([	"dojo/query",
			         	"dojo/dom-attr",
			         	"dojo/on",
			         	"dojo/NodeList-traverse",
			         	"dojo/domReady!"], Lang.hitch(this, function(query, domAttr, On, ready) {
			    
			    var uri_object = this.uri_object;
				
				this.initCommsyBar();
				
				// register event for handling mouse actions outside content div
				On(document.body, "click", Lang.hitch(this, function(event) {
					if(domAttr.get(event.target, "id") === "popup_wrapper") {
						// TODO: create something like a tooltip here
						alert("Bitte schließen Sie zuerst das Popup-Fenster, bevor Sie sonstige Seitenoperationen ausführen");
					}
				}));
				
				/* temporary inline hotfix */
				
				if (this.uri_object.mod == "group") {
					var joinNode = query("a#group_detail_group_enter")[0];
					
					if (joinNode) {
						var customObject = this.getAttrAsObject(joinNode, "data-custom");
						
						var qry = dojo.objectToQuery(this.replaceOrSetURIParam("group_option", "1"));
						
						if (customObject.needsCode) {
							
							require(["dojo/on","dijit/form/TextBox","dijit/form/Button","dijit/Dialog"], function(On, TextBox, Button, Dialog) {
								
								On(joinNode, "click", Lang.hitch(this, function(event) {
									var input = new dijit.form.TextBox({
										
									});
									
									var button = new dijit.form.Button({
										label:	"betreten",
										onClick:	Lang.hitch(this, function(event) {
											location.href = "commsy.php?" + qry + "&code=" + input.value;
											
											dialog.destroyRecursive();
										})
									});
									var dialog = new dijit.Dialog({
										title:		"Teilnahme-Code"
									});
									dojo.place(input.domNode, dialog.containerNode, "last");
									dojo.place(button.domNode, dialog.containerNode, "last");
									
									
									dialog.show();
									
									event.preventDefault();
									return false;
								}));
								
								
							});
						}
					}
				}
				
				/* ~temporary inline hotfix */
				
				// widget popups
				var aStackNode = query("a#tm_stack")[0];
				if (aStackNode) {
					require(["commsy/popups/ToggleStack"], function(StackPopup) {
						var handler = new StackPopup(aStackNode, query("div#tm_menus div#tm_dropmenu_stack")[0]);
					});
				}
				
				var aWidgetsNode = query("a#tm_widgets")[0];
				if (aWidgetsNode) {
					require(["commsy/popups/ToggleWidgets"], function(WidgetsPopup) {
						var handler = new WidgetsPopup(aWidgetsNode, query("div#tm_menus div#tm_dropmenu_widget_bar")[0]);
					});
				}
				
				var aPortfolioNode = query("a#tm_portfolio")[0];
				if (aPortfolioNode) {
					require(["commsy/popups/TogglePortfolio"], function(PortfolioPopup) {
						var handler = new PortfolioPopup(aPortfolioNode, query("div#tm_menus div#tm_dropmenu_portfolio")[0]);
					});
				}
				
				/*
				
				require(["commsy/popups/ToggleMyCalendar"], function(MyCalendarPopup) {
					var handler = newMyCalendarPopup(query("a#tm_mycalendar")[0], query("div#tm_menus div#tm_dropmenu_mycalendar")[0]);
				});
				*/
				
				// setup rubric forms
				query(".open_popup").forEach(Lang.hitch(this, function(node, index, arr) {
					// get custom data object
					var customObject = this.getAttrAsObject(node, "data-custom");
					
					var module = customObject.module;
					
					require(["commsy/popups/Click" + this.ucFirst(module) + "Popup"], function(ClickPopup) {
						var handler = new ClickPopup();
						handler.init(node, customObject);
					});
				}));
				
				// buzzwords and tags expander
				if (this.uri_object.fct === "index") {
					require(["commsy/DivToggle"], function(DivToggle) {
						var handler = new DivToggle();
						handler.setup();
					});
				}
				
				// ajax actions
				require(["commsy/AjaxActions"], function(AjaxActions) {
					var aNodes = query("a.ajax_action");
					
					if (aNodes) {
						var handler = new AjaxActions();
						handler.setup(aNodes);
					}
				});
				
				// ckeditor
				query("div.ckeditor").forEach(function(node, index, arr) {
					require(["commsy/ckeditor"], function(CKEditor) {
						var handler = new CKEditor();
						handler.create(node);
					});
				});
				
				// tree
				query("div.tree").forEach(function(node, index, arr) {
					require(["commsy/tree"], function(Tree) {
						var handler = new Tree();
						handler.setupTree(node, function() {
							// highlight path
							if (uri_object.seltag) {
								var seltag = uri_object.seltag;
								var path = handler.buildPath(seltag);
								handler.tree.set("paths", [path]);
							}
						}, true);
					});
				});
				
				// threaded discussion tree
				if (this.uri_object.mod == "discussion" && this.uri_object.fct == "detail") {
					var treeNode = query("div#discussion_tree")[0];
					
					if (treeNode) {
						require(["commsy/DiscussionTree"], function(DiscussionTree) {
							var handler = new DiscussionTree();
							handler.setupTree(treeNode);
						});
					}
				}
				
				// calendar scroll bar position and select auto submit - process directly here
				if (this.uri_object.fct == "index" && this.uri_object.mod == "date") {
					require(["commsy/DateCalendar"], function(DateCalendar) {
						var handler = new DateCalendar();
						handler.setup();
					});
				}
				
				// search
				if(this.from_php.dev.indexed_search === true) {
					var inputNode = query("input#search_input")[0];
					
					if (inputNode) {
						require(["commsy/Search"], function(Search) {
							var handler = new Search();
							handler.setup(inputNode);
						});
					}
				}
				
				// overlays
				query("a.new_item_2, a.new_item, a.attachment, span#detail_assessment, div.cal_days_events a, div.cal_days_week_events a").forEach(function(node, index, arr) {
					require(["commsy/Overlay"], function(Overlay) {
						var handler = Overlay();
						handler.setup(node);
					});
				});
				
				// div expander
				if(this.uri_object.mod === "home") {
					var objects = [];
					query("div.content_item div[class^='list_wrap']").forEach(function(node, index, arr) {					
						objects.push({ div: node, actor:	query("a.open_close", node.parentNode)[0] });
					});
					
					require(["commsy/DivExpander"], function(DivExpander) {
						var handler = DivExpander();
						handler.setup(objects);
					});
				}
				
				// lightbox
				require(["commsy/Lightbox"], function(Lightbox) {
					var handler = Lightbox();
					handler.setup(query("a[class^='lightbox']"));
				});
				
				// progressbar
				query("div.progressbar").forEach(function(node, index, arr) {
					require(["commsy/ProgressBar"], function(ProgressBar) {
						var handler = ProgressBar();
						handler.setup(node);
					});
				});
				
				// on detail context
				if(this.uri_object.fct === "detail") {
					// action expander
					var actors = query(	"div.item_actions a.edit," +
										"div.item_actions a.detail," +
										"div.item_actions a.workflow," +
										"div.item_actions a.linked," + 
										"div.item_actions a.annotations," +
										"div.item_actions a.versions");
						
					require(["commsy/ActionExpander"], function(ActionExpander) {
						var handler = new ActionExpander();
						handler.setup(actors);
					});
				}
				
				// on list context
				if(this.uri_object.fct === "index") {
					// list selection
					var inputNodes = query("input[type='checkbox'][name^='form_data[attach]']");
					var counterNode = query("div.ii_right span#selected_items")[0];
					
					require(["commsy/ListSelection"], function(ListSelection) {
						var handler = new ListSelection();
						handler.setup(inputNodes, counterNode);
					});
				}
				
				// uploader
				query("div.uploader").forEach(function(node, index, arr) {
					require(["commsy/Uploader"], function(Uploader) {
						var handler = new Uploader();
						handler.setup(node);
					});
				});
				
				// follow anchors
				if(window.location.href.indexOf("#") !== -1) {
					require(["commsy/AnchorFollower"], function(AnchorFollower) {
						var handler = new AnchorFollower();
						
						var anchor = window.location.href.substring(window.location.href.indexOf("#") + 1);
						handler.follow(anchor);
					});
				}
				
				// assessment
				require(["commsy/Assessment"], function(Assessment) {
					var handler = new Assessment();
					handler.setup(query("span#detail_assessment")[0]);
				});
				
				// colorpicker
				query("div.colorpicker").forEach(function(node, index, arr) {
					require(["commsy/Colorpicker"], function(Colorpicker) {
						var handler = new Colorpicker();
						handler.setup(node);
					});
				});
				
				// automatic popup opener
				// should be loaded at the very last
				require(["commsy/popups/TogglePersonalConfiguration", "commsy/AutoOpenPopup"], function(Configuration, AutoOpenPopup) {
					var handler = new AutoOpenPopup();
					handler.setup();
				});
			}));
		},
		
		initCommsyBar: function() {
			require([	"dojo/query",
			         	"dojo/on",
			         	"dojo/NodeList-traverse",
			         	"dojo/domReady!"], Lang.hitch(this, function(Query, On, ready) {
			    
	         	/*
	         	 * initiate popup handler
	         	 * new method: first click is handled here and not by module, so we only need to load it when requested
	         	 */
			    var aConfigurationNode = Query("a#tm_settings")[0];
			    if (aConfigurationNode) {
			    	On.once(aConfigurationNode, "click", Lang.hitch(this, function(event) {
			    		require(["commsy/popups/ToggleRoomConfiguration"], function(RoomConfigurationPopup) {
			    			var handler = new RoomConfigurationPopup(aConfigurationNode, Query("div#tm_menus div#tm_dropmenu_configuration")[0]);
			    			handler.open();
		    			});
			    	}));
			    }
			    
			    var aPersonalNode = Query("a#tm_user")[0];
			    if (aPersonalNode) {
			    	On.once(aPersonalNode, "click", Lang.hitch(this, function(event) {
			    		require(["commsy/popups/TogglePersonalConfiguration"], function(PersonalConfigurationPopup) {
		    				var handler = new PersonalConfigurationPopup(aPersonalNode, Query("div#tm_menus div#tm_dropmenu_pers_bar")[0]);
		    				handler.open();
		    			});
			    	}));
			    }
			    
			    var aBreadcrumbNode = Query("a#tm_bread_crumb")[0];
			    if (aBreadcrumbNode) {
			    	On.once(aBreadcrumbNode, "click", Lang.hitch(this, function(event) {
			    		require(["commsy/popups/ToggleBreadcrumb"], function(BreadcrumbPopup) {
	    					var handler = new BreadcrumbPopup(aBreadcrumbNode, Query("div#tm_menus div#tm_dropmenu_breadcrumb")[0]);
	    					handler.open();
	    				});
			    	}));
    			}
			    
			    var aClipboardNode = Query("a#tm_clipboard")[0];
			    if (aClipboardNode) {
			    	On.once(aClipboardNode, "click", Lang.hitch(this, function(event) {
			    		require(["commsy/popups/ToggleClipboard"], function(ClipboardPopup) {
	    					var handler = new ClipboardPopup(aClipboardNode, Query("div#tm_menus div#tm_dropmenu_clipboard")[0]);
	    					handler.open();
	    				});
			    	}));
    			}
			    
    			var aCalendarNode = Query("a#tm_mycalendar")[0];
    			if (aCalendarNode) {
    				On.once(aCalendarNode, "click", Lang.hitch(this, function(event) {
    					require(["commsy/bar/ToggleCalendar"], function(ToggleCalendar) {
    						var handler = new ToggleCalendar(aCalendarNode, Query("div#tm_menus div#tm_dropmenu_mycalendar")[0]);
    						handler.open();
    					});
    				}));
    			}
			}));
		}
	});
	
	var ctrl = new Controller;
	ctrl.init();
});
},
'dojo/data/ItemFileWriteStore':function(){
define("dojo/data/ItemFileWriteStore", ["../_base/lang", "../_base/declare", "../_base/array", "../_base/json", "../_base/kernel",
	"./ItemFileReadStore", "../date/stamp"
], function(lang, declare, arrayUtil, jsonUtil, kernel, ItemFileReadStore, dateStamp){

// module:
//		dojo/data/ItemFileWriteStore

return declare("dojo.data.ItemFileWriteStore", ItemFileReadStore, {
	// summary:
	//		TODOC

	constructor: function(/* object */ keywordParameters){
		// keywordParameters:
		//		The structure of the typeMap object is as follows:
		// |	{
		// |		type0: function || object,
		// |		type1: function || object,
		// |		...
		// |		typeN: function || object
		// |	}
		//		Where if it is a function, it is assumed to be an object constructor that takes the
		//		value of _value as the initialization parameters.  It is serialized assuming object.toString()
		//		serialization.  If it is an object, then it is assumed
		//		to be an object of general form:
		// |	{
		// |		type: function, //constructor.
		// |		deserialize:	function(value) //The function that parses the value and constructs the object defined by type appropriately.
		// |		serialize:	function(object) //The function that converts the object back into the proper file format form.
		// |	}

		// ItemFileWriteStore extends ItemFileReadStore to implement these additional dojo.data APIs
		this._features['dojo.data.api.Write'] = true;
		this._features['dojo.data.api.Notification'] = true;

		// For keeping track of changes so that we can implement isDirty and revert
		this._pending = {
			_newItems:{},
			_modifiedItems:{},
			_deletedItems:{}
		};

		if(!this._datatypeMap['Date'].serialize){
			this._datatypeMap['Date'].serialize = function(obj){
				return dateStamp.toISOString(obj, {zulu:true});
			};
		}
		//Disable only if explicitly set to false.
		if(keywordParameters && (keywordParameters.referenceIntegrity === false)){
			this.referenceIntegrity = false;
		}

		// this._saveInProgress is set to true, briefly, from when save() is first called to when it completes
		this._saveInProgress = false;
	},

	referenceIntegrity: true, //Flag that defaultly enabled reference integrity tracking.  This way it can also be disabled pogrammatially or declaratively.

	_assert: function(/* boolean */ condition){
		if(!condition){
			throw new Error("assertion failed in ItemFileWriteStore");
		}
	},

	_getIdentifierAttribute: function(){
		// this._assert((identifierAttribute === Number) || (dojo.isString(identifierAttribute)));
		return this.getFeatures()['dojo.data.api.Identity'];
	},


/* dojo/data/api/Write */

	newItem: function(/* Object? */ keywordArgs, /* Object? */ parentInfo){
		// summary:
		//		See dojo/data/api/Write.newItem()

		this._assert(!this._saveInProgress);

		if(!this._loadFinished){
			// We need to do this here so that we'll be able to find out what
			// identifierAttribute was specified in the data file.
			this._forceLoad();
		}

		if(typeof keywordArgs != "object" && typeof keywordArgs != "undefined"){
			throw new Error("newItem() was passed something other than an object");
		}
		var newIdentity = null;
		var identifierAttribute = this._getIdentifierAttribute();
		if(identifierAttribute === Number){
			newIdentity = this._arrayOfAllItems.length;
		}else{
			newIdentity = keywordArgs[identifierAttribute];
			if(typeof newIdentity === "undefined"){
				throw new Error("newItem() was not passed an identity for the new item");
			}
			if(lang.isArray(newIdentity)){
				throw new Error("newItem() was not passed an single-valued identity");
			}
		}

		// make sure this identity is not already in use by another item, if identifiers were
		// defined in the file.  Otherwise it would be the item count,
		// which should always be unique in this case.
		if(this._itemsByIdentity){
			this._assert(typeof this._itemsByIdentity[newIdentity] === "undefined");
		}
		this._assert(typeof this._pending._newItems[newIdentity] === "undefined");
		this._assert(typeof this._pending._deletedItems[newIdentity] === "undefined");

		var newItem = {};
		newItem[this._storeRefPropName] = this;
		newItem[this._itemNumPropName] = this._arrayOfAllItems.length;
		if(this._itemsByIdentity){
			this._itemsByIdentity[newIdentity] = newItem;
			//We have to set the identifier now, otherwise we can't look it
			//up at calls to setValueorValues in parentInfo handling.
			newItem[identifierAttribute] = [newIdentity];
		}
		this._arrayOfAllItems.push(newItem);

		//We need to construct some data for the onNew call too...
		var pInfo = null;

		// Now we need to check to see where we want to assign this thingm if any.
		if(parentInfo && parentInfo.parent && parentInfo.attribute){
			pInfo = {
				item: parentInfo.parent,
				attribute: parentInfo.attribute,
				oldValue: undefined
			};

			//See if it is multi-valued or not and handle appropriately
			//Generally, all attributes are multi-valued for this store
			//So, we only need to append if there are already values present.
			var values = this.getValues(parentInfo.parent, parentInfo.attribute);
			if(values && values.length > 0){
				var tempValues = values.slice(0, values.length);
				if(values.length === 1){
					pInfo.oldValue = values[0];
				}else{
					pInfo.oldValue = values.slice(0, values.length);
				}
				tempValues.push(newItem);
				this._setValueOrValues(parentInfo.parent, parentInfo.attribute, tempValues, false);
				pInfo.newValue = this.getValues(parentInfo.parent, parentInfo.attribute);
			}else{
				this._setValueOrValues(parentInfo.parent, parentInfo.attribute, newItem, false);
				pInfo.newValue = newItem;
			}
		}else{
			//Toplevel item, add to both top list as well as all list.
			newItem[this._rootItemPropName]=true;
			this._arrayOfTopLevelItems.push(newItem);
		}

		this._pending._newItems[newIdentity] = newItem;

		//Clone over the properties to the new item
		for(var key in keywordArgs){
			if(key === this._storeRefPropName || key === this._itemNumPropName){
				// Bummer, the user is trying to do something like
				// newItem({_S:"foo"}).  Unfortunately, our superclass,
				// ItemFileReadStore, is already using _S in each of our items
				// to hold private info.  To avoid a naming collision, we
				// need to move all our private info to some other property
				// of all the items/objects.  So, we need to iterate over all
				// the items and do something like:
				//	  item.__S = item._S;
				//	  item._S = undefined;
				// But first we have to make sure the new "__S" variable is
				// not in use, which means we have to iterate over all the
				// items checking for that.
				throw new Error("encountered bug in ItemFileWriteStore.newItem");
			}
			var value = keywordArgs[key];
			if(!lang.isArray(value)){
				value = [value];
			}
			newItem[key] = value;
			if(this.referenceIntegrity){
				for(var i = 0; i < value.length; i++){
					var val = value[i];
					if(this.isItem(val)){
						this._addReferenceToMap(val, newItem, key);
					}
				}
			}
		}
		this.onNew(newItem, pInfo); // dojo/data/api/Notification call
		return newItem; // item
	},

	_removeArrayElement: function(/* Array */ array, /* anything */ element){
		var index = arrayUtil.indexOf(array, element);
		if(index != -1){
			array.splice(index, 1);
			return true;
		}
		return false;
	},

	deleteItem: function(/* dojo/data/api/Item */ item){
		// summary:
		//		See dojo/data/api/Write.deleteItem()
		this._assert(!this._saveInProgress);
		this._assertIsItem(item);

		// Remove this item from the _arrayOfAllItems, but leave a null value in place
		// of the item, so as not to change the length of the array, so that in newItem()
		// we can still safely do: newIdentity = this._arrayOfAllItems.length;
		var indexInArrayOfAllItems = item[this._itemNumPropName];
		var identity = this.getIdentity(item);

		//If we have reference integrity on, we need to do reference cleanup for the deleted item
		if(this.referenceIntegrity){
			//First scan all the attributes of this items for references and clean them up in the map
			//As this item is going away, no need to track its references anymore.

			//Get the attributes list before we generate the backup so it
			//doesn't pollute the attributes list.
			var attributes = this.getAttributes(item);

			//Backup the map, we'll have to restore it potentially, in a revert.
			if(item[this._reverseRefMap]){
				item["backup_" + this._reverseRefMap] = lang.clone(item[this._reverseRefMap]);
			}

			//TODO:  This causes a reversion problem.  This list won't be restored on revert since it is
			//attached to the 'value'. item, not ours.  Need to back tese up somehow too.
			//Maybe build a map of the backup of the entries and attach it to the deleted item to be restored
			//later.  Or just record them and call _addReferenceToMap on them in revert.
			arrayUtil.forEach(attributes, function(attribute){
				arrayUtil.forEach(this.getValues(item, attribute), function(value){
					if(this.isItem(value)){
						//We have to back up all the references we had to others so they can be restored on a revert.
						if(!item["backupRefs_" + this._reverseRefMap]){
							item["backupRefs_" + this._reverseRefMap] = [];
						}
						item["backupRefs_" + this._reverseRefMap].push({id: this.getIdentity(value), attr: attribute});
						this._removeReferenceFromMap(value, item, attribute);
					}
				}, this);
			}, this);

			//Next, see if we have references to this item, if we do, we have to clean them up too.
			var references = item[this._reverseRefMap];
			if(references){
				//Look through all the items noted as references to clean them up.
				for(var itemId in references){
					var containingItem = null;
					if(this._itemsByIdentity){
						containingItem = this._itemsByIdentity[itemId];
					}else{
						containingItem = this._arrayOfAllItems[itemId];
					}
					//We have a reference to a containing item, now we have to process the
					//attributes and clear all references to the item being deleted.
					if(containingItem){
						for(var attribute in references[itemId]){
							var oldValues = this.getValues(containingItem, attribute) || [];
							var newValues = arrayUtil.filter(oldValues, function(possibleItem){
								return !(this.isItem(possibleItem) && this.getIdentity(possibleItem) == identity);
							}, this);
							//Remove the note of the reference to the item and set the values on the modified attribute.
							this._removeReferenceFromMap(item, containingItem, attribute);
							if(newValues.length < oldValues.length){
								this._setValueOrValues(containingItem, attribute, newValues, true);
							}
						}
					}
				}
			}
		}

		this._arrayOfAllItems[indexInArrayOfAllItems] = null;

		item[this._storeRefPropName] = null;
		if(this._itemsByIdentity){
			delete this._itemsByIdentity[identity];
		}
		this._pending._deletedItems[identity] = item;

		//Remove from the toplevel items, if necessary...
		if(item[this._rootItemPropName]){
			this._removeArrayElement(this._arrayOfTopLevelItems, item);
		}
		this.onDelete(item); // dojo/data/api/Notification call
		return true;
	},

	setValue: function(/* dojo/data/api/Item */ item, /* attribute-name-string */ attribute, /* almost anything */ value){
		// summary:
		//		See dojo/data/api/Write.set()
		return this._setValueOrValues(item, attribute, value, true); // boolean
	},

	setValues: function(/* dojo/data/api/Item */ item, /* attribute-name-string */ attribute, /* array */ values){
		// summary:
		//		See dojo/data/api/Write.setValues()
		return this._setValueOrValues(item, attribute, values, true); // boolean
	},

	unsetAttribute: function(/* dojo/data/api/Item */ item, /* attribute-name-string */ attribute){
		// summary:
		//		See dojo/data/api/Write.unsetAttribute()
		return this._setValueOrValues(item, attribute, [], true);
	},

	_setValueOrValues: function(/* dojo/data/api/Item */ item, /* attribute-name-string */ attribute, /* anything */ newValueOrValues, /*boolean?*/ callOnSet){
		this._assert(!this._saveInProgress);

		// Check for valid arguments
		this._assertIsItem(item);
		this._assert(lang.isString(attribute));
		this._assert(typeof newValueOrValues !== "undefined");

		// Make sure the user isn't trying to change the item's identity
		var identifierAttribute = this._getIdentifierAttribute();
		if(attribute == identifierAttribute){
			throw new Error("ItemFileWriteStore does not have support for changing the value of an item's identifier.");
		}

		// To implement the Notification API, we need to make a note of what
		// the old attribute value was, so that we can pass that info when
		// we call the onSet method.
		var oldValueOrValues = this._getValueOrValues(item, attribute);

		var identity = this.getIdentity(item);
		if(!this._pending._modifiedItems[identity]){
			// Before we actually change the item, we make a copy of it to
			// record the original state, so that we'll be able to revert if
			// the revert method gets called.  If the item has already been
			// modified then there's no need to do this now, since we already
			// have a record of the original state.
			var copyOfItemState = {};
			for(var key in item){
				if((key === this._storeRefPropName) || (key === this._itemNumPropName) || (key === this._rootItemPropName)){
					copyOfItemState[key] = item[key];
				}else if(key === this._reverseRefMap){
					copyOfItemState[key] = lang.clone(item[key]);
				}else{
					copyOfItemState[key] = item[key].slice(0, item[key].length);
				}
			}
			// Now mark the item as dirty, and save the copy of the original state
			this._pending._modifiedItems[identity] = copyOfItemState;
		}

		// Okay, now we can actually change this attribute on the item
		var success = false;

		if(lang.isArray(newValueOrValues) && newValueOrValues.length === 0){

			// If we were passed an empty array as the value, that counts
			// as "unsetting" the attribute, so we need to remove this
			// attribute from the item.
			success = delete item[attribute];
			newValueOrValues = undefined; // used in the onSet Notification call below

			if(this.referenceIntegrity && oldValueOrValues){
				var oldValues = oldValueOrValues;
				if(!lang.isArray(oldValues)){
					oldValues = [oldValues];
				}
				for(var i = 0; i < oldValues.length; i++){
					var value = oldValues[i];
					if(this.isItem(value)){
						this._removeReferenceFromMap(value, item, attribute);
					}
				}
			}
		}else{
			var newValueArray;
			if(lang.isArray(newValueOrValues)){
				// Unfortunately, it's not safe to just do this:
				//	  newValueArray = newValueOrValues;
				// Instead, we need to copy the array, which slice() does very nicely.
				// This is so that our internal data structure won't
				// get corrupted if the user mucks with the values array *after*
				// calling setValues().
				newValueArray = newValueOrValues.slice(0, newValueOrValues.length);
			}else{
				newValueArray = [newValueOrValues];
			}

			//We need to handle reference integrity if this is on.
			//In the case of set, we need to see if references were added or removed
			//and update the reference tracking map accordingly.
			if(this.referenceIntegrity){
				if(oldValueOrValues){
					var oldValues = oldValueOrValues;
					if(!lang.isArray(oldValues)){
						oldValues = [oldValues];
					}
					//Use an associative map to determine what was added/removed from the list.
					//Should be O(n) performant.  First look at all the old values and make a list of them
					//Then for any item not in the old list, we add it.  If it was already present, we remove it.
					//Then we pass over the map and any references left it it need to be removed (IE, no match in
					//the new values list).
					var map = {};
					arrayUtil.forEach(oldValues, function(possibleItem){
						if(this.isItem(possibleItem)){
							var id = this.getIdentity(possibleItem);
							map[id.toString()] = true;
						}
					}, this);
					arrayUtil.forEach(newValueArray, function(possibleItem){
						if(this.isItem(possibleItem)){
							var id = this.getIdentity(possibleItem);
							if(map[id.toString()]){
								delete map[id.toString()];
							}else{
								this._addReferenceToMap(possibleItem, item, attribute);
							}
						}
					}, this);
					for(var rId in map){
						var removedItem;
						if(this._itemsByIdentity){
							removedItem = this._itemsByIdentity[rId];
						}else{
							removedItem = this._arrayOfAllItems[rId];
						}
						this._removeReferenceFromMap(removedItem, item, attribute);
					}
				}else{
					//Everything is new (no old values) so we have to just
					//insert all the references, if any.
					for(var i = 0; i < newValueArray.length; i++){
						var value = newValueArray[i];
						if(this.isItem(value)){
							this._addReferenceToMap(value, item, attribute);
						}
					}
				}
			}
			item[attribute] = newValueArray;
			success = true;
		}

		// Now we make the dojo/data/api/Notification call
		if(callOnSet){
			this.onSet(item, attribute, oldValueOrValues, newValueOrValues);
		}
		return success; // boolean
	},

	_addReferenceToMap: function(/* dojo/data/api/Item */ refItem, /* dojo/data/api/Item */ parentItem, /* string */ attribute){
		// summary:
		//		Method to add an reference map entry for an item and attribute.
		// description:
		//		Method to add an reference map entry for an item and attribute.
		// refItem:
		//		The item that is referenced.
		// parentItem:
		//		The item that holds the new reference to refItem.
		// attribute:
		//		The attribute on parentItem that contains the new reference.

		var parentId = this.getIdentity(parentItem);
		var references = refItem[this._reverseRefMap];

		if(!references){
			references = refItem[this._reverseRefMap] = {};
		}
		var itemRef = references[parentId];
		if(!itemRef){
			itemRef = references[parentId] = {};
		}
		itemRef[attribute] = true;
	},

	_removeReferenceFromMap: function(/* dojo/data/api/Item */ refItem, /* dojo/data/api/Item */ parentItem, /* string */ attribute){
		// summary:
		//		Method to remove an reference map entry for an item and attribute.
		// description:
		//		Method to remove an reference map entry for an item and attribute.  This will
		//		also perform cleanup on the map such that if there are no more references at all to
		//		the item, its reference object and entry are removed.
		// refItem:
		//		The item that is referenced.
		// parentItem:
		//		The item holding a reference to refItem.
		// attribute:
		//		The attribute on parentItem that contains the reference.
		var identity = this.getIdentity(parentItem);
		var references = refItem[this._reverseRefMap];
		var itemId;
		if(references){
			for(itemId in references){
				if(itemId == identity){
					delete references[itemId][attribute];
					if(this._isEmpty(references[itemId])){
						delete references[itemId];
					}
				}
			}
			if(this._isEmpty(references)){
				delete refItem[this._reverseRefMap];
			}
		}
	},

	_dumpReferenceMap: function(){
		// summary:
		//		Function to dump the reverse reference map of all items in the store for debug purposes.
		// description:
		//		Function to dump the reverse reference map of all items in the store for debug purposes.
		var i;
		for(i = 0; i < this._arrayOfAllItems.length; i++){
			var item = this._arrayOfAllItems[i];
			if(item && item[this._reverseRefMap]){
				console.log("Item: [" + this.getIdentity(item) + "] is referenced by: " + jsonUtil.toJson(item[this._reverseRefMap]));
			}
		}
	},

	_getValueOrValues: function(/* dojo/data/api/Item */ item, /* attribute-name-string */ attribute){
		var valueOrValues = undefined;
		if(this.hasAttribute(item, attribute)){
			var valueArray = this.getValues(item, attribute);
			if(valueArray.length == 1){
				valueOrValues = valueArray[0];
			}else{
				valueOrValues = valueArray;
			}
		}
		return valueOrValues;
	},

	_flatten: function(/* anything */ value){
		if(this.isItem(value)){
			// Given an item, return an serializable object that provides a
			// reference to the item.
			// For example, given kermit:
			//	  var kermit = store.newItem({id:2, name:"Kermit"});
			// we want to return
			//	  {_reference:2}
			return {_reference: this.getIdentity(value)};
		}else{
			if(typeof value === "object"){
				for(var type in this._datatypeMap){
					var typeMap = this._datatypeMap[type];
					if(lang.isObject(typeMap) && !lang.isFunction(typeMap)){
						if(value instanceof typeMap.type){
							if(!typeMap.serialize){
								throw new Error("ItemFileWriteStore:  No serializer defined for type mapping: [" + type + "]");
							}
							return {_type: type, _value: typeMap.serialize(value)};
						}
					}else if(value instanceof typeMap){
						//SImple mapping, therefore, return as a toString serialization.
						return {_type: type, _value: value.toString()};
					}
				}
			}
			return value;
		}
	},

	_getNewFileContentString: function(){
		// summary:
		//		Generate a string that can be saved to a file.
		//		The result should look similar to:
		//		http://trac.dojotoolkit.org/browser/dojo/trunk/tests/data/countries.json
		var serializableStructure = {};

		var identifierAttribute = this._getIdentifierAttribute();
		if(identifierAttribute !== Number){
			serializableStructure.identifier = identifierAttribute;
		}
		if(this._labelAttr){
			serializableStructure.label = this._labelAttr;
		}
		serializableStructure.items = [];
		for(var i = 0; i < this._arrayOfAllItems.length; ++i){
			var item = this._arrayOfAllItems[i];
			if(item !== null){
				var serializableItem = {};
				for(var key in item){
					if(key !== this._storeRefPropName && key !== this._itemNumPropName && key !== this._reverseRefMap && key !== this._rootItemPropName){
						var valueArray = this.getValues(item, key);
						if(valueArray.length == 1){
							serializableItem[key] = this._flatten(valueArray[0]);
						}else{
							var serializableArray = [];
							for(var j = 0; j < valueArray.length; ++j){
								serializableArray.push(this._flatten(valueArray[j]));
								serializableItem[key] = serializableArray;
							}
						}
					}
				}
				serializableStructure.items.push(serializableItem);
			}
		}
		var prettyPrint = true;
		return jsonUtil.toJson(serializableStructure, prettyPrint);
	},

	_isEmpty: function(something){
		// summary:
		//		Function to determine if an array or object has no properties or values.
		// something:
		//		The array or object to examine.
		var empty = true;
		if(lang.isObject(something)){
			var i;
			for(i in something){
				empty = false;
				break;
			}
		}else if(lang.isArray(something)){
			if(something.length > 0){
				empty = false;
			}
		}
		return empty; //boolean
	},

	save: function(/* object */ keywordArgs){
		// summary:
		//		See dojo/data/api/Write.save()
		this._assert(!this._saveInProgress);

		// this._saveInProgress is set to true, briefly, from when save is first called to when it completes
		this._saveInProgress = true;

		var self = this;
		var saveCompleteCallback = function(){
			self._pending = {
				_newItems:{},
				_modifiedItems:{},
				_deletedItems:{}
			};

			self._saveInProgress = false; // must come after this._pending is cleared, but before any callbacks
			if(keywordArgs && keywordArgs.onComplete){
				var scope = keywordArgs.scope || kernel.global;
				keywordArgs.onComplete.call(scope);
			}
		};
		var saveFailedCallback = function(err){
			self._saveInProgress = false;
			if(keywordArgs && keywordArgs.onError){
				var scope = keywordArgs.scope || kernel.global;
				keywordArgs.onError.call(scope, err);
			}
		};

		if(this._saveEverything){
			var newFileContentString = this._getNewFileContentString();
			this._saveEverything(saveCompleteCallback, saveFailedCallback, newFileContentString);
		}
		if(this._saveCustom){
			this._saveCustom(saveCompleteCallback, saveFailedCallback);
		}
		if(!this._saveEverything && !this._saveCustom){
			// Looks like there is no user-defined save-handler function.
			// That's fine, it just means the datastore is acting as a "mock-write"
			// store -- changes get saved in memory but don't get saved to disk.
			saveCompleteCallback();
		}
	},

	revert: function(){
		// summary:
		//		See dojo/data/api/Write.revert()
		this._assert(!this._saveInProgress);

		var identity;
		for(identity in this._pending._modifiedItems){
			// find the original item and the modified item that replaced it
			var copyOfItemState = this._pending._modifiedItems[identity];
			var modifiedItem = null;
			if(this._itemsByIdentity){
				modifiedItem = this._itemsByIdentity[identity];
			}else{
				modifiedItem = this._arrayOfAllItems[identity];
			}

			// Restore the original item into a full-fledged item again, we want to try to
			// keep the same object instance as if we don't it, causes bugs like #9022.
			copyOfItemState[this._storeRefPropName] = this;
			for(var key in modifiedItem){
				delete modifiedItem[key];
			}
			lang.mixin(modifiedItem, copyOfItemState);
		}
		var deletedItem;
		for(identity in this._pending._deletedItems){
			deletedItem = this._pending._deletedItems[identity];
			deletedItem[this._storeRefPropName] = this;
			var index = deletedItem[this._itemNumPropName];

			//Restore the reverse refererence map, if any.
			if(deletedItem["backup_" + this._reverseRefMap]){
				deletedItem[this._reverseRefMap] = deletedItem["backup_" + this._reverseRefMap];
				delete deletedItem["backup_" + this._reverseRefMap];
			}
			this._arrayOfAllItems[index] = deletedItem;
			if(this._itemsByIdentity){
				this._itemsByIdentity[identity] = deletedItem;
			}
			if(deletedItem[this._rootItemPropName]){
				this._arrayOfTopLevelItems.push(deletedItem);
			}
		}
		//We have to pass through it again and restore the reference maps after all the
		//undeletes have occurred.
		for(identity in this._pending._deletedItems){
			deletedItem = this._pending._deletedItems[identity];
			if(deletedItem["backupRefs_" + this._reverseRefMap]){
				arrayUtil.forEach(deletedItem["backupRefs_" + this._reverseRefMap], function(reference){
					var refItem;
					if(this._itemsByIdentity){
						refItem = this._itemsByIdentity[reference.id];
					}else{
						refItem = this._arrayOfAllItems[reference.id];
					}
					this._addReferenceToMap(refItem, deletedItem, reference.attr);
				}, this);
				delete deletedItem["backupRefs_" + this._reverseRefMap];
			}
		}

		for(identity in this._pending._newItems){
			var newItem = this._pending._newItems[identity];
			newItem[this._storeRefPropName] = null;
			// null out the new item, but don't change the array index so
			// so we can keep using _arrayOfAllItems.length.
			this._arrayOfAllItems[newItem[this._itemNumPropName]] = null;
			if(newItem[this._rootItemPropName]){
				this._removeArrayElement(this._arrayOfTopLevelItems, newItem);
			}
			if(this._itemsByIdentity){
				delete this._itemsByIdentity[identity];
			}
		}

		this._pending = {
			_newItems:{},
			_modifiedItems:{},
			_deletedItems:{}
		};
		return true; // boolean
	},

	isDirty: function(/* item? */ item){
		// summary:
		//		See dojo/data/api/Write.isDirty()
		if(item){
			// return true if the item is dirty
			var identity = this.getIdentity(item);
			return new Boolean(this._pending._newItems[identity] ||
				this._pending._modifiedItems[identity] ||
				this._pending._deletedItems[identity]).valueOf(); // boolean
		}else{
			// return true if the store is dirty -- which means return true
			// if there are any new items, dirty items, or modified items
			return !this._isEmpty(this._pending._newItems) ||
				!this._isEmpty(this._pending._modifiedItems) ||
				!this._isEmpty(this._pending._deletedItems); // boolean
		}
	},

/* dojo/data/api/Notification */

	onSet: function(/* dojo/data/api/Item */ item,
					/*attribute-name-string*/ attribute,
					/*object|array*/ oldValue,
					/*object|array*/ newValue){
		// summary:
		//		See dojo/data/api/Notification.onSet()

		// No need to do anything. This method is here just so that the
		// client code can connect observers to it.
	},

	onNew: function(/* dojo/data/api/Item */ newItem, /*object?*/ parentInfo){
		// summary:
		//		See dojo/data/api/Notification.onNew()

		// No need to do anything. This method is here just so that the
		// client code can connect observers to it.
	},

	onDelete: function(/* dojo/data/api/Item */ deletedItem){
		// summary:
		//		See dojo/data/api/Notification.onDelete()

		// No need to do anything. This method is here just so that the
		// client code can connect observers to it.
	},

	close: function(/* object? */ request){
		 // summary:
		 //		Over-ride of base close function of ItemFileReadStore to add in check for store state.
		 // description:
		 //		Over-ride of base close function of ItemFileReadStore to add in check for store state.
		 //		If the store is still dirty (unsaved changes), then an error will be thrown instead of
		 //		clearing the internal state for reload from the url.

		 //Clear if not dirty ... or throw an error
		 if(this.clearOnClose){
			 if(!this.isDirty()){
				 this.inherited(arguments);
			 }else{
				 //Only throw an error if the store was dirty and we were loading from a url (cannot reload from url until state is saved).
				 throw new Error("dojo.data.ItemFileWriteStore: There are unsaved changes present in the store.  Please save or revert the changes before invoking close.");
			 }
		 }
	}
});

});

},
'url:dijit/templates/TreeNode.html':"<div class=\"dijitTreeNode\" role=\"presentation\"\n\t><div data-dojo-attach-point=\"rowNode\" class=\"dijitTreeRow dijitInline\" role=\"presentation\"\n\t\t><div data-dojo-attach-point=\"indentNode\" class=\"dijitInline\"></div\n\t\t><img src=\"${_blankGif}\" alt=\"\" data-dojo-attach-point=\"expandoNode\" class=\"dijitTreeExpando\" role=\"presentation\"\n\t\t/><span data-dojo-attach-point=\"expandoNodeText\" class=\"dijitExpandoText\" role=\"presentation\"\n\t\t></span\n\t\t><span data-dojo-attach-point=\"contentNode\"\n\t\t\tclass=\"dijitTreeContent\" role=\"presentation\">\n\t\t\t<img src=\"${_blankGif}\" alt=\"\" data-dojo-attach-point=\"iconNode\" class=\"dijitIcon dijitTreeIcon\" role=\"presentation\"\n\t\t\t/><span data-dojo-attach-point=\"labelNode\" class=\"dijitTreeLabel\" role=\"treeitem\" tabindex=\"-1\" aria-selected=\"false\"></span>\n\t\t</span\n\t></div>\n\t<div data-dojo-attach-point=\"containerNode\" class=\"dijitTreeContainer\" role=\"presentation\" style=\"display: none;\"></div>\n</div>\n",
'url:dijit/templates/Tree.html':"<div class=\"dijitTree dijitTreeContainer\" role=\"tree\">\n\t<div class=\"dijitInline dijitTreeIndent\" style=\"position: absolute; top: -9999px\" data-dojo-attach-point=\"indentDetector\"></div>\n</div>\n",
'commsy/DivToggle':function(){
define("commsy/DivToggle", [	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/query",
        	"dojo/dom-attr",
        	"dojo/dom-class",
        	"dojo/dom-style",
        	"dojo/_base/lang",
        	"dojo/fx",
        	"dojo/on"], function(declare, BaseClass, Query, DomAttr, DomClass, DomStyle, Lang, FX, On) {
	return declare(BaseClass, {
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function() {
			// get all a-tags with class "divToggle"
			var aNodes = Query("a.divToggle");
			
			dojo.forEach(aNodes, Lang.hitch(this, function(node, index, arr) {
				if (node) {
					// get the div node to toggle
					var customObject = this.getAttrAsObject(node, "data-custom");
					var toggleNode = Query("div#" + customObject.toggleId)[0];
					
					// register clicks
					if (toggleNode) {
						On(node, "click", Lang.hitch(this, function(event) {
							this.onClick(node, toggleNode);
						}));
					}
				}
			}));
		},
		
		onClick: function(triggerNode, toggleNode) {
			
			
			// get state of trigger
			if (DomClass.contains(toggleNode, "hidden")) {
				/* hidden */
				
				// set title of trigger node
				DomAttr.set(triggerNode, "title", this.from_php.translations.common_hide);
				// set new image and alt of img node
				var imgNode = Query("img", triggerNode)[0];
				if (imgNode) {
					DomAttr.set(imgNode, "src", this.from_php.template.tpl_path + "img/btn_close_rc.gif");
					DomAttr.set(imgNode, "alt", this.from_php.translations.common_hide);
				}
				
				DomClass.remove(toggleNode, "hidden");
				DomStyle.set(toggleNode, "height", "0px");
				
				
				// show div
				FX.wipeIn({
					node:		toggleNode
				}).play();
			} else {
				/* not hidden */
				
				// set title of trigger node
				DomAttr.set(triggerNode, "title", this.from_php.translations.common_show);
				
				// set new image and alt of img node
				var imgNode = Query("img", triggerNode)[0];
				if (imgNode) {
					DomAttr.set(imgNode, "src", this.from_php.template.tpl_path + "img/btn_open_rc.gif");
					DomAttr.set(imgNode, "alt", this.from_php.translations.common_show);
				}
				
				// show div
				FX.wipeOut({
					node:		toggleNode,
					onEnd:		function() {
						DomClass.add(toggleNode, "hidden");
					}
				}).play();
			}
		}
	});
});
},
'dojo/cookie':function(){
define("dojo/cookie", ["./_base/kernel", "./regexp"], function(dojo, regexp){

// module:
//		dojo/cookie

/*=====
var __cookieProps = {
	// expires: Date|String|Number?
	//		If a number, the number of days from today at which the cookie
	//		will expire. If a date, the date past which the cookie will expire.
	//		If expires is in the past, the cookie will be deleted.
	//		If expires is omitted or is 0, the cookie will expire when the browser closes.
	// path: String?
	//		The path to use for the cookie.
	// domain: String?
	//		The domain to use for the cookie.
	// secure: Boolean?
	//		Whether to only send the cookie on secure connections
};
=====*/


dojo.cookie = function(/*String*/name, /*String?*/ value, /*__cookieProps?*/ props){
	// summary:
	//		Get or set a cookie.
	// description:
	//		If one argument is passed, returns the value of the cookie
	//		For two or more arguments, acts as a setter.
	// name:
	//		Name of the cookie
	// value:
	//		Value for the cookie
	// props:
	//		Properties for the cookie
	// example:
	//		set a cookie with the JSON-serialized contents of an object which
	//		will expire 5 days from now:
	//	|	require(["dojo/cookie", "dojo/json"], function(cookie, json){
	//	|		cookie("configObj", json.stringify(config, {expires: 5 }));
	//	|	});
	//
	// example:
	//		de-serialize a cookie back into a JavaScript object:
	//	|	require(["dojo/cookie", "dojo/json"], function(cookie, json){
	//	|		config = json.parse(cookie("configObj"));
	//	|	});
	//
	// example:
	//		delete a cookie:
	//	|	require(["dojo/cookie"], function(cookie){
	//	|		cookie("configObj", null, {expires: -1});
	//	|	});
	var c = document.cookie, ret;
	if(arguments.length == 1){
		var matches = c.match(new RegExp("(?:^|; )" + regexp.escapeString(name) + "=([^;]*)"));
		ret = matches ? decodeURIComponent(matches[1]) : undefined; 
	}else{
		props = props || {};
// FIXME: expires=0 seems to disappear right away, not on close? (FF3)  Change docs?
		var exp = props.expires;
		if(typeof exp == "number"){
			var d = new Date();
			d.setTime(d.getTime() + exp*24*60*60*1000);
			exp = props.expires = d;
		}
		if(exp && exp.toUTCString){ props.expires = exp.toUTCString(); }

		value = encodeURIComponent(value);
		var updatedCookie = name + "=" + value, propName;
		for(propName in props){
			updatedCookie += "; " + propName;
			var propValue = props[propName];
			if(propValue !== true){ updatedCookie += "=" + propValue; }
		}
		document.cookie = updatedCookie;
	}
	return ret; // String|undefined
};

dojo.cookie.isSupported = function(){
	// summary:
	//		Use to determine if the current browser supports cookies or not.
	//
	//		Returns true if user allows cookies.
	//		Returns false if user doesn't allow cookies.

	if(!("cookieEnabled" in navigator)){
		this("__djCookieTest__", "CookiesAllowed");
		navigator.cookieEnabled = this("__djCookieTest__") == "CookiesAllowed";
		if(navigator.cookieEnabled){
			this("__djCookieTest__", "", {expires: -1});
		}
	}
	return navigator.cookieEnabled;
};

return dojo.cookie;
});

},
'cbtree/Tree':function(){
require({cache:{
'url:cbtree/templates/cbtreeNode.html':"<div class=\"dijitTreeNode\" role=\"presentation\">\n\t<div data-dojo-attach-point=\"rowNode\" class=\"dijitTreeRow dijitInline\" role=\"presentation\">\n\t\t<div data-dojo-attach-point=\"indentNode\" class=\"dijitInline\"></div>\n\t\t<img src=\"${_blankGif}\" alt=\"\" data-dojo-attach-point=\"expandoNode\"class=\"dijitTreeExpando\" role=\"presentation\" />\n\t\t<span data-dojo-attach-point=\"expandoNodeText\" class=\"dijitExpandoText\" role=\"presentation\"></span>\n\t\t<span data-dojo-attach-point=\"checkBoxNode\" class=\"cbtreeCheckBox\" role=\"presentation\"></span>\n\t\t<span data-dojo-attach-point=\"contentNode\" class=\"dijitTreeContent\" role=\"presentation\">\n\t\t\t<img src=\"${_blankGif}\" alt=\"\" data-dojo-attach-point=\"iconNode\" class=\"dijitIcon dijitTreeIcon\" role=\"presentation\"/>\n\t\t\t<span data-dojo-attach-point=\"labelNode\" class=\"dijitTreeLabel\" role=\"treeitem\" tabindex=\"-1\" aria-selected=\"false\"></span>\n\t\t</span>\n\t</div>\n\t<div data-dojo-attach-point=\"containerNode\" class=\"dijitTreeContainer\" role=\"presentation\" style=\"display: none;\"></div>\n</div>\n"}});
//
// Copyright (c) 2010-2012, Peter Jekel
// All rights reserved.
//
//	The Checkbox Tree (cbtree), also known as the 'Dijit Tree with Multi State Checkboxes'
//	is released under to following three licenses:
//
//	1 - BSD 2-Clause							 (http://thejekels.com/cbtree/LICENSE)
//	2 - The "New" BSD License			 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L13)
//	3 - The Academic Free License	 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L43)
//
//	In case of doubt, the BSD 2-Clause license takes precedence.
//
define("cbtree/Tree", [
	"dojo/_base/array",
	"dojo/_base/declare",
	"dojo/_base/event",
	"dojo/_base/lang", 
	"dojo/DeferredList",
	"dojo/dom-construct",
	"dojo/text!./templates/cbtreeNode.html",
	"dijit/_Container",
	"dijit/registry",
	"dijit/Tree",
	"./CheckBox",
	"./models/_dndSelector",  // Fixed dijit tree issue...
	"require"
], function (array, declare, event, lang, DeferredList, domConstruct, NodeTemplate, 
							_Container, registry, Tree, CheckBox, _dndSelector, require) {

	// module:
	//		cbtree/Tree
	// note:
	//		This implementation is compatible with dojo 1.8

	var TreeNode = declare([Tree._TreeNode], {
		// templateString: String
		//		Specifies the HTML template to be used.
		templateString: NodeTemplate,

		moduleName: "cbTree/_TreeNode",
		
		// _checkBox: [private] widget 
		//		Checkbox or custome widget instance.
		_checkBox: null,

		// _toggle: [private] Boolean
		//		Indicates if the checkbox widget supports the toggle function.
		_toggle: true,
		
		// _widget: [private] Object
		//		Specifies the widget to be instanciated for the tree node. The default
		//		is the cbtree CheckBox widget.
		_widget: null,
		
		constructor: function (args){
			// summary:
			//		If a custom widget is specified, it is used instead of the default
			//		cbtree checkbox. Any optional arguments are appended to the default
			//		widget argument list.

			var checkBoxWidget = { type: CheckBox, target: 'INPUT', mixin: null, postCreate: null };
			var widgetArgs		 = { multiState: null, checked: undefined, value: 'on' };
			var customWidget	 = args.widget;

			if (customWidget) {
				lang.mixin( widgetArgs, customWidget.args );
				lang.mixin(checkBoxWidget, customWidget);
			}
			checkBoxWidget.args = widgetArgs;
			
			// Test if the widget supports the toggle() method.
			this._toggle = lang.isFunction (checkBoxWidget.type.prototype.toggle);
			this._widget = checkBoxWidget;
		},

		_createCheckBox: function (/*Boolean*/ multiState) {
			// summary:
			//		Create a checkbox on the TreeNode if a checkbox style is specified.
			// description:
			//		Create a checkbox on the tree node. A checkbox is only created if
			//		the data item has a valid 'checked' attribute OR the model has the
			//		'checkboxAll' attribute enabled.
			//
			// multiState:
			//			Indicate of multi state checkboxes are to be used (true/false).
			// tags:
			//		private

			var itemState = this.tree.model.getItemState(this.item);
			var checked   = itemState.checked;
			var enabled   = itemState.enabled;
			var widget	  = this._widget;
			var args		  = widget.args;
			
			if (checked !== undefined) {
				// Initialize the default checkbox/widget attributes.
				args.multiState = multiState;
				args.checked		= checked;
				args.value			= this.label;

				if (lang.isFunction(widget.mixin)) {
					lang.hitch(this, widget.mixin)(args);
				}

				this._checkBox = new widget.type( args );
				if (this._checkBox) {
					if (lang.isFunction(this._widget.postCreate)) {
						lang.hitch(this._checkBox, this._widget.postCreate)(this);
					}
					domConstruct.place(this._checkBox.domNode, this.checkBoxNode, 'replace');
				}
			}
			if (this._checkBox) {
				if (this.isExpandable) {
          if (this.tree.branchReadOnly || !enabled) {
            this._checkBox.set("readOnly", true);
          }
				} else {
          if (this.tree.leafReadOnly || !enabled) {
            this._checkBox.set("readOnly", true);
          }
				}
			}
		},

		_getCheckedAttr: function () {
			// summary:
			//		Get the current checkbox state. This method provides the hook for
			//		get("checked").
			// tags:
			//		private
			
			if (this._checkBox) {
				return this.tree.model.getChecked(this.item);
			}
		},

		_set_checked_Attr: function (newState) {
			// summary:
			//		Set a new state for the tree node checkbox. This method handles the
			//		internal '_checked_' events generated by the model in which case we
			//		only need to update the checkbox.
			//	newState:
			//		The checked state: 'mixed', true or false.
			// tags:
			//		private
			if (this._checkBox) {
				this._checkBox.set("checked", newState);
			}
		},
		
		_setCheckedAttr: function (/*String|Boolean*/ newState) {
			// summary:
			//		Set a new state for the tree node checkbox. This method implements
			//		the set("checked", newState). These requests are recieved from the
			//		API and therefore we need to inform the model.
			//	newState:
			//		The checked state: 'mixed', true or false.
			// tags:
			//		private

			if (this._checkBox) {
				return this.tree.model.setChecked(this.item, newState);
			}
		},

		_getEnabledAttr: function () {
			// summary:
			//		Get the current 'enabled' state of the item associated with this
			//		tree node. This method provides the hook for get("enabled").
			// tag:
			//		Private
			return this.tree.model.getEnabled(this.item);
		},
		
		_set_enabled_Attr: function (enabled) {
			// summary:
			//		Set the 'Read Only' property of the checkbox. This method handles
			//		the internal '_enabled_' event generated by the model after the
			//		store update.
			//	enabled:
			//		The new enabled state.
			// tags:
			//		private
      this._checkBox.set("readOnly", !enabled);
		},

		_setEnabledAttr: function (/*Boolean*/ newState) {
			// summary:
			//		Set the new 'enabled' state of the item associated with this tree
			//		node. This method provides the hook for set("enabled", newState).
			// newState:
			//		Boolean, true or false.
			// tag:
			//		Private.
			return this.tree.model.setEnabled(this.item, newState);
		},
		
		_toggleCheckBox: function (){
			// summary:
			//		Toggle the current checkbox checked attribute and update the model
			//		accordingly. Typically called when the spacebar is pressed. 
			//		If a custom widget does not support toggle() we will just mimic it.
			// tags:
			//		private

			var newState, oldState;
			if (this._checkBox) {
				if (this._toggle) {
					newState = this._checkBox.toggle();
				} else {
					oldState = this._checkBox.get("checked");
					newState = (oldState == "mixed" ? true : !oldState);
				}
				this.tree.model.setChecked(this.item, newState);
			}
			return newState;
		},
		
		destroy: function () {
			// summary:
			//		Destroy the checkbox of the tree node widget.
			//
			if (this._checkbox) {
				this._checkbox.destroy();
				delete this._checkbox;
			}
			this.inherited(arguments);
		},

		postCreate: function () {
			// summary:
			//		Handle the creation of the checkbox and node specific icons after
			//		the tree node has been instanciated.
			// description:
			//		Handle the creation of the checkbox after the tree node has been
			//		instanciated. If the item has a custom icon specified, overwrite
			//		the current icon.
			//
			var tree	= this.tree,
					itemIcon = null,
					nodeIcon;

			if (tree.checkBoxes === true) {
				this._createCheckBox(tree._multiState);
			}
			// If Tree styling is loaded and the model has its iconAttr set go see if
			// there is a custom icon amongst the item attributes.
			if (tree._hasStyling && tree._iconAttr) {
				var itemIcon = tree.get("icon", this.item);
				if (itemIcon) {
					this.set("_icon_",itemIcon);
				}
			}
			// Just in case one is available, set the tooltip.
			this.set("tooltip", this.title);
			this.inherited(arguments);
		},

		setChildItems: function(/* Object[] */ items){
			// summary:
			//		Sets the child items of this node, removing/adding nodes
			//		from current children to match specified items[] array.
			//		Also, if this.persist == true, expands any children that were previously
			//		opened.
			// returns:
			//		Deferred object that fires after all previously opened children
			//		have been expanded again (or fires instantly if there are no such children).

			var tree = this.tree,
				model = tree.model,
				defs = [];	// list of deferreds that need to fire before I am complete

			// Orphan all my existing children.
			// If items contains some of the same items as before then we will reattach them.
			// Don't call this.removeChild() because that will collapse the tree etc.
			var oldChildren = this.getChildren();
			array.forEach(oldChildren, function(child){
				_Container.prototype.removeChild.call(this, child);
			}, this);

			// All the old children of this TreeNode are subject for destruction if
			//		1) they aren't listed in the new children array (items)
			//		2) they aren't immediately adopted by another node (DnD)
			this.defer(function(){
				array.forEach(oldChildren, function(node){
					if(!node._destroyed && !node.getParent()){
						// If node is in selection then remove it.
						tree.dndController.removeTreeNode(node);

						// Deregister mapping from item id --> this node
						var id = model.getIdentity(node.item),
							ary = tree._itemNodesMap[id];
						if(ary.length == 1){
							delete tree._itemNodesMap[id];
						}else{
							var index = array.indexOf(ary, node);
							if(index != -1){
								ary.splice(index, 1);
							}
						}
						// And finally we can destroy the node
						node.destroyRecursive();
					}
				});
			});

			this.state = "LOADED";

			if(items && items.length > 0){
				this.isExpandable = true;
				// Create _TreeNode widget for each specified tree node, unless one already
				// exists and isn't being used (presumably it's from a DnD move and was recently
				// released
				array.forEach(items, function(item){	// MARKER: REUSE NODE
					var id = model.getIdentity(item),
						existingNodes = tree._itemNodesMap[id],
						node;
					if(existingNodes){
						for(var i=0;i<existingNodes.length;i++){
							// FIX 1 - Don't re-used destroyed nodes, instead clean them up.
							if (!existingNodes[i] || existingNodes[i]._beingDestroyed) {
								existingNodes.splice(i,1);
								if (existingNodes.length == 0) {
									delete tree._itemNodesMap[id];
								}
							} else {
								if(!existingNodes[i].getParent()) {
									node = existingNodes[i];
									node.set('indent', this.indent+1);
									break;
								}
							}
						}
					}
					if(!node){
						node = this.tree._createTreeNode({
							item: item,
							tree: tree,
							isExpandable: model.mayHaveChildren(item),
							label: tree.getLabel(item),
							tooltip: tree.getTooltip(item),
							ownerDocument: tree.ownerDocument,
							dir: tree.dir,
							lang: tree.lang,
							textDir: tree.textDir,
							indent: this.indent + 1
						});
						if(existingNodes){
							existingNodes.push(node);
						}else{
							tree._itemNodesMap[id] = [node];
						}
					}
					this.addChild(node);

					// If node was previously opened then open it again now (this may trigger
					// more data store accesses, recursively)
					if(this.tree.autoExpand || this.tree._state(node)){
						defs.push(tree._expandNode(node));
					}
				}, this);

				// note that updateLayout() needs to be called on each child after
				// _all_ the children exist
				array.forEach(this.getChildren(), function(child){
					child._updateLayout();
				});
			}else{
				// FIX 2 - If no children, delete _expandNodeDeferred if any...
				tree._collapseNode(this);
				this.isExpandable=false;
			}

			if(this._setExpando){
				// change expando to/from dot or + icon, as appropriate
				this._setExpando(false);
			}

			// Set leaf icon or folder icon, as appropriate
			this._updateItemClasses(this.item);

			// On initial tree show, make the selected TreeNode as either the root node of the tree,
			// or the first child, if the root node is hidden
			if(this == tree.rootNode){
				var fc = this.tree.showRoot ? this : this.getChildren()[0];
				if(fc){
					fc.setFocusable(true);
					tree.lastFocused = fc;
				}else{
					// fallback: no nodes in tree so focus on Tree <div> itself
					tree.domNode.setAttribute("tabIndex", "0");
				}
			}

			var def =  new DeferredList(defs);
			this.tree._startPaint(def);		// to reset TreeNode widths after an item is added/removed from the Tree
			return def;		// dojo/_base/Deferred
		}

	});	/* end declare() _TreeNode*/

	return declare([Tree], {

		//==============================
		// Parameters to constructor

		// branchIcons: Boolean
		//		Determines if the FolderOpen/FolderClosed icon or their custom equivalent
		//		is displayed.
		branchIcons: true,

		// branchReadOnly: Boolean
		//		Determines if branch checkboxes are read only. If true, the user must
		//		check/uncheck every child checkbox individually. 
		branchReadOnly: false,
		
		// checkBoxes: String
		//		If true it enables the creation of checkboxes, If a tree node actually
		//		gets a checkbox depends on the configuration of the model. If false no
		//		 checkboxes will be created regardless of the model configuration.
		checkBoxes: true,

		// leafReadOnly: Boolean
		//		Determines if leaf checkboxes are read only. If true, the user can only
		//		check/uncheck branch checkboxes and thus overwriting the per store item
		//		'enabled' features for any store item associated with a tree leaf.
		leafReadOnly: false,
		
		// nodeIcons: Boolean
		//		Determines if the Leaf icon, or its custom equivalent, is displayed.
		nodeIcons: true,

		// FIX: 3 - Force tree to use the modified _dndSelector (see ./models/_dndSelector)
		dndController: _dndSelector,

		// End Parameters to constructor
		//==============================

		moduleName: "cbTree/Tree",

		// _multiState: [private] Boolean
		//		Determines if the checked state needs to be maintained as multi state or
		//		or as a dual state. ({"mixed",true,false} vs {true,false}). Its value is
		//		fetched from the tree model.
		_multiState: true,
		
		// _checkedAttr: [private] String
		//		Attribute name associated with the checkbox checked state of a data item.
		//		The value is retrieved from the models 'checkedAttr' property and added
		//		to the list of model events.
		_checkedAttr: "",
		
		// _customWidget: [private]
		//		A custom widget to be used instead of the cbtree CheckBox widget. Any 
		//		custom widget MUST have a 'checked' property and provide support for 
		//		both the get() and set() methods.
		_customWidget: null,

		// _eventAttrMap: [private] String[]
		//		List of additional events (attribute names) the onItemChange() method
		//		will act upon besides the _checkedAttr property value.	 Any internal
		//		events are pre- and suffixed with an underscore like '_styling_'
		_eventAttrMap: null,

		// _dojoRequired [private] Object
		//		Specifies the minimum and maximum dojo version required to run this
		//		implementation of the cbtree.
		//
		//			vers-required	::= '{' (min-version | max-version | min-version ',' max-version) '}'
		//			min-version		::= version
		//			max-version		::= version
		//			version				::= '{' "major" ':' number ',' "minor" ':' number '}'
		//
		_dojoRequired: { min: {major:1, minor:8}, max: {major:1, minor:9}},

		_assertVersion: function () {
			// summary:
			//		Test if we're running the correct dojo version.
			// tag:
			//		Private
			if (dojo.version) {
				var dojoVer = (dojo.version.major * 10) + dojo.version.minor,
						dojoMax = 999,
						dojoMin = 0;
						
				if (this._dojoRequired) {
					if (this._dojoRequired.min !== undefined) {
						dojoMin = (this._dojoRequired.min.major * 10) + this._dojoRequired.min.minor;
					}
					if (this._dojoRequired.max !== undefined) {
						dojoMax = (this._dojoRequired.max.major * 10) + this._dojoRequired.max.minor;
					}
					if (dojoVer < dojoMin || dojoVer > dojoMax) { 
						throw new Error(this.moduleName+"::_assertVersion(): invalid dojo version.");
					}
				}
			} else {
				throw new Error(this.moduleNmae+"::_assertVersion(): unable to determine dojo version.");
			}
		},
		
		_createTreeNode: function (args) {
			// summary:
			//		Create a new cbtreeTreeNode instance.
			// description:
			//		Create a new cbtreeTreeNode instance.
			// tags:
			//		private

			args["widget"] = this._customWidget;		/* Mixin the custom widget */
			if (this._hasStyling && this._icon) {
				args["icon"] = this._icon;
			}
			return new TreeNode(args);
		},

		_onCheckBoxClick: function (/*TreeNode*/ nodeWidget, /*Boolean|String*/ newState, /*Event*/ evt) {
			// summary:
			//		Translates checkbox click events into commands for the controller
			//		to process.
			// description:
			//		the _onCheckBoxClick function is called whenever a mouse 'click'
			//		on a checkbox is detected. Because the click was on the checkbox
			//		we are not dealing with any node expansion or collapsing here.
			// tags:
			//		private

			var item = nodeWidget.item;
				
			this._publish("checkbox", { item: item, node: nodeWidget, state: newState, evt: evt});
			// Generate events incase any listeners are tuned in...
			this.onCheckBoxClick(item, nodeWidget, evt);
			this.onClick(nodeWidget.item, nodeWidget, evt);
			this.focusNode(nodeWidget);
			event.stop(evt);
		},

		_onClick: function(/*TreeNode*/ nodeWidget, /*Event*/ evt){
			// summary:
			//		Handler for onclick event on a tree node
			// description:
			//		If the click event occured on a checkbox, get the new checkbox checked
			//		state, update the model and generate the checkbox click related events
			//		otherwise pass the event on to the tree as a regular click event.
			// evt:
			//		Event object.
			// tags:
			//		private extension
			var checkedWidget = nodeWidget._widget;

			if (evt.target.nodeName == checkedWidget.target) {
				var newState = nodeWidget._checkBox.get("checked");				
				this.model.setChecked(nodeWidget.item, newState);
				this._onCheckBoxClick(nodeWidget, newState, evt);
			} else {
				this.inherited(arguments);
			}
		},
		
		_onItemChange: function (/*data.Item*/ item, /*String*/ attr, /*AnyType*/ value){
			// summary:
			//		Processes notification of a change to an data item's scalar values and
			//		internally generated events which effect the presentation of an item.
			// description:
			//		Processes notification of a change to a data item's scalar values like
			//		label or checkbox state.	In addition, it also handles internal events
			//		that effect the presentation of an item (see TreeStyling.js)
			//		The model, or internal, attribute name is mapped to a tree node property,
			//		only if a mapping is available is the event passed on to the appropriate
			//		tree node otherwise the event is considered of no impact to the tree
			//		presentation.
			// item:
			//		A valid data item
			// attr:
			//		Attribute/event name
			// value:
			//		New value of the item attribute
			// tags:
			//		private extension
 
			var nodeProp = this._eventAttrMap[attr];
			if (nodeProp) {
				var identity = this.model.getIdentity(item),
						nodes		= this._itemNodesMap[identity],
						request	= {};

				if (nodes){
					if (nodeProp.value) {
						if (lang.isFunction(nodeProp.value)) {
							request[nodeProp.attribute] = lang.hitch(this, nodeProp.value)(item, nodeProp.attribute, value);
						} else {
							request[nodeProp.attribute] = nodeProp.value;
						}
					} else {
						request[nodeProp.attribute] = value;
					}
					array.forEach(nodes, function (node){
							node.set(request);
						}, this);
				}
			}
		},

		_onKeyPress: function (/*Event*/ evt){
			// summary:
			//		Toggle the checkbox state when the user pressed the spacebar.
			// description:
			//		Toggle the checkbox state when the user pressed the spacebar.
			//		The spacebar is only processed if the widget that has focus is
			//		a tree node and has a checkbox.
			// tags:
			//		private extension

			if (!evt.altKey) {
				var treeNode = registry.getEnclosingWidget(evt.target);
				if (lang.isString(evt.charOrCode) && (evt.charOrCode == ' ')) {
					treeNode._toggleCheckBox();
				}
			}
			this.inherited(arguments);	/* Pass it on to the parent tree... */
		},

		_onLabelChange: function (/*String*/ oldValue, /*String*/ newValue) {
			// summary:
			//		Handler called when the model changed its label attribute property.
			//		Map the new label attribute to "label"
			// tags:
			//		private

			this.mapEventToAttr(oldValue, newValue, "label");
		},
		
		_setWidgetAttr: function (/*String|Function|Object*/ widget) {
			// summary:
			//		Set the custom widget. This method is the hook for set("widget",widget).
			// description:
			//		Set the custom widget. A valid widget MUST have a 'checked' property
			//		AND methods get() and set() otherwise the widget is rejected and an
			//		error is thrown. If valid, the widget is used instead of the default
			//		cbtree checkbox.
			// widget: 
			//		An String, object or function. In case of an object, the object can
			//		have the following properties:
			//			type			:	Function | String, the widget constructor or a module Id string
			//			args			:	Object, arguments passed to the constructor (optional)
			//			target		:	String, mouse click target nodename (optional)
			//			mixin		 :	Function, called prior to widget instantiation.
			//			postCreate: Function, called after widget instantiation
			// tag:
			//		experimental
			var customWidget = widget,
					property = "checked",
					message,
					proto;

			if (lang.isString(widget)) {
				return this._setWidgetAttr({ type: widget });
			}

			if (lang.isObject(widget) && widget.hasOwnProperty("type")) {
				customWidget = widget.type;
				if (lang.isFunction (customWidget)) {
					proto = customWidget.prototype;
					if (proto && typeof proto[property] !== "undefined"){
						// See if the widget has a getter and setter methods...
						if (lang.isFunction (proto.get) && lang.isFunction (proto.set)) {
							this._customWidget = widget;
							return;
						} else {
							message = "Widget does not support get() and/or set()";
						}
					} else {
						message = "widget MUST have a 'checked' property";
					}
				}else{
					// Test for module id string to support declarative definition of tree
					if (lang.isString(customWidget) && ~customWidget.indexOf('/')) {
						var self = this;
							require([customWidget], function(newWidget) {
								widget.type = newWidget;
								self._setWidgetAttr( widget );
							});
							return;
					}
					message = "argument is not a valid module id";
				}
			} else {
				message = "Object is missing required 'type' property";
			}
			throw new Error(this.moduleName+"::_setWidgetAttr(): " + message);
		},

		create: function() {
			this._assertVersion();
			this.inherited(arguments);
		},
		
		destroy: function() {
			this.model = null;
			this.inherited(arguments);
		},
		
		getIconStyle:function (/*data.item*/ item, /*Boolean*/ opened) {
			// summary:
			//		Return the DOM style for the node Icon. 
			// item:
			//		A valid data item
			// opened:
			//		Indicates if the tree node is expanded.
			// tags:
			//		extension
			var isExpandable = this.model.mayHaveChildren(item);
			var style = this.inherited(arguments) || {};

			if (isExpandable) {
				if (!this.branchIcons) {
					style["display"] = "none";
				}
			} else {
				if (!this.nodeIcons) {
					style["display"] = "none";
				}
			}
			return style;
		},

		mixinEvent: function (/*data.Item*/ item, /*String*/ event, /*AnyType*/ value) {
			// summary:
			//		Mixin a user generated event into the tree event stream. This method
			//		allows users to inject events as if they came from the model.
			// item:
			//		A valid data item
			// event:
			//		Event/attribute name. An entry in the event mapping table must be present.
			//		(see mapEventToAttr())
			// value:
			//		Value to be assigned to the mapped _TreeNode attribute.
			// tag:
			//		public
			
			if (this.model.isItem(item) && this._eventAttrMap[event]) {
				this._onItemChange(item, event, value);
				this.onEvent(item, event, value);
			}
		},

		onCheckBoxClick: function (/*data.item*/ item, /*treeNode*/ treeNode, /*Event*/ evt) {
			// summary:
			//		Callback when a checkbox on a tree node is clicked.
			// tags:
			//		callback
		},
		
		onEvent: function (/*===== item, event, value =====*/) {
			// summary:
			//		Callback when an event was succesfully mixed in.
			// item:
			//		A valid data item
			// event:
			//		Event/attribute name.
			// value:
			//		Value assigned to the mapped _TreeNode attribute.
			// tags:
			//		callback
		},

		postMixInProperties: function(){
			this._eventAttrMap = {};		/* Create event mapping object */

			this.inherited(arguments);
		},

		postCreate: function () {
			// summary:
			//		Handle any specifics related to the tree and model after the
			//		instanciation of the Tree. 
			// description:
			//		Whenever checkboxes are requested Validate if we have a model
			//		capable of updating item attributes.
			var model = this.model;

			if (this.model) {
				if (this.checkBoxes === true) {
					if (!this._modelOk()) {
						throw new Error(this.moduleName+"::postCreate(): model does not support getChecked() and/or setChecked().");
					}
					this._multiState	= model.multiState;
					this._checkedAttr = model.checkedAttr;

					// Add item attributes and other attributes of interest to the mapping
					// table. Checkbox checked events from the model are mapped to the 
					// internal '_checked_' event so a Tree node is able to distinguesh
					// between events coming from the model and those coming from the API
					// like set("checked",true)
					
					this.mapEventToAttr(null,(this._checkedAttr || "checked"), "_checked_");
					model.validateData();
				}
				// Monitor any changes to the models label attribute and add the current
				// 'label' and 'enabled' attribute to the mapping table.
				this.connect(model, "onLabelChange", "_onLabelChange");

				this.mapEventToAttr(null, model.get("enabledAttr"), "_enabled_");
				this.mapEventToAttr(null, model.get("labelAttr"), "label");

				this.inherited(arguments);
			} 
			else // The CheckBox Tree requires a model.
			{
				throw new Error(this.moduleName+"::postCreate(): no model was specified.");
			}
		},
		
		// =======================================================================
		// Misc helper functions/methods

		mapEventToAttr: function (/*String*/ oldAttr, /*String*/ attr, /*String*/ nodeAttr, /*anything?*/ value) {
			// summary:
			//		Add an event mapping to the mapping table.
			//description:
			//		Any event, triggered by the model or some other extension, can be
			//		mapped to a _TreeNode attribute resulting a 'set' request for the
			//		associated _TreeNode attribute.
			// oldAttr:
			//		Original attribute name. If present in the mapping table it is deleted
			//		and replace with 'attr'.
			// attr:
			//		Attribute/event name that needs mapping.
			// nodeAttr:
			//		Name of a _TreeNode attribute to which 'attr' is mapped.
			// value:
			//		If specified the value to be assigned to the _TreeNode attribute. If
			//		value is a function the function is called as: 
			//
			//			function(item, nodeAttr, newValue)
			//
			//		and the result returned is assigned to the _TreeNode attribute.
			
			if (lang.isString(attr) && lang.isString(nodeAttr)) {
				if (attr.length && nodeAttr.length) {
					if (oldAttr) {
						delete this._eventAttrMap[oldAttr];
					}
					this._eventAttrMap[attr] = {attribute: nodeAttr, value: value};
				}
			}
		},

		_modelOk: function () {
			// summary:
			//		Test if the model has the minimum required feature set, that is,
			//		model.getChecked() and model.setChecked().
			// tags:
			//		private

			if ((this.model.getChecked && lang.isFunction( this.model.getChecked )) &&
					(this.model.setChecked && lang.isFunction( this.model.setChecked ))) {
				return true;
			}
			return false;
		}
				
	});	/* end declare() Tree */

});	/* end define() */

},
'url:dijit/form/templates/Button.html':"<span class=\"dijit dijitReset dijitInline\" role=\"presentation\"\n\t><span class=\"dijitReset dijitInline dijitButtonNode\"\n\t\tdata-dojo-attach-event=\"ondijitclick:_onClick\" role=\"presentation\"\n\t\t><span class=\"dijitReset dijitStretch dijitButtonContents\"\n\t\t\tdata-dojo-attach-point=\"titleNode,focusNode\"\n\t\t\trole=\"button\" aria-labelledby=\"${id}_label\"\n\t\t\t><span class=\"dijitReset dijitInline dijitIcon\" data-dojo-attach-point=\"iconNode\"></span\n\t\t\t><span class=\"dijitReset dijitToggleButtonIconChar\">&#x25CF;</span\n\t\t\t><span class=\"dijitReset dijitInline dijitButtonText\"\n\t\t\t\tid=\"${id}_label\"\n\t\t\t\tdata-dojo-attach-point=\"containerNode\"\n\t\t\t></span\n\t\t></span\n\t></span\n\t><input ${!nameAttrSetting} type=\"${type}\" value=\"${value}\" class=\"dijitOffScreen\"\n\t\ttabIndex=\"-1\" role=\"presentation\" data-dojo-attach-point=\"valueNode\"\n/></span>\n",
'cbtree/models/_dndSelector':function(){
define("cbtree/models/_dndSelector", [
	"dojo/_base/array", // array.filter array.forEach array.map
	"dojo/_base/connect", // connect.isCopyKey
	"dojo/_base/declare", // declare
	"dojo/_base/Deferred", // Deferred
	"dojo/_base/kernel",	// global
	"dojo/_base/lang", // lang.hitch
	"dojo/cookie", // cookie
	"dojo/mouse", // mouse.isLeft
	"dojo/on",
	"dojo/touch",
	"./_dndContainer"
], function(array, connect, declare, Deferred, kernel, lang, cookie, mouse, on, touch, _dndContainer){

	// module:
	//		dijit/tree/_dndSelector


	return declare("dijit.tree._dndSelector", _dndContainer, {
		// summary:
		//		This is a base class for `dijit/tree/dndSource` , and isn't meant to be used directly.
		//		It's based on `dojo/dnd/Selector`.
		// tags:
		//		protected

		/*=====
		// selection: Object
		//		(id to DomNode) map for every TreeNode that's currently selected.
		//		The DOMNode is the TreeNode.rowNode.
		selection: {},
		=====*/

		constructor: function(){
			// summary:
			//		Initialization
			// tags:
			//		private

			this.selection={};
			this.anchor = null;

			if(!this.cookieName && this.tree.id){
				this.cookieName = this.tree.id + "SaveSelectedCookie";
			}

			this.events.push(
				on(this.tree.domNode, touch.press, lang.hitch(this,"onMouseDown")),
				on(this.tree.domNode, touch.release, lang.hitch(this,"onMouseUp")),
				on(this.tree.domNode, touch.move, lang.hitch(this,"onMouseMove"))
			);
		},

		// singular: Boolean
		//		Allows selection of only one element, if true.
		//		Tree hasn't been tested in singular=true mode, unclear if it works.
		singular: false,

		// methods
		getSelectedTreeNodes: function(){
			// summary:
			//		Returns a list of selected node(s).
			//		Used by dndSource on the start of a drag.
			// tags:
			//		protected
			var nodes=[], node, sel = this.selection;
			for(var i in sel){
				node = sel[i];
				// FIX 3 - Do NOT include nodes that don't have a DOM node or are destoyed
				//				 instead, delete them from the selection.
				if( node.domNode && !node._destroyed) {
					nodes.push(sel[i]);
				}	else {
					delete sel[i];
				}
			}
			return nodes;
		},

		selectNone: function(){
			// summary:
			//		Unselects all items
			// tags:
			//		private

			this.setSelection([]);
			return this;	// self
		},

		destroy: function(){
			// summary:
			//		Prepares the object to be garbage-collected
			this.inherited(arguments);
			this.selection = this.anchor = null;
		},
		addTreeNode: function(/*dijit/Tree._TreeNode*/ node, /*Boolean?*/isAnchor){
			// summary:
			//		add node to current selection
			// node: Node
			//		node to add
			// isAnchor: Boolean
			//		Whether the node should become anchor.

			this.setSelection(this.getSelectedTreeNodes().concat( [node] ));
			if(isAnchor){ this.anchor = node; }
			return node;
		},
		removeTreeNode: function(/*dijit/Tree._TreeNode*/ node){
			// summary:
			//		remove node from current selection
			// node: Node
			//		node to remove
			this.setSelection(this._setDifference(this.getSelectedTreeNodes(), [node]));
			return node;
		},
		isTreeNodeSelected: function(/*dijit/Tree._TreeNode*/ node){
			// summary:
			//		return true if node is currently selected
			// node: Node
			//		the node to check whether it's in the current selection

			return node.id && !!this.selection[node.id];
		},
		setSelection: function(/*dijit/Tree._TreeNode[]*/ newSelection){
			// summary:
			//		set the list of selected nodes to be exactly newSelection. All changes to the
			//		selection should be passed through this function, which ensures that derived
			//		attributes are kept up to date. Anchor will be deleted if it has been removed
			//		from the selection, but no new anchor will be added by this function.
			// newSelection: Node[]
			//		list of tree nodes to make selected
			var oldSelection = this.getSelectedTreeNodes();
			array.forEach(this._setDifference(oldSelection, newSelection), lang.hitch(this, function(node){
				node.setSelected(false);
				if(this.anchor == node){
					delete this.anchor;
				}
				delete this.selection[node.id];
			}));
			array.forEach(this._setDifference(newSelection, oldSelection), lang.hitch(this, function(node){
				node.setSelected(true);
				this.selection[node.id] = node;
			}));
			this._updateSelectionProperties();
		},
		_setDifference: function(xs,ys){
			// summary:
			//		Returns a copy of xs which lacks any objects
			//		occurring in ys. Checks for membership by
			//		modifying and then reading the object, so it will
			//		not properly handle sets of numbers or strings.

			array.forEach(ys, function(y){ y.__exclude__ = true; });
			var ret = array.filter(xs, function(x){ return !x.__exclude__; });

			// clean up after ourselves.
			array.forEach(ys, function(y){ delete y['__exclude__'] });
			return ret;
		},
		_updateSelectionProperties: function(){
			// summary:
			//		Update the following tree properties from the current selection:
			//		path[s], selectedItem[s], selectedNode[s]

			var selected = this.getSelectedTreeNodes();
			var paths = [], nodes = [], selects = [];
			array.forEach(selected, function(node){
				var ary = node.getTreePath(), model = this.tree.model;
				nodes.push(node);
				paths.push(ary);
				ary = array.map(ary, function(item){
					return model.getIdentity(item);
				}, this);
				selects.push(ary.join("/"))
			}, this);
			var items = array.map(nodes,function(node){ return node.item; });
			this.tree._set("paths", paths);
			this.tree._set("path", paths[0] || []);
			this.tree._set("selectedNodes", nodes);
			this.tree._set("selectedNode", nodes[0] || null);
			this.tree._set("selectedItems", items);
			this.tree._set("selectedItem", items[0] || null);
            if (this.tree.persist && selects.length > 0) {
                cookie(this.cookieName, selects.join(","), {expires:365});
            }
		},
		_getSavedPaths: function(){
			// summary:
			//		Returns paths of nodes that were selected previously and saved in the cookie.

			var tree = this.tree;
			if(tree.persist && tree.dndController.cookieName){
				var oreo, paths = [];
				oreo = cookie(tree.dndController.cookieName);
				if(oreo){
					paths = array.map(oreo.split(","), function(path){
					   return path.split("/");
					})
				}
				return paths;
			}
		},
		// mouse events
		onMouseDown: function(e){
			// summary:
			//		Event processor for onmousedown/ontouchstart
			// e: Event
			//		onmousedown/ontouchstart event
			// tags:
			//		protected

			// ignore click on expando node
			if(!this.current || this.tree.isExpandoNode(e.target, this.current)){ return; }

			// ignore right-click
			if(e.type != "touchstart" && !mouse.isLeft(e)){ return; }

			e.preventDefault();

			var treeNode = this.current,
			  copy = connect.isCopyKey(e), id = treeNode.id;

			// if shift key is not pressed, and the node is already in the selection,
			// delay deselection until onmouseup so in the case of DND, deselection
			// will be canceled by onmousemove.
			if(!this.singular && !e.shiftKey && this.selection[id]){
				this._doDeselect = true;
				return;
			}else{
				this._doDeselect = false;
			}
			this.userSelect(treeNode, copy, e.shiftKey);
		},

		onMouseUp: function(e){
			// summary:
			//		Event processor for onmouseup/ontouchend
			// e: Event
			//		onmouseup/ontouchend event
			// tags:
			//		protected

			// _doDeselect is the flag to indicate that the user wants to either ctrl+click on
			// a already selected item (to deselect the item), or click on a not-yet selected item
			// (which should remove all current selection, and add the clicked item). This can not
			// be done in onMouseDown, because the user may start a drag after mousedown. By moving
			// the deselection logic here, the user can drags an already selected item.
			if(!this._doDeselect){ return; }
			this._doDeselect = false;
			this.userSelect(this.current, connect.isCopyKey(e), e.shiftKey);
		},
		onMouseMove: function(/*===== e =====*/){
			// summary:
			//		event processor for onmousemove/ontouchmove
			// e: Event
			//		onmousemove/ontouchmove event
			this._doDeselect = false;
		},

		_compareNodes: function(n1, n2){
			if(n1 === n2){
				return 0;
			}

			if('sourceIndex' in document.documentElement){ //IE
				//TODO: does not yet work if n1 and/or n2 is a text node
				return n1.sourceIndex - n2.sourceIndex;
			}else if('compareDocumentPosition' in document.documentElement){ //FF, Opera
				return n1.compareDocumentPosition(n2) & 2 ? 1: -1;
			}else if(document.createRange){ //Webkit
				var r1 = doc.createRange();
				r1.setStartBefore(n1);

				var r2 = doc.createRange();
				r2.setStartBefore(n2);

				return r1.compareBoundaryPoints(r1.END_TO_END, r2);
			}else{
				throw Error("dijit.tree._compareNodes don't know how to compare two different nodes in this browser");
			}
		},

		userSelect: function(node, multi, range){
			// summary:
			//		Add or remove the given node from selection, responding
			//		to a user action such as a click or keypress.
			// multi: Boolean
			//		Indicates whether this is meant to be a multi-select action (e.g. ctrl-click)
			// range: Boolean
			//		Indicates whether this is meant to be a ranged action (e.g. shift-click)
			// tags:
			//		protected

			if(this.singular){
				if(this.anchor == node && multi){
					this.selectNone();
				}else{
					this.setSelection([node]);
					this.anchor = node;
				}
			}else{
				if(range && this.anchor){
					var cr = this._compareNodes(this.anchor.rowNode, node.rowNode),
					begin, end, anchor = this.anchor;

					if(cr < 0){ //current is after anchor
						begin = anchor;
						end = node;
					}else{ //current is before anchor
						begin = node;
						end = anchor;
					}
					var nodes = [];
					//add everything betweeen begin and end inclusively
					while(begin != end){
						nodes.push(begin);
						begin = this.tree._getNextNode(begin);
					}
					nodes.push(end);

					this.setSelection(nodes);
				}else{
					if( this.selection[ node.id ] && multi ){
						this.removeTreeNode( node );
					}else if(multi){
						this.addTreeNode(node, true);
					}else{
						this.setSelection([node]);
						this.anchor = node;
					}
				}
			}
		},

		getItem: function(/*String*/ key){
			// summary:
			//		Returns the dojo/dnd/Container._Item (representing a dragged node) by it's key (id).
			//		Called by dojo/dnd/Source.checkAcceptance().
			// tags:
			//		protected

			var widget = this.selection[key];
			return {
				data: widget,
				type: ["treeNode"]
			}; // dojo/dnd/Container._Item
		},

		forInSelectedItems: function(/*Function*/ f, /*Object?*/ o){
			// summary:
			//		Iterates over selected items;
			//		see `dojo/dnd/Container.forInItems()` for details
			o = o || kernel.global;
			for(var id in this.selection){
				// console.log("selected item id: " + id);
				f.call(o, this.getItem(id), id, this);
			}
		}
	});
});

},
'url:dijit/form/templates/CheckBox.html':"<div class=\"dijit dijitReset dijitInline\" role=\"presentation\"\n\t><input\n\t \t${!nameAttrSetting} type=\"${type}\" ${checkedAttrSetting}\n\t\tclass=\"dijitReset dijitCheckBoxInput\"\n\t\tdata-dojo-attach-point=\"focusNode\"\n\t \tdata-dojo-attach-event=\"onclick:_onClick\"\n/></div>\n",
'commsy/popups/ToggleRoomConfiguration':function(){
define("commsy/popups/ToggleRoomConfiguration", [	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(TogglePopupHandler, {
		sendImages: [],
		
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "configuration";

			this.features = [ "editor", "upload", "upload-single", "colorpicker" ];

			// register click for node
			this.registerPopupClick();
		},

		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_settings_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_settings_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},

		setupSpecific: function() {
			var communityRoomInputNode = Query("input#add_community_room", this.contentNode)[0];
			if(communityRoomInputNode) {
				// register click for community room assign button
				On(communityRoomInputNode, "click", Lang.hitch(this, function(event) {
					this.onClickAssignCommunityRoom();
				}));
			}

			// register click for additional status button
			On(Query("input#add_additional_status", this.contentNode)[0], "click", Lang.hitch(this, function(event) {
				this.onClickAdditionalStatus();
			}));

			// update schema preview and set onchange handler
			this.updateConfigurationSchemaPreview();

			On(Query("select#room_color_choice", this.contentNode)[0], "change", Lang.hitch(this, function(event) {
				this.updateConfigurationSchemaPreview();
			}));

			// participation code hiding
			dojo.forEach(Query("input[name='form_data[member_check]']", this.contentNode), Lang.hitch(this, function(node, index, arr) {
				if(DomAttr.get(node, "value") === "withcode") {
					// enable
					On(node, "click", function(event) {
						DomAttr.set(Query("input#code", this.contentNode)[0], "disabled", false);
					});
				} else {
					// disable
					On(node, "click", function(event) {
						DomAttr.set(Query("input#code", this.contentNode)[0], "disabled", true);
					});
				}
			}));

			// setup moderation support form elements
			var moderationRubricNode = Query("select#moderation_rubric", this.contentNode)[0];
			this.updateUsageHints(DomAttr.get(moderationRubricNode, "value"));

			On(moderationRubricNode, "change", Lang.hitch(this, function(event) {
				// get active moderation rubric
				var moderationRubricNode = Query("select#moderation_rubric", this.contentNode)[0];
				var activeRubric = DomAttr.get(moderationRubricNode, "value");
				this.updateUsageHints(activeRubric);
			}));

			// handle mail text update
			var mailTextRubricNode = Query("select#mailtext_rubric", this.contentNode)[0];
			var mailTextRubricChildrenNode = Query("option:checked", mailTextRubricNode)[0];
			this.updateMailText(DomAttr.get(mailTextRubricChildrenNode, "id"));

			On(mailTextRubricNode, "change", Lang.hitch(this, function(event) {
				// get active value
				var mailTextRubricChildrenNode = Query("option:checked", mailTextRubricNode)[0];
				var activeMailtext = DomAttr.get(mailTextRubricChildrenNode, "id");
				this.updateMailText(activeMailtext);
			}));

			// handle usage contract update
			var usageContractNode = Query("select#additional_agb_description_text", this.contentNode)[0];
			this.updateUsageContract(DomAttr.get(usageContractNode, "value"));

			On(usageContractNode, "change", Lang.hitch(this, function(event) {
				// get active value
				var usageContractNode = Query("select#additional_agb_description_text", this.contentNode)[0];
				var activeLang = DomAttr.get(usageContractNode, "value");
				this.updateUsageContract(activeLang);
			}));

			dojo.ready(Lang.hitch(this, function() {
				// setup callback for single uploads
				this.featureHandles["upload-single"][0].setCallback(Lang.hitch(this, function(fileInfo) {
					// room logo upload
					
					// setup preview
					var formNode = this.featureHandles["upload-single"][0].uploader.form;
					var previewNode = Query("div.filePreview", formNode)[0];
					
					DomConstruct.empty(previewNode);
					
					DomConstruct.create("img", {
						src:		"commsy.php?cid=" + this.uri_object.cid + "&mod=picture&fct=getTemp&fileName=" + fileInfo.file
					}, previewNode, "last");
					
					this.sendImages.push({ part: "room_logo", fileInfo: fileInfo });
				}));

				this.featureHandles["upload-single"][1].setCallback(Lang.hitch(this, function(fileInfo) {
					// room background
					
					// setup preview
					var formNode = this.featureHandles["upload-single"][0].uploader.form;
					var previewNode = Query("div.filePreview", formNode)[0];
					
					DomConstruct.empty(previewNode);
					
					DomConstruct.create("img", {
						src:		"commsy.php?cid=" + this.uri_object.cid + "&mod=picture&fct=getTemp&fileName=" + fileInfo.file
					}, previewNode, "last");
					
					this.sendImages.push({ part: "room_bg", fileInfo: fileInfo });
				}));
			}));

			// setup accounts tab
			require(["commsy/Accounts"], Lang.hitch(this, function(Accounts) {
				var accounts = new Accounts();
				accounts.init(this.cid, this.from_php.template.tpl_path);

				// check for auto load tab
				var autoOpen = this.from_php.autoOpenPopup;
				if (autoOpen) {
					var aNode = Query("a[href='" + autoOpen.tab + "']")[0];
					if (aNode) {
						accounts.setStatus(autoOpen.parameters.filter);
						aNode.click();
					}
				}
			}));
			
			// confirm delete Wordpress
			var deleteWordpressButton = Query("#submit_delete_wordpress", this.contentNode)[0];
			if (deleteWordpressButton) {
				On(deleteWordpressButton, "click", Lang.hitch(this, function(event) {
					this.button_delete = new dijit.form.Button({
						label:		"Blog endg&uuml;ltig l&ouml;schen",
						onClick:	Lang.hitch(this, function(event) {
							this.onPopupSubmit({
                        part: "external_configuration",
                        action: "delete_wordpress"
                     });
							// destroy the dialog
							this.dialog.destroyRecursive();
						})
					});
					
					this.button_cancel = new dijit.form.Button({
						label:		"Abbrechen",
						onClick:	Lang.hitch(this, function(event) {
							// destroy the dialog
							this.dialog.destroyRecursive();
						})
					});
					
					// create and show the dialog
					// TODO: translate
					this.dialog = new dijit.Dialog({
						title:		"Wordpress l&ouml;schen",
						content: 	"<b style='color:#ff0000;'>Achtung: Alle Daten im Blog werden gel&ouml;scht. Dieser Vorgang kann nicht r&uuml;ckg&auml;ngig gemacht werden!</b><br/><br/>"
					});
					dojo.place(this.button_delete.domNode, this.dialog.containerNode);
					dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
					
					this.dialog.show();
				}));
			}
			
		   // confirm delete Wiki
         var deleteWikiButton = Query("#submit_delete_wiki", this.contentNode)[0];
         if (deleteWikiButton) {
            On(deleteWikiButton, "click", Lang.hitch(this, function(event) {
               this.button_delete = new dijit.form.Button({
                  label:      "Wiki endg&uuml;ltig l&ouml;schen",
                  onClick: Lang.hitch(this, function(event) {
                     this.onPopupSubmit({
                        part: "external_configuration",
                        action: "delete_wiki"
                     });
                     // destroy the dialog
                     this.dialog.destroyRecursive();
                  })
               });
               
               this.button_cancel = new dijit.form.Button({
                  label:      "Abbrechen",
                  onClick: Lang.hitch(this, function(event) {
                     // destroy the dialog
                     this.dialog.destroyRecursive();
                  })
               });
               
               // create and show the dialog
               // TODO: translate
               this.dialog = new dijit.Dialog({
                  title:      "Wiki l&ouml;schen",
                  content:	  "<b style='color:#ff0000;'>Ein gel&ouml;schtes Wiki kann nicht wieder rekonstruiert werden. M&ouml;chten Sie dieses Wiki endg&uuml;ltig l&ouml;schen?</b><br/><br/>"
               });
               dojo.place(this.button_delete.domNode, this.dialog.containerNode);
               dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
               
               this.dialog.show();
            }));
         }
         
         	// confirm delete room
			var deleteWordpressButton = Query("#submit_delete_room", this.contentNode)[0];
			if (deleteWordpressButton) {
				On(deleteWordpressButton, "click", Lang.hitch(this, function(event) {
					this.button_delete = new dijit.form.Button({
						label:		"Raum endg&uuml;ltig l&ouml;schen",
						onClick:	Lang.hitch(this, function(event) {
							this.onPopupSubmit({
			                   part: "room_configuration",
			                   action: "delete_room"
			                });
							// destroy the dialog
							this.dialog.destroyRecursive();
						})
					});
					
					this.button_cancel = new dijit.form.Button({
						label:		"Abbrechen",
						onClick:	Lang.hitch(this, function(event) {
							// destroy the dialog
							this.dialog.destroyRecursive();
						})
					});
					
					// create and show the dialog
					// TODO: translate
					this.dialog = new dijit.Dialog({
						title:		"Raum l&ouml;schen",
						content: 	"<b style='color:#ff0000;'>Achtung: Der gesamte Raum und alle Daten im Raum werden gel&ouml;scht. Dieser Vorgang kann nicht r&uuml;ckg&auml;ngig gemacht werden!</b><br/><br/>"
					});
					dojo.place(this.button_delete.domNode, this.dialog.containerNode);
					dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
					
					this.dialog.show();
				}));
			}
		},

		onClickAssignCommunityRoom: function() {
			// get id from selected option
			var selectNode = Query("select#room_communityrooms", this.contentNode)[0];
			var selectedId = DomAttr.get(selectNode, "value");
			// check if id is a number and greater than -1
			if(!isNaN(selectedId) && selectedId > -1) {
				// check if already assigned
				var assigned = false;
				dojo.forEach(Query("input[name^='form_data[communityroomlist_']"), function(node, index, arr) {
					// extract id
					var regex = new RegExp("form_data\\[communityroomlist_([0-9]*)\\]");
					var results = regex.exec(DomAttr.get(node, "name"));
					var id = results[1];

					if(id == selectedId) {
						assigned = true;
						return false;
					}
				});

				if(assigned === false) {
					// append new entry
					var divNode = Query("div#assigned_community_rooms", this.contentNode)[0];

					DomConstruct.create("input", {
						id:			"room_communityroomlist",
						type:		"checkbox",
						checked:	true,
						value:		selectedId,
						name:		"form_data[communityroomlist_" + selectedId + "]"
					}, divNode, "last");

					var roomName = DomAttr.get(Query("option[value='" + selectedId + "']", selectNode)[0], "innerHTML");
					DomConstruct.create("span", {
						innerHTML:	roomName
					}, divNode, "last");
				}
			}
		},

		onClickAdditionalStatus: function() {
			var inputObject = Query("input#status")[0];

			var value = DomAttr.get(inputObject, "value");
			DomAttr.set(inputObject, "value", "");

			if(value !== "") {
				// append new entry
				var divObject = Query("div#additional_status_list")[0];

				// get new value
				var newValue = 5;
				dojo.forEach(Query("input", divObject), function(node, index, arr) {
					var regex = new RegExp("form_data\\[additional_status_([0-9]*)\\]");
					var results = regex.exec(DomAttr.get(node, "name"));
					var index = results[1];

					if(index >= newValue) newValue = parseInt(index) + 1;
				});

				DomConstruct.create("input", {
					type:		"checkbox",
					checked:	true,
					value:		value,
					name:		"form_data[additional_status_" + newValue + "]"
				}, divObject, "last");

				DomConstruct.create("span", {
					innerHTML:	value
				}, divObject, "last");
			}
		},

		updateConfigurationSchemaPreview: function() {
			// set image path for preview and handle own schema
			var selectedOptionNode = Query("select#room_color_choice option:checked", this.contentNode)[0];
			var selectedValue = DomAttr.get(selectedOptionNode, "value");
			var selectedText = DomAttr.get(selectedOptionNode, "innerHTML");
			var imageNode = Query("div#room_color_preview img", this.contentNode)[0];
			var imageDivNode = Query("div#room_color_preview", this.contentNode)[0];
			var divNode = Query("div#room_color_own", this.contentNode)[0];

			if(selectedValue === "individual") {
				// hide image preview, show own
				DomClass.add(imageDivNode, "hidden");
				DomClass.remove(divNode, "hidden");
			} else {
				// show image preview, hide own
				DomClass.remove(imageDivNode, "hidden");
				DomClass.add(divNode, "hidden");

				if(selectedValue === "default") selectedText = "default";
				DomAttr.set(imageNode, "src", "templates/themes/" + selectedValue + "/preview.gif");
			}
		},

		updateUsageHints: function(selectedValue) {
			// hide all
			dojo.forEach(Query("input[id^='moderation_title_']", this.contentNode), function(node, index, arr) {
				DomClass.add(node, "hidden");
			});

			dojo.forEach(Query("div[id^='moderation_description_'], textarea[id^='moderation_description_']", this.contentNode), function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden");
			});

			// show selected
			DomClass.remove(Query("input#moderation_title_" + selectedValue, this.contentNode)[0], "hidden");
			DomClass.remove(Query("div#moderation_description_" + selectedValue + ", textarea#moderation_description_" + selectedValue, this.contentNode)[0].parentNode, "hidden");
		},

		updateMailText: function(selectedValue) {
			// extract index
			var regex = new RegExp("mail_text_([0-9]*)");
			var results = regex.exec(selectedValue);
			var index = results[1];

			// hide all
			dojo.forEach(Query("textarea[id^='moderation_mail_body_']", this.contentNode), function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden")
				DomClass.add(node.parentNode.parentNode, "hidden");
			});

			// show selected
			dojo.forEach(Query("textarea#moderation_mail_body_de_" + index + ", textarea#moderation_mail_body_en_" + index, this.contentNode), function(node, index, arr) {
				DomClass.remove(node.parentNode, "hidden")
				DomClass.remove(node.parentNode.parentNode, "hidden");
			});
		},

		updateUsageContract: function(selectedValue) {
			// hide all
			dojo.forEach(Query("div[id^='agb_text_']", this.contentNode), function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden")
				DomClass.add(node.parentNode.parentNode, "hidden");
			});

			// show selected
			var node = Query("div[id^='agb_text_" + selectedValue + "']")[0];
			DomClass.remove(node.parentNode, "hidden");
			DomClass.remove(node.parentNode.parentNode, "hidden");
		},

		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			var action = customObject.action;

			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
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
			
			this.submit(search, { part: part, action: action });
		},

		onPopupSubmitSuccess: function(item_id) {
			// save images
			if (this.sendImages.length > 0) {
				var data = {
						module:			"configuration",
						additional: {
						    part:		this.sendImages[0].part,
						    fileInfo:	this.sendImages[0].fileInfo
						}
					};
				
				this.AJAXRequest("popup", "save", data, Lang.hitch(this, function(response) {
					if (this.sendImages[1]) {
						var data = {
								module:			"configuration",
								additional: {
								    part:		this.sendImages[1].part,
								    fileInfo:	this.sendImages[1].fileInfo
								}
							};
						
						this.AJAXRequest("popup", "save", data, function(response) {
							location.reload();
						});
					} else {
						location.reload();
					}
				}));
			} else {
				if (!item_id) {
					location.reload();
				} else {
					location.href = "commsy.php?cid=" + item_id;
				}
			}
		}
	});
});
},
'commsy/popups/ToggleBreadcrumb':function(){
define("commsy/popups/ToggleBreadcrumb", [	"dojo/_base/declare",
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
			var aEditNode = Query("a#edit_roomlist", this.contentNode)[0]
			if (aEditNode) {
				On.once(aEditNode, "click", Lang.hitch(this, function(event) {
					this.setupEditMode();
				}));
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
			dojo.forEach(Query("div.room_block", this.contentNode), Lang.hitch(this, function(blockNode, index, arr) {
				var roomAreaObjects = Query("div.breadcrumb_room_area", blockNode);
				
				// group h3-tags together
				var ref = null;
				var divNode = null;
				dojo.forEach(roomAreaObjects, Lang.hitch(this, function(roomAreaObject, index, arr) {
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
				dojo.forEach(Query("div.room_change_item, div.room_dummy", ref), Lang.hitch(this, function(node, index, arr) {
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
			dojo.forEach(contentObjects, function(contentObject, index, arr) {
				dojo.forEach(Query("div.breadcrumb_room_area", contentObject), function(sourceNode, index, arr) {
					sourceNodes.push(sourceNode);
				});
			});
			
			// make all sources a dojo.dnd.Source and set nodes
			var sources = [];
			dojo.forEach(sourceNodes, Lang.hitch(this, function(sourceNode, index, arr) {
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
},
'commsy/popups/ClickTopicPopup':function(){
define("commsy/popups/ClickTopicPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		sendImages: [],

		constructor: function() {
			this.sendImages = [];
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "topic";
			this.editType = customObject.editType;

			this.fileInfo = null;

			this.features = [ "editor", "upload", "upload-single", "netnavigation", "calendar", "path" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
			dojo.ready(lang.hitch(this, function() {
				// setup callback for single upload
				if (this.featureHandles["upload-single"]) {
					this.featureHandles["upload-single"][0].setCallback(lang.hitch(this, function(fileInfo) {
						// setup preview
						var formNode = this.featureHandles["upload-single"][0].uploader.form;
						var previewNode = query("div.filePreview", formNode)[0];

						domConstruct.empty(previewNode);

						domConstruct.create("img", {
							src:		"commsy.php?cid=" + this.uri_object.cid + "&mod=picture&fct=getTemp&fileName=" + fileInfo.file
						}, previewNode, "last");

						this.sendImages.push({ part: "upload_picture", fileInfo: fileInfo });
					}));
				}
			}));
		},

		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			var search = {
				tabs: [
				    { id: "rights_tab" }
				],
				nodeLists: [
					{ query: query("div#files_attached", this.contentNode) },
					{ query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) }
				]
			};

			this.submit(search);
		},

		onPopupSubmitSuccess: function(item_id) {
			if (this.sendImages.length > 0) {
				// send ajax request
				var data = {
					module:			"group",
					additional: {
						action:		this.sendImages[0].part,
					    fileInfo:	this.sendImages[0].fileInfo,
					    iid:		item_id
					}
				};
			}

			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					this.featureHandles["path"][0].save(item_id, lang.hitch(this, function() {
						if (this.sendImages.length > 0) {
							this.AJAXRequest("popup", "save", data, lang.hitch(this, function(response) {
								this.reload(item_id);
							}));
						} else {
							this.reload(item_id);
						}
					}));
				}));
			} else {
				if (this.sendImages.length > 0) {
					this.AJAXRequest("popup", "save", data, lang.hitch(this, function(response) {
						this.featureHandles["path"][0].save(item_id, lang.hitch(this, function() {
							//this.close();
							this.reload(item_id);
						}));
					}));
				} else {
					this.featureHandles["path"][0].save(item_id, lang.hitch(this, function() {
						//this.close();
						this.reload(item_id);
					}));
				}
			}
		}
	});
});
},
'dijit/form/_FormWidgetMixin':function(){
define("dijit/form/_FormWidgetMixin", [
	"dojo/_base/array", // array.forEach
	"dojo/_base/declare", // declare
	"dojo/dom-attr", // domAttr.set
	"dojo/dom-style", // domStyle.get
	"dojo/_base/lang", // lang.hitch lang.isArray
	"dojo/mouse", // mouse.isLeft
	"dojo/sniff", // has("webkit")
	"dojo/window", // winUtils.scrollIntoView
	"../a11y"	// a11y.hasDefaultTabStop
], function(array, declare, domAttr, domStyle, lang, mouse, has, winUtils, a11y){

// module:
//		dijit/form/_FormWidgetMixin

return declare("dijit.form._FormWidgetMixin", null, {
	// summary:
	//		Mixin for widgets corresponding to native HTML elements such as `<checkbox>` or `<button>`,
	//		which can be children of a `<form>` node or a `dijit/form/Form` widget.
	//
	// description:
	//		Represents a single HTML element.
	//		All these widgets should have these attributes just like native HTML input elements.
	//		You can set them during widget construction or afterwards, via `dijit/_WidgetBase.set()`.
	//
	//		They also share some common methods.

	// name: [const] String
	//		Name used when submitting form; same as "name" attribute or plain HTML elements
	name: "",

	// alt: String
	//		Corresponds to the native HTML `<input>` element's attribute.
	alt: "",

	// value: String
	//		Corresponds to the native HTML `<input>` element's attribute.
	value: "",

	// type: [const] String
	//		Corresponds to the native HTML `<input>` element's attribute.
	type: "text",

	// tabIndex: String
	//		Order fields are traversed when user hits the tab key
	tabIndex: "0",
	_setTabIndexAttr: "focusNode",	// force copy even when tabIndex default value, needed since Button is <span>

	// disabled: Boolean
	//		Should this widget respond to user input?
	//		In markup, this is specified as "disabled='disabled'", or just "disabled".
	disabled: false,

	// intermediateChanges: Boolean
	//		Fires onChange for each value change or only on demand
	intermediateChanges: false,

	// scrollOnFocus: Boolean
	//		On focus, should this widget scroll into view?
	scrollOnFocus: true,

	// Override _WidgetBase mapping id to this.domNode, needs to be on focusNode so <label> etc.
	// works with screen reader
	_setIdAttr: "focusNode",

	_setDisabledAttr: function(/*Boolean*/ value){
		this._set("disabled", value);
		domAttr.set(this.focusNode, 'disabled', value);
		if(this.valueNode){
			domAttr.set(this.valueNode, 'disabled', value);
		}
		this.focusNode.setAttribute("aria-disabled", value ? "true" : "false");

		if(value){
			// reset these, because after the domNode is disabled, we can no longer receive
			// mouse related events, see #4200
			this._set("hovering", false);
			this._set("active", false);

			// clear tab stop(s) on this widget's focusable node(s)  (ComboBox has two focusable nodes)
			var attachPointNames = "tabIndex" in this.attributeMap ? this.attributeMap.tabIndex :
				("_setTabIndexAttr" in this) ? this._setTabIndexAttr : "focusNode";
			array.forEach(lang.isArray(attachPointNames) ? attachPointNames : [attachPointNames], function(attachPointName){
				var node = this[attachPointName];
				// complex code because tabIndex=-1 on a <div> doesn't work on FF
				if(has("webkit") || a11y.hasDefaultTabStop(node)){	// see #11064 about webkit bug
					node.setAttribute('tabIndex', "-1");
				}else{
					node.removeAttribute('tabIndex');
				}
			}, this);
		}else{
			if(this.tabIndex != ""){
				this.set('tabIndex', this.tabIndex);
			}
		}
	},

	_onFocus: function(/*String*/ by){
		// If user clicks on the widget, even if the mouse is released outside of it,
		// this widget's focusNode should get focus (to mimic native browser hehavior).
		// Browsers often need help to make sure the focus via mouse actually gets to the focusNode.
		if(by == "mouse" && this.isFocusable()){
			// IE exhibits strange scrolling behavior when refocusing a node so only do it when !focused.
			var focusConnector = this.connect(this.focusNode, "onfocus", function(){
				this.disconnect(mouseUpConnector);
				this.disconnect(focusConnector);
			});
			// Set a global event to handle mouseup, so it fires properly
			// even if the cursor leaves this.domNode before the mouse up event.
			var mouseUpConnector = this.connect(this.ownerDocumentBody, "onmouseup", function(){
				this.disconnect(mouseUpConnector);
				this.disconnect(focusConnector);
				// if here, then the mousedown did not focus the focusNode as the default action
				if(this.focused){
					this.focus();
				}
			});
		}
		if(this.scrollOnFocus){
			this.defer(function(){ winUtils.scrollIntoView(this.domNode); }); // without defer, the input caret position can change on mouse click
		}
		this.inherited(arguments);
	},

	isFocusable: function(){
		// summary:
		//		Tells if this widget is focusable or not.  Used internally by dijit.
		// tags:
		//		protected
		return !this.disabled && this.focusNode && (domStyle.get(this.domNode, "display") != "none");
	},

	focus: function(){
		// summary:
		//		Put focus on this widget
		if(!this.disabled && this.focusNode.focus){
			try{ this.focusNode.focus(); }catch(e){}/*squelch errors from hidden nodes*/
		}
	},

	compare: function(/*anything*/ val1, /*anything*/ val2){
		// summary:
		//		Compare 2 values (as returned by get('value') for this widget).
		// tags:
		//		protected
		if(typeof val1 == "number" && typeof val2 == "number"){
			return (isNaN(val1) && isNaN(val2)) ? 0 : val1 - val2;
		}else if(val1 > val2){
			return 1;
		}else if(val1 < val2){
			return -1;
		}else{
			return 0;
		}
	},

	onChange: function(/*===== newValue =====*/){
		// summary:
		//		Callback when this widget's value is changed.
		// tags:
		//		callback
	},

	// _onChangeActive: [private] Boolean
	//		Indicates that changes to the value should call onChange() callback.
	//		This is false during widget initialization, to avoid calling onChange()
	//		when the initial value is set.
	_onChangeActive: false,

	_handleOnChange: function(/*anything*/ newValue, /*Boolean?*/ priorityChange){
		// summary:
		//		Called when the value of the widget is set.  Calls onChange() if appropriate
		// newValue:
		//		the new value
		// priorityChange:
		//		For a slider, for example, dragging the slider is priorityChange==false,
		//		but on mouse up, it's priorityChange==true.  If intermediateChanges==false,
		//		onChange is only called form priorityChange=true events.
		// tags:
		//		private
		if(this._lastValueReported == undefined && (priorityChange === null || !this._onChangeActive)){
			// this block executes not for a change, but during initialization,
			// and is used to store away the original value (or for ToggleButton, the original checked state)
			this._resetValue = this._lastValueReported = newValue;
		}
		this._pendingOnChange = this._pendingOnChange
			|| (typeof newValue != typeof this._lastValueReported)
			|| (this.compare(newValue, this._lastValueReported) != 0);
		if((this.intermediateChanges || priorityChange || priorityChange === undefined) && this._pendingOnChange){
			this._lastValueReported = newValue;
			this._pendingOnChange = false;
			if(this._onChangeActive){
				if(this._onChangeHandle){
					this._onChangeHandle.remove();
				}
				// defer allows hidden value processing to run and
				// also the onChange handler can safely adjust focus, etc
				this._onChangeHandle = this.defer(
					function(){
						this._onChangeHandle = null;
						this.onChange(newValue);
					}); // try to collapse multiple onChange's fired faster than can be processed
			}
		}
	},

	create: function(){
		// Overrides _Widget.create()
		this.inherited(arguments);
		this._onChangeActive = true;
	},

	destroy: function(){
		if(this._onChangeHandle){ // destroy called before last onChange has fired
			this._onChangeHandle.remove();
			this.onChange(this._lastValueReported);
		}
		this.inherited(arguments);
	}
});

});

},
'commsy/ListSelection':function(){
define("commsy/ListSelection", [	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/cookie",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/_base/array"], function(declare, BaseClass, Lang, Query, On, Cookie, DomAttr, DomConstruct, BaseArray) {
	return declare(BaseClass, {
		cookieName:		"commsy_list_selection",
		cookieObject: {
			lastRubric:		null,
			selectedIDs:	[]
		},
		currentRubric:	null,
		inputNodes:		null,
		counterNode:	null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(inputNodes, counterNode) {
			this.inputNodes = inputNodes;
			this.counterNode = counterNode;
			
			// set current rubric
			this.currentRubric = this.uri_object.mod;
			
			// set values from cookie - if cookie exists
			var cookieJSON = Cookie(this.cookieName);
			var cookieObject = {};
			if(cookieJSON) {
				cookieObject = dojo.fromJson(cookieJSON);
			}
			
			// last rubric comes either from cookie or - if the cookie was not set - is the current rubric
			this.cookieObject.lastRubric = cookieObject.lastRubric || this.currentRubric;
			this.cookieObject.selectedIDs = cookieObject.selectedIDs || [];
			
			// setup handler for checkboxes
			On(this.inputNodes, "click", Lang.hitch(this, function(event) {
				this.onClickCheckbox(event.target);
			}));
			
			// setup handler for list action submit button
			var inputSubmitNode = Query("input#delete_confirmselect_option")[0];
			if (inputSubmitNode) {
				On(inputSubmitNode, "click", Lang.hitch(this, function(event) {
					this.onClickListActionSubmit(event.target);
				}))
			}
			
			// setup select all handler
			var inputSelectAllNode = Query("input#selectAll")[0];
			if (inputSelectAllNode) {
				On(inputSelectAllNode, "click", Lang.hitch(this, function(event) {
					this.onSelectAll(event.target);
				}));
			}
			
			// if current rubric equals last rubric - restore selection from cookie,
			// otherwise reset selection
			if(this.currentRubric === this.cookieObject.lastRubric) {
				this.restoreSelection();
			} else {
				this.cookieObject.selectedIDs = [];
			}
			
			// save current rubric as last rubric and save cookie
			this.cookieObject.lastRubric = this.currentRubric;
			Cookie(this.cookieName, dojo.toJson(this.cookieObject));
		},
		
		restoreSelection: function() {
			// restore checkbox status
			dojo.forEach(this.inputNodes, Lang.hitch(this, function(node, index, arr) {
				var name = DomAttr.get(node, "name");
				var id = name.substr(18).substr(0, name.length - 19);
				
				if(BaseArray.indexOf(this.cookieObject.selectedIDs, id) !== -1) {
					DomAttr.set(node, "checked", "checked");
				}
			}));
			
			// restore number of selected entries
			if(this.counterNode) DomAttr.set(this.counterNode, "innerHTML", this.cookieObject.selectedIDs.length);
		},
		
		onClickCheckbox: function(checkboxNode) {
			var name = DomAttr.get(checkboxNode, "name");
			var id = name.substr(18).substr(0, name.length - 19);
			
			// get current checkbox status
			var isChecked = (DomAttr.get(checkboxNode, "checked"));
			
			if(isChecked) {
				// add id to selected
				this.cookieObject.selectedIDs.push(id);
			} else {
				// remove id from selected
				this.cookieObject.selectedIDs.splice(BaseArray.indexOf(this.cookieObject.selectedIDs, id), 1);
			}
			
			// save cookie
			Cookie(this.cookieName, dojo.toJson(this.cookieObject));
			
			// restore selection
			this.restoreSelection();
		},
		
		performListOption: function(option) {
			if (option == "listoption_download") {
				// bad hack... :(
				// create a link and simulate click - like in detail view
				dojo.forEach(this.cookieObject.selectedIDs, Lang.hitch(this, function(id, index, arr) {
					var downloadLinkNode = DomConstruct.create("a", {
						href:	"commsy.php?cid=" + this.uri_object.cid + "&mod=download&fct=action&iid=" + id,//this.cookieObject.selectedIDs.join(";"),
						target:	"_blank"
					}, Query("body")[0], "last");
					
					downloadLinkNode.click();
					
					DomConstruct.destroy(downloadLinkNode);
				}));
			}
		},
		
		onClickListActionSubmit: function(inputNode) {
			// perform list action
			var value = DomAttr.get(Query("select[name='form_data[option][list]']")[0], "value");
			this.performListOption(value);
			
			// empty selected ids
			this.cookieObject.selectedIDs = [];
			
			// save cookie
			Cookie(this.cookieName, dojo.toJson(this.cookieObject));
		},
		
		onSelectAll: function(inputNode) {
			dojo.forEach(Query("div.row_even input[type='checkbox'], div.row_odd input[type='checkbox']"), Lang.hitch(this, function(checkboxNode, index, arr) {
				checkboxNode.click();
			}));
		}
	});
});
},
'url:cbtree/templates/cbtreeNode.html':"<div class=\"dijitTreeNode\" role=\"presentation\">\n\t<div data-dojo-attach-point=\"rowNode\" class=\"dijitTreeRow dijitInline\" role=\"presentation\">\n\t\t<div data-dojo-attach-point=\"indentNode\" class=\"dijitInline\"></div>\n\t\t<img src=\"${_blankGif}\" alt=\"\" data-dojo-attach-point=\"expandoNode\"class=\"dijitTreeExpando\" role=\"presentation\" />\n\t\t<span data-dojo-attach-point=\"expandoNodeText\" class=\"dijitExpandoText\" role=\"presentation\"></span>\n\t\t<span data-dojo-attach-point=\"checkBoxNode\" class=\"cbtreeCheckBox\" role=\"presentation\"></span>\n\t\t<span data-dojo-attach-point=\"contentNode\" class=\"dijitTreeContent\" role=\"presentation\">\n\t\t\t<img src=\"${_blankGif}\" alt=\"\" data-dojo-attach-point=\"iconNode\" class=\"dijitIcon dijitTreeIcon\" role=\"presentation\"/>\n\t\t\t<span data-dojo-attach-point=\"labelNode\" class=\"dijitTreeLabel\" role=\"treeitem\" tabindex=\"-1\" aria-selected=\"false\"></span>\n\t\t</span>\n\t</div>\n\t<div data-dojo-attach-point=\"containerNode\" class=\"dijitTreeContainer\" role=\"presentation\" style=\"display: none;\"></div>\n</div>\n",
'commsy/popups/ClickAnnouncementPopup':function(){
define("commsy/popups/ClickAnnouncementPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
		},

		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "announcement";
			this.editType = customObject.editType;

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
		},

		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			var search = {
				tabs: [
					{ id: "rights_tab" },
					{ id: "buzzwords_tab", group: "buzzwords" },
					{ id: "tags_tab", group: "tags" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[dayEnd]']", this.contentNode) },
				    { query: query("input[name='form_data[timeEnd]']", this.contentNode) },
				    { query: query("input.tabStatus", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) }
				]
			};

			this.submit(search);
		},

		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					//this.close();
					this.reload(item_id);
				}));
			} else {
				//this.close();
				this.reload(item_id);
			}
		}
	});
});
},
'cbtree/models/ItemWriteStoreEX':function(){
//
// Copyright (c) 2010-2012, Peter Jekel
// All rights reserved.
//
//	The Checkbox Tree (cbtree), also known as the 'Dijit Tree with Multi State Checkboxes'
//	is released under to following three licenses:
//
//	1 - BSD 2-Clause							 (http://thejekels.com/cbtree/LICENSE)
//	2 - The "New" BSD License			 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L13)
//	3 - The Academic Free License	 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L43)
//
//	In case of doubt, the BSD 2-Clause license takes precedence.
//
define("cbtree/models/ItemWriteStoreEX", [
	"dojo/_base/array",
	"dojo/_base/lang",
	"dojo/data/ItemFileWriteStore"
], function (array, lang, ItemFileWriteStore){

		// module:
		//		cbtree/models/ItemWriteStoreEX
		// summary:
		//		Implements a set of extensions to the dojo.data.ItemFileWriteStore to
		//		provide support to the cbtree/models/TreeStoreModel API,

	lang.extend( ItemFileWriteStore, {
	
		// _validated: [private] Boolean
		//		Indicates if the store has been validated. This property has no real
		//		value to the store itself but is used by the model(s) operating on
		//		the store. It is as a shared variable amongst models.
		_validated: false,
		
		addReference: function (/*dojo.data.item*/ refItem, /*dojo.data.item*/ parentItem, /*String*/ attribute, /*Number?*/ index) {
			// summary:
			//		Add an existing item to the parentItem by reference.
			// refItem:
			//		Item being referenced to be added to the parents list of children.
			// parentItem:
			//		Parent item.
			// attribute:
			//		List attribute of the parent to which the refItem it added.
			// index:
			//		The location at which the refItem is inserted in the list (optional)
			// returns:
			//		Returns true if successful otherwise false
			// tag:
			//		public

			if (!this.isItem(refItem) || !this.isItem(parentItem)) {
				throw new Error( "ItemWriteStoreEX::addReference(): refItem and/or parentItem is not a valid store item");
			}
			// Prevent recursive referencing..
			if (refItem !== parentItem){
				var oldValue;
				if (parentItem[attribute]){
					oldValue = parentItem[attribute];
					this._addReferenceToMap( refItem, parentItem, attribute );
					if (typeof index === "number") {
						parentItem[attribute].splice(index,0,refItem);
					} else {
						parentItem[attribute].push(refItem);
					}
				} else {
					this._addReferenceToMap( refItem, parentItem, attribute );
					parentItem[attribute] = [refItem];
				}
				// Fire off an event..
				this.onSet( parentItem, attribute, oldValue, parentItem[attribute] );
				return true;
			} else {
				throw new Error( "ItemWriteStoreEX::addReference(): parent and reference items are identical" );
			}
			return false;
		},
		 
		attachToRoot: function (/*dojo.data.item*/ item) {
			// summary:
			//		Promote a store item to a top level item.
			// item:
			//		A valid dojo.data.store item.
			// tag:
			//		public

			if ( !this.isRootItem(/*dojo.data.item*/ item) ) {
				item[this._rootItemPropName] = true;
				this._arrayOfTopLevelItems.push(item);
				this.onRoot( item, "attach" );
			}
		},

		detachFromRoot: function (/*dojo.data.item*/ item) {
			// summary:
			//		Detach item from the root by removing it from the top level item list
			//		and removing its '_rootItemPropName' property.
			// item:
			//		A valid dojo.data.store item.
			// tag:
			//		public

			if ( this.isRootItem(item) ) {
				this._removeArrayElement(this._arrayOfTopLevelItems, item);
				delete item[this._rootItemPropName];
				this.onRoot( item, "detach" );
			}
		},
		
		getIdentifierAttr: function() {
			// summary:
			//		Returns the store identifier attribute is defined.
			// tag:
			//		public

			if (!this._loadFinished) {
				this._forceLoad();
			}
			return this._getIdentifierAttribute();
		},
		
		getParents: function (/*dojo.data.item*/ item) {
			// summary:
			//		Get the parent(s) of a dojo.data.item.	
			// description:
			//		Get the parent(s) of a dojo.data item.	Either the '_reverseRefMap' or
			//		'backup_reverseRefMap' property is used to fetch the parent(s). In the
			//		latter case the item is pending deletion.
			// storeItem:
			//		The dojo.data.item whose parent(s) will be returned.
			// tags:
			//		private

			var parents = [],
					itemId;

			if (item) {
				var	references = item[this._reverseRefMap] || item["backup_" + this._reverseRefMap];
				if (references) {
					for(itemId in references) {
						parents.push(this._getItemByIdentity(itemId));
					}
				}
				return parents;
			}
		},

		isRootItem: function (/*dojo.data.item*/ item) {
			// summary:
			//		Returns true if the item has the '_rootItemPropName' property defined
			//		and its value is true, otherwise false is returned.
			// item:
			//		A valid dojo.data.store item.
			// returns:
			//		True if the item is a root item otherwise false
			// tag:
			//		public

			this._assertIsItem(item);
			return item[this._rootItemPropName] ? true : false; 
		},

		isValidated: function () {
			// summary:
			//		Returns true if a model has signalled the store has successfully been
			//		validated. The attribute _validated is part of the store and not of a
			//		model as multiple models may operate on this store. 
			return this._validated;
		},

		itemExist: function (/*Object*/ keywordArgs) {
			// summary:
			//		Tests if, and return a store item if it exists.	 This method is based
			//		on the same set of prerequisites as newItem(), that is, the store must
			//		be fully loaded; if the store has an identifier attribute each store
			//		item MUST at least have that same attribute and the item can not be
			//		pending deletion.
			// keywordArgs:
			//		Object defining the store item properties.
			// returns:
			//		The dojo.data.item if is exist
			// tag:
			//		public
			
			var identifierAttr,
					itemIdentity,
					item;
			
			if (typeof keywordArgs != "object" && typeof keywordArgs != "undefined"){
				throw new Error("ItemWriteStoreEX::itemExist(): argument is not an object");
			}
			this._assert(!this._saveInProgress);

			identifierAttr = this.getIdentifierAttr();
			if (identifierAttr !== Number && this._itemsByIdentity){
				itemIdentity = keywordArgs[identifierAttr]
				if (typeof itemIdentity === "undefined"){
					throw new Error("ItemWriteStoreEX::itemExist(): item has no identity");
				}
				if (!this._pending._deletedItems[itemIdentity]) {
					item = this._itemsByIdentity[itemIdentity];
				} else {
					throw new Error("ItemWriteStoreEX::itemExist(): item is pending deletion");
				}
			}
			return item;
		},
		
		loadStore: function ( keywordArgs ) {
			// summary:
			//		Try a forced load of the entire store but only if it has not
			//		already been loaded.
			//
			// keywordArgs:
			// 		onComplete:
			//				If an onComplete callback function is provided, the callback function
			//				will be called once on successful completion of the load operation
			//				with the total number of items loaded: onComplete(count)
			// 		onError:
			//				The onError parameter is the callback to invoke when loadStore()
			//				encountered an error. It takes one parameter, the error object.
			// 		scope:
			//				If a scope object is provided, all of the callback functions (onComplete,
			//				onError, etc) will be invoked in the context of the scope object. In
			//				the body of the callback function, the value of the "this" keyword
			//				will be the scope object otherwise window.global is used.
			// tag:
			//		public
			var scope = keywordArgs.scope || window.global;
			var self  = this;
			
			function loadComplete( count, requestArgs ) {
				// summary:
				var loadArgs = requestArgs.loadArgs || null;
				var scope    = loadArgs.scope;

				self.onLoad( count );

				if (loadArgs) {
					if (loadArgs.onComplete) {
						loadArgs.onComplete.call(scope, count);
					}
				}
			}

			if (!this._loadFinished) {
				var request  = { queryOptions: {deep: true}, 
												 loadArgs: keywordArgs, 
												 onBegin: loadComplete, 
												 onError: keywordArgs.onError, 
												 scope: this};
				try {
					this.fetch(request);
				} catch(err) {
					if (onError) {
						onError.call(scope, err);
					} else {
						throw err;
					}
				}
			} else {
				if (onComplete) {
					onComplete.call(scope, this._allFileItems.length);
				}
			}
		},
		
		onDelete: function(/*dojo.data.item*/ deletedItem){
			// summary:
			//		See dojo.data.api.Notification.onDelete()
			// tag:
			//		callback.
			// NOTE: Don't call isItem() as it will fail, the item is already deleted
			//			 and therefore no longer valid.
			if ( deletedItem[this._rootItemPropName] === true ){
				this.onRoot( deletedItem, "delete" );
			}
		},
		
		onNew: function(/*dojo.data.item*/ item, parentInfo ){
			// summary:
			//		See dojo.data.api.Notification.onNew()
			// tag:
			//		callback.
			if ( this.isRootItem(item) ){
				this.onRoot( item, "new" );
			}
		},
		
		onLoad: function ( count ) {
			// summary:
			//		Invoked when loading the store completes. This method is only called
			//		when the loadStore() is used.
			// count:
			//		Number of store items loaded.
			// tag:
			//		callback.
		},
		
		onRoot: function(/*dojo.data.item*/ item, /*string*/ action ) {
			// summary:
			//		Invoked whenever a item is added to, or removed from the root.
			// item:
			//		Store item.
			// action:
			//		Event action which can be: "new", "delete", "attach" or "detach" 
			// tag:
			//		callback.
		},
		
		setValidated: function (/*Boolean*/ value) {
			// summary:
			//		Mark the store as successfully been validated.
			this._validated = Boolean(value);
		},
		
		removeReference: function ( /*dojo.data.item*/ refItem, /*dojo.data.item*/ parentItem, /*String*/ attribute ){
			// summary:
			//		Remove a item reference from its parent. Only the references are
			//		removed, the refItem itself is not delete.
			// refItem:
			//		Referenced item to be removed from parents children list.
			// parentItem:
			//		Parent item.
			// attribute:
			//		List attribute of the parent from which the refItem it removed.
			// returns:
			//		True if successful otherwise false
			// tag:
			//		public

			if (!this.isItem(refItem) || !this.isItem(parentItem)) {
				throw new Error( "ItemWriteStoreEX::removeReference(): refItem and/or parentItem is not a valid store item");
			}
			if ( parentItem[attribute] ) {
				this._removeReferenceFromMap( refItem, parentItem, attribute );
				var oldValue = parentItem[attribute];
				if (this._removeArrayElement( parentItem[attribute], refItem )) {
					// Delete attribute if empty which prevents false mayHaveChildren()
					if (this._isEmpty(parentItem[attribute])) {
						delete parentItem[attribute];
					}
					// Fire off an event..
					this.onSet( parentItem, attribute, oldValue, parentItem[attribute] );
					return true;
				}
			}
			return false;
		}

	}); /* end lang.extend() */
	
}); /* end define() */

},
'cbtree/models/_dndContainer':function(){
define("cbtree/models/_dndContainer", [
	"dojo/aspect",	// aspect.after
	"dojo/_base/declare", // declare
	"dojo/dom-class", // domClass.add domClass.remove domClass.replace
	"dojo/_base/event",	// event.stop
	"dojo/_base/lang", // lang.mixin lang.hitch
	"dojo/on",
	"dojo/touch"
], function(aspect, declare,domClass, event, lang, on, touch){

	// module:
	//		dijit/tree/_dndContainer

	/*=====
	 var __Args = {
		 // summary:
		 //		A dict of parameters for Tree source configuration.
		 // isSource: Boolean?
		 //		Can be used as a DnD source. Defaults to true.
		 // accept: String[]
		 //		List of accepted types (text strings) for a target; defaults to
		 //		["text", "treeNode"]
		 // copyOnly: Boolean?
		 //		Copy items, if true, use a state of Ctrl key otherwise,
		 // dragThreshold: Number
		 //		The move delay in pixels before detecting a drag; 0 by default
		 // betweenThreshold: Integer
		 //		Distance from upper/lower edge of node to allow drop to reorder nodes
	 };
	 =====*/

	return declare("dijit.tree._dndContainer", null, {

		// summary:
		//		This is a base class for `dijit/tree/_dndSelector`, and isn't meant to be used directly.
		//		It's modeled after `dojo/dnd/Container`.
		// tags:
		//		protected

		/*=====
		// current: DomNode
		//		The currently hovered TreeNode.rowNode (which is the DOM node
		//		associated w/a given node in the tree, excluding it's descendants)
		current: null,
		=====*/

		constructor: function(tree, params){
			// summary:
			//		A constructor of the Container
			// tree: Node
			//		Node or node's id to build the container on
			// params: __Args
			//		A dict of parameters, which gets mixed into the object
			// tags:
			//		private
			this.tree = tree;
			this.node = tree.domNode;	// TODO: rename; it's not a TreeNode but the whole Tree
			lang.mixin(this, params);

			// class-specific variables
			this.current = null;	// current TreeNode's DOM node

			// states
			this.containerState = "";
			domClass.add(this.node, "dojoDndContainer");

			// set up events
			this.events = [
				// Mouse (or touch) enter/leave on Tree itself
				on(this.node, touch.enter, lang.hitch(this, "onOverEvent")),
				on(this.node, touch.leave,	lang.hitch(this, "onOutEvent")),

				// switching between TreeNodes
				aspect.after(this.tree, "_onNodeMouseEnter", lang.hitch(this, "onMouseOver"), true),
				aspect.after(this.tree, "_onNodeMouseLeave", lang.hitch(this, "onMouseOut"), true),

				// cancel text selection and text dragging
				on(this.node, "dragstart", lang.hitch(event, "stop")),
				on(this.node, "selectstart", lang.hitch(event, "stop"))
			];
		},

		destroy: function(){
			// summary:
			//		Prepares this object to be garbage-collected

			var h;
			while(h = this.events.pop()){ h.remove(); }

			// this.clearItems();
			this.node = this.parent = null;
		},

		// mouse events
		onMouseOver: function(widget /*===== , evt =====*/){
			// summary:
			//		Called when mouse is moved over a TreeNode
			// widget: TreeNode
			// evt: Event
			// tags:
			//		protected
			this.current = widget;
		},

		onMouseOut: function(/*===== widget, evt =====*/){
			// summary:
			//		Called when mouse is moved away from a TreeNode
			// widget: TreeNode
			// evt: Event
			// tags:
			//		protected
			this.current = null;
		},

		_changeState: function(type, newState){
			// summary:
			//		Changes a named state to new state value
			// type: String
			//		A name of the state to change
			// newState: String
			//		new state
			var prefix = "dojoDnd" + type;
			var state = type.toLowerCase() + "State";
			//domClass.replace(this.node, prefix + newState, prefix + this[state]);
			domClass.replace(this.node, prefix + newState, prefix + this[state]);
			this[state] = newState;
		},

		_addItemClass: function(node, type){
			// summary:
			//		Adds a class with prefix "dojoDndItem"
			// node: Node
			//		A node
			// type: String
			//		A variable suffix for a class name
			domClass.add(node, "dojoDndItem" + type);
		},

		_removeItemClass: function(node, type){
			// summary:
			//		Removes a class with prefix "dojoDndItem"
			// node: Node
			//		A node
			// type: String
			//		A variable suffix for a class name
			domClass.remove(node, "dojoDndItem" + type);
		},

		onOverEvent: function(){
			// summary:
			//		This function is called once, when mouse is over our container
			// tags:
			//		protected
			this._changeState("Container", "Over");
		},

		onOutEvent: function(){
			// summary:
			//		This function is called once, when mouse is out of our container
			// tags:
			//		protected
			this._changeState("Container", "");
		}
	});
});

},
'dojo/dnd/Avatar':function(){
define("dojo/dnd/Avatar", [
	"../_base/declare",
	"../_base/window",
	"../dom",
	"../dom-attr",
	"../dom-class",
	"../dom-construct",
	"../hccss",
	"../query"
], function(declare, win, dom, domAttr, domClass, domConstruct, has, query){

// module:
//		dojo/dnd/Avatar

return declare("dojo.dnd.Avatar", null, {
	// summary:
	//		Object that represents transferred DnD items visually
	// manager: Object
	//		a DnD manager object

	constructor: function(manager){
		this.manager = manager;
		this.construct();
	},

	// methods
	construct: function(){
		// summary:
		//		constructor function;
		//		it is separate so it can be (dynamically) overwritten in case of need

		var a = domConstruct.create("table", {
				"class": "dojoDndAvatar",
				style: {
					position: "absolute",
					zIndex:   "1999",
					margin:   "0px"
				}
			}),
			source = this.manager.source, node,
			b = domConstruct.create("tbody", null, a),
			tr = domConstruct.create("tr", null, b),
			td = domConstruct.create("td", null, tr),
			k = Math.min(5, this.manager.nodes.length), i = 0;

		if(has("highcontrast")){
			domConstruct.create("span", {
				id : "a11yIcon",
				innerHTML : this.manager.copy ? '+' : "<"
			}, td)
		}
		domConstruct.create("span", {
			innerHTML: source.generateText ? this._generateText() : ""
		}, td);

		// we have to set the opacity on IE only after the node is live
		domAttr.set(tr, {
			"class": "dojoDndAvatarHeader",
			style: {opacity: 0.9}
		});
		for(; i < k; ++i){
			if(source.creator){
				// create an avatar representation of the node
				node = source._normalizedCreator(source.getItem(this.manager.nodes[i].id).data, "avatar").node;
			}else{
				// or just clone the node and hope it works
				node = this.manager.nodes[i].cloneNode(true);
				if(node.tagName.toLowerCase() == "tr"){
					// insert extra table nodes
					var table = domConstruct.create("table"),
						tbody = domConstruct.create("tbody", null, table);
					tbody.appendChild(node);
					node = table;
				}
			}
			node.id = "";
			tr = domConstruct.create("tr", null, b);
			td = domConstruct.create("td", null, tr);
			td.appendChild(node);
			domAttr.set(tr, {
				"class": "dojoDndAvatarItem",
				style: {opacity: (9 - i) / 10}
			});
		}
		this.node = a;
	},
	destroy: function(){
		// summary:
		//		destructor for the avatar; called to remove all references so it can be garbage-collected
		domConstruct.destroy(this.node);
		this.node = false;
	},
	update: function(){
		// summary:
		//		updates the avatar to reflect the current DnD state
		domClass.toggle(this.node, "dojoDndAvatarCanDrop", this.manager.canDropFlag);
		if(has("highcontrast")){
			var icon = dom.byId("a11yIcon");
			var text = '+';   // assume canDrop && copy
			if (this.manager.canDropFlag && !this.manager.copy){
				text = '< '; // canDrop && move
			}else if (!this.manager.canDropFlag && !this.manager.copy){
				text = "o"; //!canDrop && move
			}else if(!this.manager.canDropFlag){
				text = 'x';  // !canDrop && copy
			}
			icon.innerHTML=text;
		}
		// replace text
		query(("tr.dojoDndAvatarHeader td span" +(has("highcontrast") ? " span" : "")), this.node).forEach(
			function(node){
				node.innerHTML = this.manager.source.generateText ? this._generateText() : "";
			}, this);
	},
	_generateText: function(){
		// summary:
		//		generates a proper text to reflect copying or moving of items
		return this.manager.nodes.length.toString();
	}
});

});

},
'dijit/form/Button':function(){
require({cache:{
'url:dijit/form/templates/Button.html':"<span class=\"dijit dijitReset dijitInline\" role=\"presentation\"\n\t><span class=\"dijitReset dijitInline dijitButtonNode\"\n\t\tdata-dojo-attach-event=\"ondijitclick:_onClick\" role=\"presentation\"\n\t\t><span class=\"dijitReset dijitStretch dijitButtonContents\"\n\t\t\tdata-dojo-attach-point=\"titleNode,focusNode\"\n\t\t\trole=\"button\" aria-labelledby=\"${id}_label\"\n\t\t\t><span class=\"dijitReset dijitInline dijitIcon\" data-dojo-attach-point=\"iconNode\"></span\n\t\t\t><span class=\"dijitReset dijitToggleButtonIconChar\">&#x25CF;</span\n\t\t\t><span class=\"dijitReset dijitInline dijitButtonText\"\n\t\t\t\tid=\"${id}_label\"\n\t\t\t\tdata-dojo-attach-point=\"containerNode\"\n\t\t\t></span\n\t\t></span\n\t></span\n\t><input ${!nameAttrSetting} type=\"${type}\" value=\"${value}\" class=\"dijitOffScreen\"\n\t\ttabIndex=\"-1\" role=\"presentation\" data-dojo-attach-point=\"valueNode\"\n/></span>\n"}});
define("dijit/form/Button", [
	"require",
	"dojo/_base/declare", // declare
	"dojo/dom-class", // domClass.toggle
	"dojo/has",			// has("dijit-legacy-requires")
	"dojo/_base/kernel", // kernel.deprecated
	"dojo/_base/lang", // lang.trim
	"dojo/ready",
	"./_FormWidget",
	"./_ButtonMixin",
	"dojo/text!./templates/Button.html"
], function(require, declare, domClass, has, kernel, lang, ready, _FormWidget, _ButtonMixin, template){

// module:
//		dijit/form/Button

// Back compat w/1.6, remove for 2.0
if(has("dijit-legacy-requires")){
	ready(0, function(){
		var requires = ["dijit/form/DropDownButton", "dijit/form/ComboButton", "dijit/form/ToggleButton"];
		require(requires);	// use indirection so modules not rolled into a build
	});
}

return declare("dijit.form.Button", [_FormWidget, _ButtonMixin], {
	// summary:
	//		Basically the same thing as a normal HTML button, but with special styling.
	// description:
	//		Buttons can display a label, an icon, or both.
	//		A label should always be specified (through innerHTML) or the label
	//		attribute.  It can be hidden via showLabel=false.
	// example:
	// |	<button data-dojo-type="dijit/form/Button" onClick="...">Hello world</button>
	//
	// example:
	// |	var button1 = new Button({label: "hello world", onClick: foo});
	// |	dojo.body().appendChild(button1.domNode);

	// showLabel: Boolean
	//		Set this to true to hide the label text and display only the icon.
	//		(If showLabel=false then iconClass must be specified.)
	//		Especially useful for toolbars.
	//		If showLabel=true, the label will become the title (a.k.a. tooltip/hint) of the icon.
	//
	//		The exception case is for computers in high-contrast mode, where the label
	//		will still be displayed, since the icon doesn't appear.
	showLabel: true,

	// iconClass: String
	//		Class to apply to DOMNode in button to make it display an icon
	iconClass: "dijitNoIcon",
	_setIconClassAttr: { node: "iconNode", type: "class" },

	baseClass: "dijitButton",

	templateString: template,

	// Map widget attributes to DOMNode attributes.
	_setValueAttr: "valueNode",

	_onClick: function(/*Event*/ e){
		// summary:
		//		Internal function to handle click actions
		var ok = this.inherited(arguments);
		if(ok){
			if(this.valueNode){
				this.valueNode.click();
				e.preventDefault(); // cancel BUTTON click and continue with hidden INPUT click
                e.stopPropagation();    // avoid two events bubbling from Button widget
				// leave ok = true so that subclasses can do what they need to do
			}
		}
		return ok;
	},

	_fillContent: function(/*DomNode*/ source){
		// Overrides _Templated._fillContent().
		// If button label is specified as srcNodeRef.innerHTML rather than
		// this.params.label, handle it here.
		// TODO: remove the method in 2.0, parser will do it all for me
		if(source && (!this.params || !("label" in this.params))){
			var sourceLabel = lang.trim(source.innerHTML);
			if(sourceLabel){
				this.label = sourceLabel; // _applyAttributes will be called after buildRendering completes to update the DOM
			}
		}
	},

	_setShowLabelAttr: function(val){
		if(this.containerNode){
			domClass.toggle(this.containerNode, "dijitDisplayNone", !val);
		}
		this._set("showLabel", val);
	},

	setLabel: function(/*String*/ content){
		// summary:
		//		Deprecated.  Use set('label', ...) instead.
		kernel.deprecated("dijit.form.Button.setLabel() is deprecated.  Use set('label', ...) instead.", "", "2.0");
		this.set("label", content);
	},

	_setLabelAttr: function(/*String*/ content){
		// summary:
		//		Hook for set('label', ...) to work.
		// description:
		//		Set the label (text) of the button; takes an HTML string.
		//		If the label is hidden (showLabel=false) then and no title has
		//		been specified, then label is also set as title attribute of icon.
		this.inherited(arguments);
		if(!this.showLabel && !("title" in this.params)){
			this.titleNode.title = lang.trim(this.containerNode.innerText || this.containerNode.textContent || '');
		}
	}
});


});


},
'cbtree/CheckBox':function(){
//
// Copyright (c) 2010-2012, Peter Jekel
// All rights reserved.
//
//	The Checkbox Tree (cbtree), also known as the 'Dijit Tree with Multi State Checkboxes'
//	is released under to following three licenses:
//
//	1 - BSD 2-Clause							 (http://thejekels.com/js/cbtree/LICENSE)
//	2 - The 'New' BSD License			 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L13)
//	3 - The Academic Free License	 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L43)
//
//	In case of doubt, the BSD 2-Clause license takes precedence.
//
define("cbtree/CheckBox", [
	'dijit/form/CheckBox',
	'dojo/_base/declare',
	'dojo/_base/event',
	'dojo/dom-attr'
], function ( CheckBox, declare, event, domAttr ) {

	return declare( [CheckBox], {
		// baseClass: [protected] String
		//		Root CSS class of the widget (ex: twcCheckBox), used to add CSS
		//		classes of widget.
		//		(ex: 'cbtreeCheckBox cbtreeCheckBoxChecked cbtreeCheckBoxMixed')
		baseClass: 'cbtreeCheckBox',
		
		// value:	Boolean
		//		Indicate if the checkbox is a mutli state checkbox or not. If
		//		multiState is true the 'checked' attr can be either: 'mixed',
		//		true or false otherwise 'checked' can only be true or false.
		multiState: true,

		_getCheckedAttr: function () {
			// summary:
			//		Returns the current checked state. This method provides the hook 
			//		for get('checked').
			return this.checked;
		},
		
		_onClick: function (/*Event*/ evt ) {
			// summary:
			//		Process a click event on the checkbox.
			// description:
			//		Process a click event on the checkbox. If the checkbox is in a mixed
			//		state it will change to checked. Any other state will just toggle the
			//		current checkbox state.
			//
			//		NOTE: A click event will never change the state to mixed.
			// evt:
			//		Click event object
			//
			
			if (!this.readOnly && !this.disabled){
				this.toggle();
				return this.onClick(evt);
			}
			return event.stop(evt);
		},

		_setCheckedAttr: function (/*Boolean | String*/ checked, /*Boolean?*/ priorityChange ) {
			// summary
			//		Set the new checked state of the checkbox.
			// description
			//		Set the new checked state of the checkbox.
			// checked:
			//		New state which is either 'mixed', true or false.
			var newState = checked,
					txtState;

			// Normalize the new state 
			if ( newState !== 'mixed' || !this.multiState ) {
				newState = newState ? true : false;
			} 
			txtState = (newState == 'mixed' ? newState : (newState ? 'true' : 'false'));

			this._set('checked', newState );			/* Fast track set() */
			domAttr.set(this.focusNode || this.domNode, 'checked', newState );
			(this.focusNode || this.domNode).setAttribute('aria-checked', txtState );
			this._handleOnChange( newState, priorityChange);
			return newState;
		},

		_setValueAttr: function (/*String | Boolean*/ newValue, /*Boolean?*/ priorityChange){
			// summary:
			//		Handler for value= attribute to constructor, Overwrites the
			//		default '_setValueAttr' method as we will handle the Checkbox
			//		checked attribute explictly.
			// description:
			//		If passed a string, changes the value attribute of the CheckBox
			//		(the one specified as 'value' when the CheckBox was constructed).
			//
			//		NOTE: Changing the checkbox value DOES NOT change the checked state.
			// newValue:
			
			if (typeof newValue == 'string'){
				this.value = newValue;
				domAttr.set(this.focusNode, 'value', newValue);
			}
		},

		toggle: function () {
			// summary:
			//		Toggle the current checkbox state and return the new state. If the
			//		checkbox is read-only or disabled the current state is returned.
			//
			var curState = this.get( 'checked' );
			if (!this.readOnly && !this.disabled){
				return this._setCheckedAttr( (curState == 'mixed' ? true : !curState ) );			
			}
			return curState;
		}
	});	/* end declare() */
});	/* end define() */

},
'dojo/regexp':function(){
define("dojo/regexp", ["./_base/kernel", "./_base/lang"], function(dojo, lang){

// module:
//		dojo/regexp

var regexp = {
	// summary:
	//		Regular expressions and Builder resources
};
lang.setObject("dojo.regexp", regexp);

regexp.escapeString = function(/*String*/str, /*String?*/except){
	// summary:
	//		Adds escape sequences for special characters in regular expressions
	// except:
	//		a String with special characters to be left unescaped

	return str.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, function(ch){
		if(except && except.indexOf(ch) != -1){
			return ch;
		}
		return "\\" + ch;
	}); // String
};

regexp.buildGroupRE = function(/*Object|Array*/arr, /*Function*/re, /*Boolean?*/nonCapture){
	// summary:
	//		Builds a regular expression that groups subexpressions
	// description:
	//		A utility function used by some of the RE generators. The
	//		subexpressions are constructed by the function, re, in the second
	//		parameter.  re builds one subexpression for each elem in the array
	//		a, in the first parameter. Returns a string for a regular
	//		expression that groups all the subexpressions.
	// arr:
	//		A single value or an array of values.
	// re:
	//		A function. Takes one parameter and converts it to a regular
	//		expression.
	// nonCapture:
	//		If true, uses non-capturing match, otherwise matches are retained
	//		by regular expression. Defaults to false

	// case 1: a is a single value.
	if(!(arr instanceof Array)){
		return re(arr); // String
	}

	// case 2: a is an array
	var b = [];
	for(var i = 0; i < arr.length; i++){
		// convert each elem to a RE
		b.push(re(arr[i]));
	}

	 // join the REs as alternatives in a RE group.
	return regexp.group(b.join("|"), nonCapture); // String
};

regexp.group = function(/*String*/expression, /*Boolean?*/nonCapture){
	// summary:
	//		adds group match to expression
	// nonCapture:
	//		If true, uses non-capturing match, otherwise matches are retained
	//		by regular expression.
	return "(" + (nonCapture ? "?:":"") + expression + ")"; // String
};

return regexp;
});

},
'dojo/data/util/simpleFetch':function(){
define("dojo/data/util/simpleFetch", ["../../_base/lang", "../../_base/kernel", "./sorter"],
  function(lang, kernel, sorter){
	// module:
	//		dojo/data/util/simpleFetch
	// summary:
	//		The simpleFetch mixin is designed to serve as a set of function(s) that can
	//		be mixed into other datastore implementations to accelerate their development.

var simpleFetch = {};
lang.setObject("dojo.data.util.simpleFetch", simpleFetch);

simpleFetch.errorHandler = function(/*Object*/ errorData, /*Object*/ requestObject){
	// summary:
	//		The error handler when there is an error fetching items.  This function should not be called
	//		directly and is used by simpleFetch.fetch().
	if(requestObject.onError){
		var scope = requestObject.scope || kernel.global;
		requestObject.onError.call(scope, errorData, requestObject);
	}
};

simpleFetch.fetchHandler = function(/*Array*/ items, /*Object*/ requestObject){
	// summary:
	//		The handler when items are sucessfully fetched.  This function should not be called directly
	//		and is used by simpleFetch.fetch().
	var oldAbortFunction = requestObject.abort || null,
		aborted = false,

		startIndex = requestObject.start?requestObject.start: 0,
		endIndex = (requestObject.count && (requestObject.count !== Infinity))?(startIndex + requestObject.count):items.length;

	requestObject.abort = function(){
		aborted = true;
		if(oldAbortFunction){
			oldAbortFunction.call(requestObject);
		}
	};

	var scope = requestObject.scope || kernel.global;
	if(!requestObject.store){
		requestObject.store = this;
	}
	if(requestObject.onBegin){
		requestObject.onBegin.call(scope, items.length, requestObject);
	}
	if(requestObject.sort){
		items.sort(sorter.createSortFunction(requestObject.sort, this));
	}
	if(requestObject.onItem){
		for(var i = startIndex; (i < items.length) && (i < endIndex); ++i){
			var item = items[i];
			if(!aborted){
				requestObject.onItem.call(scope, item, requestObject);
			}
		}
	}
	if(requestObject.onComplete && !aborted){
		var subset = null;
		if(!requestObject.onItem){
			subset = items.slice(startIndex, endIndex);
		}
		requestObject.onComplete.call(scope, subset, requestObject);
	}
};

simpleFetch.fetch = function(/* Object? */ request){
	// summary:
	//		The simpleFetch mixin is designed to serve as a set of function(s) that can
	//		be mixed into other datastore implementations to accelerate their development.
	// description:
	//		The simpleFetch mixin should work well for any datastore that can respond to a _fetchItems()
	//		call by returning an array of all the found items that matched the query.  The simpleFetch mixin
	//		is not designed to work for datastores that respond to a fetch() call by incrementally
	//		loading items, or sequentially loading partial batches of the result
	//		set.  For datastores that mixin simpleFetch, simpleFetch
	//		implements a fetch method that automatically handles eight of the fetch()
	//		arguments -- onBegin, onItem, onComplete, onError, start, count, sort and scope
	//		The class mixing in simpleFetch should not implement fetch(),
	//		but should instead implement a _fetchItems() method.  The _fetchItems()
	//		method takes three arguments, the keywordArgs object that was passed
	//		to fetch(), a callback function to be called when the result array is
	//		available, and an error callback to be called if something goes wrong.
	//		The _fetchItems() method should ignore any keywordArgs parameters for
	//		start, count, onBegin, onItem, onComplete, onError, sort, and scope.
	//		The _fetchItems() method needs to correctly handle any other keywordArgs
	//		parameters, including the query parameter and any optional parameters
	//		(such as includeChildren).  The _fetchItems() method should create an array of
	//		result items and pass it to the fetchHandler along with the original request object --
	//		or, the _fetchItems() method may, if it wants to, create an new request object
	//		with other specifics about the request that are specific to the datastore and pass
	//		that as the request object to the handler.
	//
	//		For more information on this specific function, see dojo/data/api/Read.fetch()
	//
	// request:
	//		The keywordArgs parameter may either be an instance of
	//		conforming to dojo/data/api/Request or may be a simple anonymous object
	//		that may contain any of the following:
	// |	{
	// |		query: query-object or query-string,
	// |		queryOptions: object,
	// |		onBegin: Function,
	// |		onItem: Function,
	// |		onComplete: Function,
	// |		onError: Function,
	// |		scope: object,
	// |		start: int
	// |		count: int
	// |		sort: array
	// |	}
	//		All implementations should accept keywordArgs objects with any of
	//		the 9 standard properties: query, onBegin, onItem, onComplete, onError
	//		scope, sort, start, and count.  Some implementations may accept additional
	//		properties in the keywordArgs object as valid parameters, such as
	//		{includeOutliers:true}.
	//
	//		####The *query* parameter
	//
	//		The query may be optional in some data store implementations.
	//		The dojo/data/api/Read API does not specify the syntax or semantics
	//		of the query itself -- each different data store implementation
	//		may have its own notion of what a query should look like.
	//		However, as of dojo 0.9, 1.0, and 1.1, all the provided datastores in dojo.data
	//		and dojox.data support an object structure query, where the object is a set of
	//		name/value parameters such as { attrFoo: valueBar, attrFoo1: valueBar1}.  Most of the
	//		dijit widgets, such as ComboBox assume this to be the case when working with a datastore
	//		when they dynamically update the query.  Therefore, for maximum compatibility with dijit
	//		widgets the recommended query parameter is a key/value object.  That does not mean that the
	//		the datastore may not take alternative query forms, such as a simple string, a Date, a number,
	//		or a mix of such.  Ultimately, The dojo/data/api/Read API is agnostic about what the query
	//		format.
	//
	//		Further note:  In general for query objects that accept strings as attribute
	//		value matches, the store should also support basic filtering capability, such as *
	//		(match any character) and ? (match single character).  An example query that is a query object
	//		would be like: { attrFoo: "value*"}.  Which generally means match all items where they have
	//		an attribute named attrFoo, with a value that starts with 'value'.
	//
	//		####The *queryOptions* parameter
	//
	//		The queryOptions parameter is an optional parameter used to specify options that may modify
	//		the query in some fashion, such as doing a case insensitive search, or doing a deep search
	//		where all items in a hierarchical representation of data are scanned instead of just the root
	//		items.  It currently defines two options that all datastores should attempt to honor if possible:
	// |	{
	// |		ignoreCase: boolean, // Whether or not the query should match case sensitively or not.  Default behaviour is false.
	// |		deep: boolean	// Whether or not a fetch should do a deep search of items and all child
	// |						// items instead of just root-level items in a datastore.  Default is false.
	// |	}
	//
	//		####The *onBegin* parameter.
	//
	//		function(size, request);
	//		If an onBegin callback function is provided, the callback function
	//		will be called just once, before the first onItem callback is called.
	//		The onBegin callback function will be passed two arguments, the
	//		the total number of items identified and the Request object.  If the total number is
	//		unknown, then size will be -1.  Note that size is not necessarily the size of the
	//		collection of items returned from the query, as the request may have specified to return only a
	//		subset of the total set of items through the use of the start and count parameters.
	//
	//		####The *onItem* parameter.
	//
	//		function(item, request);
	//
	//		If an onItem callback function is provided, the callback function
	//		will be called as each item in the result is received. The callback
	//		function will be passed two arguments: the item itself, and the
	//		Request object.
	//
	//		####The *onComplete* parameter.
	//
	//		function(items, request);
	//
	//		If an onComplete callback function is provided, the callback function
	//		will be called just once, after the last onItem callback is called.
	//		Note that if the onItem callback is not present, then onComplete will be passed
	//		an array containing all items which matched the query and the request object.
	//		If the onItem callback is present, then onComplete is called as:
	//		onComplete(null, request).
	//
	//		####The *onError* parameter.
	//
	//		function(errorData, request);
	//
	//		If an onError callback function is provided, the callback function
	//		will be called if there is any sort of error while attempting to
	//		execute the query.
	//		The onError callback function will be passed two arguments:
	//		an Error object and the Request object.
	//
	//		####The *scope* parameter.
	//
	//		If a scope object is provided, all of the callback functions (onItem,
	//		onComplete, onError, etc) will be invoked in the context of the scope
	//		object.  In the body of the callback function, the value of the "this"
	//		keyword will be the scope object.   If no scope object is provided,
	//		the callback functions will be called in the context of dojo.global().
	//		For example, onItem.call(scope, item, request) vs.
	//		onItem.call(dojo.global(), item, request)
	//
	//		####The *start* parameter.
	//
	//		If a start parameter is specified, this is a indication to the datastore to
	//		only start returning items once the start number of items have been located and
	//		skipped.  When this parameter is paired with 'count', the store should be able
	//		to page across queries with millions of hits by only returning subsets of the
	//		hits for each query
	//
	//		####The *count* parameter.
	//
	//		If a count parameter is specified, this is a indication to the datastore to
	//		only return up to that many items.  This allows a fetch call that may have
	//		millions of item matches to be paired down to something reasonable.
	//
	//		####The *sort* parameter.
	//
	//		If a sort parameter is specified, this is a indication to the datastore to
	//		sort the items in some manner before returning the items.  The array is an array of
	//		javascript objects that must conform to the following format to be applied to the
	//		fetching of items:
	// |	{
	// |		attribute: attribute || attribute-name-string,
	// |		descending: true|false;   // Optional.  Default is false.
	// |	}
	//		Note that when comparing attributes, if an item contains no value for the attribute
	//		(undefined), then it the default ascending sort logic should push it to the bottom
	//		of the list.  In the descending order case, it such items should appear at the top of the list.

	request = request || {};
	if(!request.store){
		request.store = this;
	}

	this._fetchItems(request, lang.hitch(this, "fetchHandler"), lang.hitch(this, "errorHandler"));
	return request;	// Object
};

return simpleFetch;
});

},
'dijit/form/_CheckBoxMixin':function(){
define("dijit/form/_CheckBoxMixin", [
	"dojo/_base/declare", // declare
	"dojo/dom-attr", // domAttr.set
	"dojo/_base/event" // event.stop
], function(declare, domAttr, event){

	// module:
	//		dijit/form/_CheckBoxMixin

	return declare("dijit.form._CheckBoxMixin", null, {
		// summary:
		//		Mixin to provide widget functionality corresponding to an HTML checkbox
		//
		// description:
		//		User interacts with real html inputs.
		//		On onclick (which occurs by mouse click, space-bar, or
		//		using the arrow keys to switch the selected radio button),
		//		we update the state of the checkbox/radio.
		//

		// type: [private] String
		//		type attribute on `<input>` node.
		//		Overrides `dijit/form/Button.type`.  Users should not change this value.
		type: "checkbox",

		// value: String
		//		As an initialization parameter, equivalent to value field on normal checkbox
		//		(if checked, the value is passed as the value when form is submitted).
		value: "on",

		// readOnly: Boolean
		//		Should this widget respond to user input?
		//		In markup, this is specified as "readOnly".
		//		Similar to disabled except readOnly form values are submitted.
		readOnly: false,
		
		// aria-pressed for toggle buttons, and aria-checked for checkboxes
		_aria_attr: "aria-checked",

		_setReadOnlyAttr: function(/*Boolean*/ value){
			this._set("readOnly", value);
			domAttr.set(this.focusNode, 'readOnly', value);
			this.focusNode.setAttribute("aria-readonly", value);
		},

		// Override dijit/form/Button._setLabelAttr() since we don't even have a containerNode.
		// Normally users won't try to set label, except when CheckBox or RadioButton is the child of a dojox/layout/TabContainer
		_setLabelAttr: undefined,

		_getSubmitValue: function(/*String*/ value){
			return !value && value !== 0 ? "on" : value;
		},

		_setValueAttr: function(newValue){
			newValue = this._getSubmitValue(newValue);	// "on" to match browser native behavior when value unspecified
			this._set("value", newValue);
			domAttr.set(this.focusNode, "value", newValue);
		},

		reset: function(){
			this.inherited(arguments);
			// Handle unlikely event that the <input type=checkbox> value attribute has changed
			this._set("value", this.params.value || "on");
			domAttr.set(this.focusNode, 'value', this.value);
		},

		_onClick: function(/*Event*/ e){
			// summary:
			//		Internal function to handle click actions - need to check
			//		readOnly, since button no longer does that check.
			if(this.readOnly){
				event.stop(e);
				return false;
			}
			return this.inherited(arguments);
		}
	});
});

},
'commsy/popups/ToggleStack':function(){
define("commsy/popups/ToggleStack", [	"dojo/_base/declare",
        	"commsy/WidgetPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, WidgetPopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(WidgetPopupHandler, {
		constructor: function(button_node, content_node) {
			// parent constructor is called automatically
			this.module = "stack";
			
			this.features = [ ];
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_stack_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_stack_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// add some widgets hardcoded
			var widgetArray = [
			    "widgets/StackStack",
			    "widgets/StackNew",
			    "widgets/StackTagView",
			    "widgets/StackBuzzwordView"
			];
			
			this.loadWidgetsManual(widgetArray).then(
				Lang.hitch(this, function(results) {
					// place widgets
					dojo.forEach(results, Lang.hitch(this, function(result, index, arr) {
						if (index === 0) {
							result[1].handle.placeAt(Query("div.widgetAreaLeft", this.contentNode)[0]);
						} else {
							result[1].handle.placeAt(Query("div.widgetAreaRight", this.contentNode)[0]);
						}
					}));
				})
			);
		}
	});
});
},
'commsy/popups/ClickTodoPopup':function(){
define("commsy/popups/ClickTodoPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/topic"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On, Topic) {
	return declare(ClickPopupHandler, {
		constructor: function() {

		},

		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "todo";
			this.editType = customObject.editType;
			this.contextId = customObject.contextId;

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
		},

		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			var search = {
				tabs: [
					{ id: "rights_tab" },
					{ id: "buzzwords_tab", group: "buzzwords" },
					{ id: "tags_tab", group: "tags" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[day_end]']", this.contentNode) },
				    { query: query("input[name='form_data[time_end]']", this.contentNode) },
				    { query: query("input[name='form_data[minutes]']", this.contentNode) },
				    { query: query("select[name='form_data[time_type]']", this.contentNode) },
				    { query: query("select[name='form_data[status]']", this.contentNode) },
				    { query: query("input.tabStatus", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) }
				]
			};

			this.submit(search, { contextId: this.contextId });
		},

		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					if (this.contextId) {
						this.close();
						Topic.publish("newOwnRoomItem", { itemId: item_id });
					} else {
						this.reload(item_id);
					};
				}));
			} else {
				if (this.contextId) {
					this.close();
					var aNode = query("a#listItem" + item_id)[0];
					if (aNode) {
						aNode.click();
					}
				} else {
					this.reload(item_id);
				};
			}
		}
	});
});
},
'dijit/_Contained':function(){
define("dijit/_Contained", [
	"dojo/_base/declare", // declare
	"./registry"	// registry.getEnclosingWidget(), registry.byNode()
], function(declare, registry){

	// module:
	//		dijit/_Contained

	return declare("dijit._Contained", null, {
		// summary:
		//		Mixin for widgets that are children of a container widget
		//
		// example:
		//	|	// make a basic custom widget that knows about it's parents
		//	|	declare("my.customClass",[dijit._Widget,dijit._Contained],{});

		_getSibling: function(/*String*/ which){
			// summary:
			//		Returns next or previous sibling
			// which:
			//		Either "next" or "previous"
			// tags:
			//		private
			var node = this.domNode;
			do{
				node = node[which+"Sibling"];
			}while(node && node.nodeType != 1);
			return node && registry.byNode(node);	// dijit/_WidgetBase
		},

		getPreviousSibling: function(){
			// summary:
			//		Returns null if this is the first child of the parent,
			//		otherwise returns the next element sibling to the "left".

			return this._getSibling("previous"); // dijit/_WidgetBase
		},

		getNextSibling: function(){
			// summary:
			//		Returns null if this is the last child of the parent,
			//		otherwise returns the next element sibling to the "right".

			return this._getSibling("next"); // dijit/_WidgetBase
		},

		getIndexInParent: function(){
			// summary:
			//		Returns the index of this widget within its container parent.
			//		It returns -1 if the parent does not exist, or if the parent
			//		is not a dijit._Container

			var p = this.getParent();
			if(!p || !p.getIndexOfChild){
				return -1; // int
			}
			return p.getIndexOfChild(this); // int
		}
	});
});

},
'commsy/DivExpander':function(){
define("commsy/DivExpander", [	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/dom-attr",
        	"dojo/dom-class",
        	"dojo/dom-style",
        	"dojo/query",
        	"dojo/on",
        	"dojo/fx",
        	"dojo/_base/lang"], function(declare, BaseClass, DomAttr, DomClass, DomStyle, Query, On, FX, Lang) {
	return declare(BaseClass, {
		display:	false,
		anim:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(objects) {
			dojo.forEach(objects, Lang.hitch(this, function(object, index, arr) {
				var actor = object.actor;
				var div = object.div;
				var img = Query("> img", actor)[0];
				
				On(actor, "click", Lang.hitch(this, function(event) {
					this.onEvent(actor, div, img);
					
					event.preventDefault();
				}));
			}));
		},
		
		onEvent: function(actor, div, img) {
			if(img) {
				if(DomStyle.get(div, "display") === "none" || DomClass.contains(div, "hidden")) {
					
					if(DomClass.contains(div, "hidden")) {
						DomStyle.set(div, "display", "none");
						DomClass.remove(div, "hidden");
					}
					
					FX.wipeIn({
						node:		div
					}).play();
					
					DomAttr.set(img, "src", this.from_php.template.tpl_path + "img/btn_ci_close.gif");
				} else {
					FX.wipeOut({
						node:		div
					}).play();
					
					DomAttr.set(img, "src", this.from_php.template.tpl_path + "img/btn_ci_open.gif");
				}
			}
		}
	});
});
},
'dijit/form/CheckBox':function(){
require({cache:{
'url:dijit/form/templates/CheckBox.html':"<div class=\"dijit dijitReset dijitInline\" role=\"presentation\"\n\t><input\n\t \t${!nameAttrSetting} type=\"${type}\" ${checkedAttrSetting}\n\t\tclass=\"dijitReset dijitCheckBoxInput\"\n\t\tdata-dojo-attach-point=\"focusNode\"\n\t \tdata-dojo-attach-event=\"onclick:_onClick\"\n/></div>\n"}});
define("dijit/form/CheckBox", [
	"require",
	"dojo/_base/declare", // declare
	"dojo/dom-attr", // domAttr.set
	"dojo/has",		// has("dijit-legacy-requires")
	"dojo/query", // query
	"dojo/ready",
	"./ToggleButton",
	"./_CheckBoxMixin",
	"dojo/text!./templates/CheckBox.html",
	"dojo/NodeList-dom" // NodeList.addClass/removeClass
], function(require, declare, domAttr, has, query, ready, ToggleButton, _CheckBoxMixin, template){

	// module:
	//		dijit/form/CheckBox

	// Back compat w/1.6, remove for 2.0
	if(has("dijit-legacy-requires")){
		ready(0, function(){
			var requires = ["dijit/form/RadioButton"];
			require(requires);	// use indirection so modules not rolled into a build
		});
	}

	return declare("dijit.form.CheckBox", [ToggleButton, _CheckBoxMixin], {
		// summary:
		//		Same as an HTML checkbox, but with fancy styling.
		//
		// description:
		//		User interacts with real html inputs.
		//		On onclick (which occurs by mouse click, space-bar, or
		//		using the arrow keys to switch the selected radio button),
		//		we update the state of the checkbox/radio.
		//
		//		There are two modes:
		//
		//		1. High contrast mode
		//		2. Normal mode
		//
		//		In case 1, the regular html inputs are shown and used by the user.
		//		In case 2, the regular html inputs are invisible but still used by
		//		the user. They are turned quasi-invisible and overlay the background-image.

		templateString: template,

		baseClass: "dijitCheckBox",

		_setValueAttr: function(/*String|Boolean*/ newValue, /*Boolean*/ priorityChange){
			// summary:
			//		Handler for value= attribute to constructor, and also calls to
			//		set('value', val).
			// description:
			//		During initialization, just saves as attribute to the `<input type=checkbox>`.
			//
			//		After initialization,
			//		when passed a boolean, controls whether or not the CheckBox is checked.
			//		If passed a string, changes the value attribute of the CheckBox (the one
			//		specified as "value" when the CheckBox was constructed
			//		(ex: `<input data-dojo-type="dijit/CheckBox" value="chicken">`).
			//
			//		`widget.set('value', string)` will check the checkbox and change the value to the
			//		specified string.
			//
			//		`widget.set('value', boolean)` will change the checked state.

			if(typeof newValue == "string"){
				this.inherited(arguments);
				newValue = true;
			}
			if(this._created){
				this.set('checked', newValue, priorityChange);
			}
		},
		_getValueAttr: function(){
			// summary:
			//		Hook so get('value') works.
			// description:
			//		If the CheckBox is checked, returns the value attribute.
			//		Otherwise returns false.
			return (this.checked ? this.value : false);
		},

		// Override behavior from Button, since we don't have an iconNode
		_setIconClassAttr: null,

		postMixInProperties: function(){
			this.inherited(arguments);

			// Need to set initial checked state as part of template, so that form submit works.
			// domAttr.set(node, "checked", bool) doesn't work on IE until node has been attached
			// to <body>, see #8666
			this.checkedAttrSetting = this.checked ? "checked" : "";
		},

		 _fillContent: function(){
			// Override Button::_fillContent() since it doesn't make sense for CheckBox,
			// since CheckBox doesn't even have a container
		},

		_onFocus: function(){
			if(this.id){
				query("label[for='"+this.id+"']").addClass("dijitFocusedLabel");
			}
			this.inherited(arguments);
		},

		_onBlur: function(){
			if(this.id){
				query("label[for='"+this.id+"']").removeClass("dijitFocusedLabel");
			}
			this.inherited(arguments);
		}
	});
});

},
'dijit/tree/_dndSelector':function(){
define("dijit/tree/_dndSelector", [
	"dojo/_base/array", // array.filter array.forEach array.map
	"dojo/_base/connect", // connect.isCopyKey
	"dojo/_base/declare", // declare
	"dojo/_base/Deferred", // Deferred
	"dojo/_base/kernel",	// global
	"dojo/_base/lang", // lang.hitch
	"dojo/cookie", // cookie
	"dojo/mouse", // mouse.isLeft
	"dojo/on",
	"dojo/touch",
	"./_dndContainer"
], function(array, connect, declare, Deferred, kernel, lang, cookie, mouse, on, touch, _dndContainer){

	// module:
	//		dijit/tree/_dndSelector


	return declare("dijit.tree._dndSelector", _dndContainer, {
		// summary:
		//		This is a base class for `dijit/tree/dndSource` , and isn't meant to be used directly.
		//		It's based on `dojo/dnd/Selector`.
		// tags:
		//		protected

		/*=====
		// selection: Object
		//		(id to DomNode) map for every TreeNode that's currently selected.
		//		The DOMNode is the TreeNode.rowNode.
		selection: {},
		=====*/

		constructor: function(){
			// summary:
			//		Initialization
			// tags:
			//		private

			this.selection={};
			this.anchor = null;

			if(!this.cookieName && this.tree.id){
				this.cookieName = this.tree.id + "SaveSelectedCookie";
			}

			this.events.push(
				on(this.tree.domNode, touch.press, lang.hitch(this,"onMouseDown")),
				on(this.tree.domNode, touch.release, lang.hitch(this,"onMouseUp")),
				on(this.tree.domNode, touch.move, lang.hitch(this,"onMouseMove"))
			);
		},

		// singular: Boolean
		//		Allows selection of only one element, if true.
		//		Tree hasn't been tested in singular=true mode, unclear if it works.
		singular: false,

		// methods
		getSelectedTreeNodes: function(){
			// summary:
			//		Returns a list of selected node(s).
			//		Used by dndSource on the start of a drag.
			// tags:
			//		protected
			var nodes=[], sel = this.selection;
			for(var i in sel){
				nodes.push(sel[i]);
			}
			return nodes;
		},

		selectNone: function(){
			// summary:
			//		Unselects all items
			// tags:
			//		private

			this.setSelection([]);
			return this;	// self
		},

		destroy: function(){
			// summary:
			//		Prepares the object to be garbage-collected
			this.inherited(arguments);
			this.selection = this.anchor = null;
		},
		addTreeNode: function(/*dijit/Tree._TreeNode*/ node, /*Boolean?*/isAnchor){
			// summary:
			//		add node to current selection
			// node: Node
			//		node to add
			// isAnchor: Boolean
			//		Whether the node should become anchor.

			this.setSelection(this.getSelectedTreeNodes().concat( [node] ));
			if(isAnchor){ this.anchor = node; }
			return node;
		},
		removeTreeNode: function(/*dijit/Tree._TreeNode*/ node){
			// summary:
			//		remove node from current selection
			// node: Node
			//		node to remove
			this.setSelection(this._setDifference(this.getSelectedTreeNodes(), [node]));
			return node;
		},
		isTreeNodeSelected: function(/*dijit/Tree._TreeNode*/ node){
			// summary:
			//		return true if node is currently selected
			// node: Node
			//		the node to check whether it's in the current selection

			return node.id && !!this.selection[node.id];
		},
		setSelection: function(/*dijit/Tree._TreeNode[]*/ newSelection){
			// summary:
			//		set the list of selected nodes to be exactly newSelection. All changes to the
			//		selection should be passed through this function, which ensures that derived
			//		attributes are kept up to date. Anchor will be deleted if it has been removed
			//		from the selection, but no new anchor will be added by this function.
			// newSelection: Node[]
			//		list of tree nodes to make selected
			var oldSelection = this.getSelectedTreeNodes();
			array.forEach(this._setDifference(oldSelection, newSelection), lang.hitch(this, function(node){
				node.setSelected(false);
				if(this.anchor == node){
					delete this.anchor;
				}
				delete this.selection[node.id];
			}));
			array.forEach(this._setDifference(newSelection, oldSelection), lang.hitch(this, function(node){
				node.setSelected(true);
				this.selection[node.id] = node;
			}));
			this._updateSelectionProperties();
		},
		_setDifference: function(xs,ys){
			// summary:
			//		Returns a copy of xs which lacks any objects
			//		occurring in ys. Checks for membership by
			//		modifying and then reading the object, so it will
			//		not properly handle sets of numbers or strings.

			array.forEach(ys, function(y){ y.__exclude__ = true; });
			var ret = array.filter(xs, function(x){ return !x.__exclude__; });

			// clean up after ourselves.
			array.forEach(ys, function(y){ delete y['__exclude__'] });
			return ret;
		},
		_updateSelectionProperties: function(){
			// summary:
			//		Update the following tree properties from the current selection:
			//		path[s], selectedItem[s], selectedNode[s]

			var selected = this.getSelectedTreeNodes();
			var paths = [], nodes = [], selects = [];
			array.forEach(selected, function(node){
				var ary = node.getTreePath(), model = this.tree.model;
				nodes.push(node);
				paths.push(ary);
				ary = array.map(ary, function(item){
					return model.getIdentity(item);
				}, this);
				selects.push(ary.join("/"))
			}, this);
			var items = array.map(nodes,function(node){ return node.item; });
			this.tree._set("paths", paths);
			this.tree._set("path", paths[0] || []);
			this.tree._set("selectedNodes", nodes);
			this.tree._set("selectedNode", nodes[0] || null);
			this.tree._set("selectedItems", items);
			this.tree._set("selectedItem", items[0] || null);
            if (this.tree.persist && selects.length > 0) {
                cookie(this.cookieName, selects.join(","), {expires:365});
            }
		},
		_getSavedPaths: function(){
			// summary:
			//		Returns paths of nodes that were selected previously and saved in the cookie.

			var tree = this.tree;
			if(tree.persist && tree.dndController.cookieName){
				var oreo, paths = [];
				oreo = cookie(tree.dndController.cookieName);
				if(oreo){
					paths = array.map(oreo.split(","), function(path){
					   return path.split("/");
					})
				}
				return paths;
			}
		},
		// mouse events
		onMouseDown: function(e){
			// summary:
			//		Event processor for onmousedown/ontouchstart
			// e: Event
			//		onmousedown/ontouchstart event
			// tags:
			//		protected

			// ignore click on expando node
			if(!this.current || this.tree.isExpandoNode(e.target, this.current)){ return; }

			// ignore right-click
			if(e.type != "touchstart" && !mouse.isLeft(e)){ return; }

			e.preventDefault();

			var treeNode = this.current,
			  copy = connect.isCopyKey(e), id = treeNode.id;

			// if shift key is not pressed, and the node is already in the selection,
			// delay deselection until onmouseup so in the case of DND, deselection
			// will be canceled by onmousemove.
			if(!this.singular && !e.shiftKey && this.selection[id]){
				this._doDeselect = true;
				return;
			}else{
				this._doDeselect = false;
			}
			this.userSelect(treeNode, copy, e.shiftKey);
		},

		onMouseUp: function(e){
			// summary:
			//		Event processor for onmouseup/ontouchend
			// e: Event
			//		onmouseup/ontouchend event
			// tags:
			//		protected

			// _doDeselect is the flag to indicate that the user wants to either ctrl+click on
			// a already selected item (to deselect the item), or click on a not-yet selected item
			// (which should remove all current selection, and add the clicked item). This can not
			// be done in onMouseDown, because the user may start a drag after mousedown. By moving
			// the deselection logic here, the user can drags an already selected item.
			if(!this._doDeselect){ return; }
			this._doDeselect = false;
			this.userSelect(this.current, connect.isCopyKey(e), e.shiftKey);
		},
		onMouseMove: function(/*===== e =====*/){
			// summary:
			//		event processor for onmousemove/ontouchmove
			// e: Event
			//		onmousemove/ontouchmove event
			this._doDeselect = false;
		},

		_compareNodes: function(n1, n2){
			if(n1 === n2){
				return 0;
			}

			if('sourceIndex' in document.documentElement){ //IE
				//TODO: does not yet work if n1 and/or n2 is a text node
				return n1.sourceIndex - n2.sourceIndex;
			}else if('compareDocumentPosition' in document.documentElement){ //FF, Opera
				return n1.compareDocumentPosition(n2) & 2 ? 1: -1;
			}else if(document.createRange){ //Webkit
				var r1 = doc.createRange();
				r1.setStartBefore(n1);

				var r2 = doc.createRange();
				r2.setStartBefore(n2);

				return r1.compareBoundaryPoints(r1.END_TO_END, r2);
			}else{
				throw Error("dijit.tree._compareNodes don't know how to compare two different nodes in this browser");
			}
		},

		userSelect: function(node, multi, range){
			// summary:
			//		Add or remove the given node from selection, responding
			//		to a user action such as a click or keypress.
			// multi: Boolean
			//		Indicates whether this is meant to be a multi-select action (e.g. ctrl-click)
			// range: Boolean
			//		Indicates whether this is meant to be a ranged action (e.g. shift-click)
			// tags:
			//		protected

			if(this.singular){
				if(this.anchor == node && multi){
					this.selectNone();
				}else{
					this.setSelection([node]);
					this.anchor = node;
				}
			}else{
				if(range && this.anchor){
					var cr = this._compareNodes(this.anchor.rowNode, node.rowNode),
					begin, end, anchor = this.anchor;

					if(cr < 0){ //current is after anchor
						begin = anchor;
						end = node;
					}else{ //current is before anchor
						begin = node;
						end = anchor;
					}
					var nodes = [];
					//add everything betweeen begin and end inclusively
					while(begin != end){
						nodes.push(begin);
						begin = this.tree._getNextNode(begin);
					}
					nodes.push(end);

					this.setSelection(nodes);
				}else{
					if( this.selection[ node.id ] && multi ){
						this.removeTreeNode( node );
					}else if(multi){
						this.addTreeNode(node, true);
					}else{
						this.setSelection([node]);
						this.anchor = node;
					}
				}
			}
		},

		getItem: function(/*String*/ key){
			// summary:
			//		Returns the dojo/dnd/Container._Item (representing a dragged node) by it's key (id).
			//		Called by dojo/dnd/Source.checkAcceptance().
			// tags:
			//		protected

			var widget = this.selection[key];
			return {
				data: widget,
				type: ["treeNode"]
			}; // dojo/dnd/Container._Item
		},

		forInSelectedItems: function(/*Function*/ f, /*Object?*/ o){
			// summary:
			//		Iterates over selected items;
			//		see `dojo/dnd/Container.forInItems()` for details
			o = o || kernel.global;
			for(var id in this.selection){
				// console.log("selected item id: " + id);
				f.call(o, this.getItem(id), id, this);
			}
		}
	});
});

},
'dojo/dnd/Source':function(){
define("dojo/dnd/Source", [
	"../_base/array", "../_base/connect", "../_base/declare", "../_base/kernel", "../_base/lang",
	"../dom-class", "../dom-geometry", "../mouse", "../ready", "../topic",
	"./common", "./Selector", "./Manager"
], function(array, connect, declare, kernel, lang, domClass, domGeom, mouse, ready, topic,
			dnd, Selector, Manager){

// module:
//		dojo/dnd/Source

/*
	Container property:
		"Horizontal"- if this is the horizontal container
	Source states:
		""			- normal state
		"Moved"		- this source is being moved
		"Copied"	- this source is being copied
	Target states:
		""			- normal state
		"Disabled"	- the target cannot accept an avatar
	Target anchor state:
		""			- item is not selected
		"Before"	- insert point is before the anchor
		"After"		- insert point is after the anchor
*/

/*=====
var __SourceArgs = {
	// summary:
	//		a dict of parameters for DnD Source configuration. Note that any
	//		property on Source elements may be configured, but this is the
	//		short-list
	// isSource: Boolean?
	//		can be used as a DnD source. Defaults to true.
	// accept: Array?
	//		list of accepted types (text strings) for a target; defaults to
	//		["text"]
	// autoSync: Boolean
	//		if true refreshes the node list on every operation; false by default
	// copyOnly: Boolean?
	//		copy items, if true, use a state of Ctrl key otherwise,
	//		see selfCopy and selfAccept for more details
	// delay: Number
	//		the move delay in pixels before detecting a drag; 0 by default
	// horizontal: Boolean?
	//		a horizontal container, if true, vertical otherwise or when omitted
	// selfCopy: Boolean?
	//		copy items by default when dropping on itself,
	//		false by default, works only if copyOnly is true
	// selfAccept: Boolean?
	//		accept its own items when copyOnly is true,
	//		true by default, works only if copyOnly is true
	// withHandles: Boolean?
	//		allows dragging only by handles, false by default
	// generateText: Boolean?
	//		generate text node for drag and drop, true by default
};
=====*/

// For back-compat, remove in 2.0.
if(!kernel.isAsync){
	ready(0, function(){
		var requires = ["dojo/dnd/AutoSource", "dojo/dnd/Target"];
		require(requires);	// use indirection so modules not rolled into a build
	});
}

var Source = declare("dojo.dnd.Source", Selector, {
	// summary:
	//		a Source object, which can be used as a DnD source, or a DnD target

	// object attributes (for markup)
	isSource: true,
	horizontal: false,
	copyOnly: false,
	selfCopy: false,
	selfAccept: true,
	skipForm: false,
	withHandles: false,
	autoSync: false,
	delay: 0, // pixels
	accept: ["text"],
	generateText: true,

	constructor: function(/*DOMNode|String*/ node, /*__SourceArgs?*/ params){
		// summary:
		//		a constructor of the Source
		// node:
		//		node or node's id to build the source on
		// params:
		//		any property of this class may be configured via the params
		//		object which is mixed-in to the `dojo/dnd/Source` instance
		lang.mixin(this, lang.mixin({}, params));
		var type = this.accept;
		if(type.length){
			this.accept = {};
			for(var i = 0; i < type.length; ++i){
				this.accept[type[i]] = 1;
			}
		}
		// class-specific variables
		this.isDragging = false;
		this.mouseDown = false;
		this.targetAnchor = null;
		this.targetBox = null;
		this.before = true;
		this._lastX = 0;
		this._lastY = 0;
		// states
		this.sourceState  = "";
		if(this.isSource){
			domClass.add(this.node, "dojoDndSource");
		}
		this.targetState  = "";
		if(this.accept){
			domClass.add(this.node, "dojoDndTarget");
		}
		if(this.horizontal){
			domClass.add(this.node, "dojoDndHorizontal");
		}
		// set up events
		this.topics = [
			topic.subscribe("/dnd/source/over", lang.hitch(this, "onDndSourceOver")),
			topic.subscribe("/dnd/start",  lang.hitch(this, "onDndStart")),
			topic.subscribe("/dnd/drop",   lang.hitch(this, "onDndDrop")),
			topic.subscribe("/dnd/cancel", lang.hitch(this, "onDndCancel"))
		];
	},

	// methods
	checkAcceptance: function(source, nodes){
		// summary:
		//		checks if the target can accept nodes from this source
		// source: Object
		//		the source which provides items
		// nodes: Array
		//		the list of transferred items
		if(this == source){
			return !this.copyOnly || this.selfAccept;
		}
		for(var i = 0; i < nodes.length; ++i){
			var type = source.getItem(nodes[i].id).type;
			// type instanceof Array
			var flag = false;
			for(var j = 0; j < type.length; ++j){
				if(type[j] in this.accept){
					flag = true;
					break;
				}
			}
			if(!flag){
				return false;	// Boolean
			}
		}
		return true;	// Boolean
	},
	copyState: function(keyPressed, self){
		// summary:
		//		Returns true if we need to copy items, false to move.
		//		It is separated to be overwritten dynamically, if needed.
		// keyPressed: Boolean
		//		the "copy" key was pressed
		// self: Boolean?
		//		optional flag that means that we are about to drop on itself

		if(keyPressed){ return true; }
		if(arguments.length < 2){
			self = this == Manager.manager().target;
		}
		if(self){
			if(this.copyOnly){
				return this.selfCopy;
			}
		}else{
			return this.copyOnly;
		}
		return false;	// Boolean
	},
	destroy: function(){
		// summary:
		//		prepares the object to be garbage-collected
		Source.superclass.destroy.call(this);
		array.forEach(this.topics, function(t){t.remove();});
		this.targetAnchor = null;
	},

	// mouse event processors
	onMouseMove: function(e){
		// summary:
		//		event processor for onmousemove
		// e: Event
		//		mouse event
		if(this.isDragging && this.targetState == "Disabled"){ return; }
		Source.superclass.onMouseMove.call(this, e);
		var m = Manager.manager();
		if(!this.isDragging){
			if(this.mouseDown && this.isSource &&
					(Math.abs(e.pageX - this._lastX) > this.delay || Math.abs(e.pageY - this._lastY) > this.delay)){
				var nodes = this.getSelectedNodes();
				if(nodes.length){
					m.startDrag(this, nodes, this.copyState(dnd.getCopyKeyState(e), true));
				}
			}
		}
		if(this.isDragging){
			// calculate before/after
			var before = false;
			if(this.current){
				if(!this.targetBox || this.targetAnchor != this.current){
					this.targetBox = domGeom.position(this.current, true);
				}
				if(this.horizontal){
					// In LTR mode, the left part of the object means "before", but in RTL mode it means "after".
					before = (e.pageX - this.targetBox.x < this.targetBox.w / 2) == domGeom.isBodyLtr(this.current.ownerDocument);
				}else{
					before = (e.pageY - this.targetBox.y) < (this.targetBox.h / 2);
				}
			}
			if(this.current != this.targetAnchor || before != this.before){
				this._markTargetAnchor(before);
				m.canDrop(!this.current || m.source != this || !(this.current.id in this.selection));
			}
		}
	},
	onMouseDown: function(e){
		// summary:
		//		event processor for onmousedown
		// e: Event
		//		mouse event
		if(!this.mouseDown && this._legalMouseDown(e) && (!this.skipForm || !dnd.isFormElement(e))){
			this.mouseDown = true;
			this._lastX = e.pageX;
			this._lastY = e.pageY;
			Source.superclass.onMouseDown.call(this, e);
		}
	},
	onMouseUp: function(e){
		// summary:
		//		event processor for onmouseup
		// e: Event
		//		mouse event
		if(this.mouseDown){
			this.mouseDown = false;
			Source.superclass.onMouseUp.call(this, e);
		}
	},

	// topic event processors
	onDndSourceOver: function(source){
		// summary:
		//		topic event processor for /dnd/source/over, called when detected a current source
		// source: Object
		//		the source which has the mouse over it
		if(this !== source){
			this.mouseDown = false;
			if(this.targetAnchor){
				this._unmarkTargetAnchor();
			}
		}else if(this.isDragging){
			var m = Manager.manager();
			m.canDrop(this.targetState != "Disabled" && (!this.current || m.source != this || !(this.current.id in this.selection)));
		}
	},
	onDndStart: function(source, nodes, copy){
		// summary:
		//		topic event processor for /dnd/start, called to initiate the DnD operation
		// source: Object
		//		the source which provides items
		// nodes: Array
		//		the list of transferred items
		// copy: Boolean
		//		copy items, if true, move items otherwise
		if(this.autoSync){ this.sync(); }
		if(this.isSource){
			this._changeState("Source", this == source ? (copy ? "Copied" : "Moved") : "");
		}
		var accepted = this.accept && this.checkAcceptance(source, nodes);
		this._changeState("Target", accepted ? "" : "Disabled");
		if(this == source){
			Manager.manager().overSource(this);
		}
		this.isDragging = true;
	},
	onDndDrop: function(source, nodes, copy, target){
		// summary:
		//		topic event processor for /dnd/drop, called to finish the DnD operation
		// source: Object
		//		the source which provides items
		// nodes: Array
		//		the list of transferred items
		// copy: Boolean
		//		copy items, if true, move items otherwise
		// target: Object
		//		the target which accepts items
		if(this == target){
			// this one is for us => move nodes!
			this.onDrop(source, nodes, copy);
		}
		this.onDndCancel();
	},
	onDndCancel: function(){
		// summary:
		//		topic event processor for /dnd/cancel, called to cancel the DnD operation
		if(this.targetAnchor){
			this._unmarkTargetAnchor();
			this.targetAnchor = null;
		}
		this.before = true;
		this.isDragging = false;
		this.mouseDown = false;
		this._changeState("Source", "");
		this._changeState("Target", "");
	},

	// local events
	onDrop: function(source, nodes, copy){
		// summary:
		//		called only on the current target, when drop is performed
		// source: Object
		//		the source which provides items
		// nodes: Array
		//		the list of transferred items
		// copy: Boolean
		//		copy items, if true, move items otherwise

		if(this != source){
			this.onDropExternal(source, nodes, copy);
		}else{
			this.onDropInternal(nodes, copy);
		}
	},
	onDropExternal: function(source, nodes, copy){
		// summary:
		//		called only on the current target, when drop is performed
		//		from an external source
		// source: Object
		//		the source which provides items
		// nodes: Array
		//		the list of transferred items
		// copy: Boolean
		//		copy items, if true, move items otherwise

		var oldCreator = this._normalizedCreator;
		// transferring nodes from the source to the target
		if(this.creator){
			// use defined creator
			this._normalizedCreator = function(node, hint){
				return oldCreator.call(this, source.getItem(node.id).data, hint);
			};
		}else{
			// we have no creator defined => move/clone nodes
			if(copy){
				// clone nodes
				this._normalizedCreator = function(node /*=====, hint =====*/){
					var t = source.getItem(node.id);
					var n = node.cloneNode(true);
					n.id = dnd.getUniqueId();
					return {node: n, data: t.data, type: t.type};
				};
			}else{
				// move nodes
				this._normalizedCreator = function(node /*=====, hint =====*/){
					var t = source.getItem(node.id);
					source.delItem(node.id);
					return {node: node, data: t.data, type: t.type};
				};
			}
		}
		this.selectNone();
		if(!copy && !this.creator){
			source.selectNone();
		}
		this.insertNodes(true, nodes, this.before, this.current);
		if(!copy && this.creator){
			source.deleteSelectedNodes();
		}
		this._normalizedCreator = oldCreator;
	},
	onDropInternal: function(nodes, copy){
		// summary:
		//		called only on the current target, when drop is performed
		//		from the same target/source
		// nodes: Array
		//		the list of transferred items
		// copy: Boolean
		//		copy items, if true, move items otherwise

		var oldCreator = this._normalizedCreator;
		// transferring nodes within the single source
		if(this.current && this.current.id in this.selection){
			// do nothing
			return;
		}
		if(copy){
			if(this.creator){
				// create new copies of data items
				this._normalizedCreator = function(node, hint){
					return oldCreator.call(this, this.getItem(node.id).data, hint);
				};
			}else{
				// clone nodes
				this._normalizedCreator = function(node/*=====, hint =====*/){
					var t = this.getItem(node.id);
					var n = node.cloneNode(true);
					n.id = dnd.getUniqueId();
					return {node: n, data: t.data, type: t.type};
				};
			}
		}else{
			// move nodes
			if(!this.current){
				// do nothing
				return;
			}
			this._normalizedCreator = function(node /*=====, hint =====*/){
				var t = this.getItem(node.id);
				return {node: node, data: t.data, type: t.type};
			};
		}
		this._removeSelection();
		this.insertNodes(true, nodes, this.before, this.current);
		this._normalizedCreator = oldCreator;
	},
	onDraggingOver: function(){
		// summary:
		//		called during the active DnD operation, when items
		//		are dragged over this target, and it is not disabled
	},
	onDraggingOut: function(){
		// summary:
		//		called during the active DnD operation, when items
		//		are dragged away from this target, and it is not disabled
	},

	// utilities
	onOverEvent: function(){
		// summary:
		//		this function is called once, when mouse is over our container
		Source.superclass.onOverEvent.call(this);
		Manager.manager().overSource(this);
		if(this.isDragging && this.targetState != "Disabled"){
			this.onDraggingOver();
		}
	},
	onOutEvent: function(){
		// summary:
		//		this function is called once, when mouse is out of our container
		Source.superclass.onOutEvent.call(this);
		Manager.manager().outSource(this);
		if(this.isDragging && this.targetState != "Disabled"){
			this.onDraggingOut();
		}
	},
	_markTargetAnchor: function(before){
		// summary:
		//		assigns a class to the current target anchor based on "before" status
		// before: Boolean
		//		insert before, if true, after otherwise
		if(this.current == this.targetAnchor && this.before == before){ return; }
		if(this.targetAnchor){
			this._removeItemClass(this.targetAnchor, this.before ? "Before" : "After");
		}
		this.targetAnchor = this.current;
		this.targetBox = null;
		this.before = before;
		if(this.targetAnchor){
			this._addItemClass(this.targetAnchor, this.before ? "Before" : "After");
		}
	},
	_unmarkTargetAnchor: function(){
		// summary:
		//		removes a class of the current target anchor based on "before" status
		if(!this.targetAnchor){ return; }
		this._removeItemClass(this.targetAnchor, this.before ? "Before" : "After");
		this.targetAnchor = null;
		this.targetBox = null;
		this.before = true;
	},
	_markDndStatus: function(copy){
		// summary:
		//		changes source's state based on "copy" status
		this._changeState("Source", copy ? "Copied" : "Moved");
	},
	_legalMouseDown: function(e){
		// summary:
		//		checks if user clicked on "approved" items
		// e: Event
		//		mouse event

		// accept only the left mouse button, or the left finger
		if(e.type != "touchstart" && !mouse.isLeft(e)){ return false; }

		if(!this.withHandles){ return true; }

		// check for handles
		for(var node = e.target; node && node !== this.node; node = node.parentNode){
			if(domClass.contains(node, "dojoDndHandle")){ return true; }
			if(domClass.contains(node, "dojoDndItem") || domClass.contains(node, "dojoDndIgnore")){ break; }
		}
		return false;	// Boolean
	}
});

return Source;

});

},
'dojo/data/ItemFileReadStore':function(){
define("dojo/data/ItemFileReadStore", ["../_base/kernel", "../_base/lang", "../_base/declare", "../_base/array", "../_base/xhr",
	"../Evented", "./util/filter", "./util/simpleFetch", "../date/stamp"
], function(kernel, lang, declare, array, xhr, Evented, filterUtil, simpleFetch, dateStamp){

// module:
//		dojo/data/ItemFileReadStore

var ItemFileReadStore = declare("dojo.data.ItemFileReadStore", [Evented],{
	// summary:
	//		The ItemFileReadStore implements the dojo/data/api/Read API and reads
	//		data from JSON files that have contents in this format --
	// |	{ items: [
	// |		{ name:'Kermit', color:'green', age:12, friends:['Gonzo', {_reference:{name:'Fozzie Bear'}}]},
	// |		{ name:'Fozzie Bear', wears:['hat', 'tie']},
	// |		{ name:'Miss Piggy', pets:'Foo-Foo'}
	// |	]}
	//		Note that it can also contain an 'identifier' property that specified which attribute on the items
	//		in the array of items that acts as the unique identifier for that item.

	constructor: function(/* Object */ keywordParameters){
		// summary:
		//		constructor
		// keywordParameters:
		//		{url: String} {data: jsonObject} {typeMap: object}
		//		The structure of the typeMap object is as follows:
		// |	{
		// |		type0: function || object,
		// |		type1: function || object,
		// |		...
		// |		typeN: function || object
		// |	}
		//		Where if it is a function, it is assumed to be an object constructor that takes the
		//		value of _value as the initialization parameters.  If it is an object, then it is assumed
		//		to be an object of general form:
		// |	{
		// |		type: function, //constructor.
		// |		deserialize:	function(value) //The function that parses the value and constructs the object defined by type appropriately.
		// |	}

		this._arrayOfAllItems = [];
		this._arrayOfTopLevelItems = [];
		this._loadFinished = false;
		this._jsonFileUrl = keywordParameters.url;
		this._ccUrl = keywordParameters.url;
		this.url = keywordParameters.url;
		this._jsonData = keywordParameters.data;
		this.data = null;
		this._datatypeMap = keywordParameters.typeMap || {};
		if(!this._datatypeMap['Date']){
			//If no default mapping for dates, then set this as default.
			//We use the dojo/date/stamp here because the ISO format is the 'dojo way'
			//of generically representing dates.
			this._datatypeMap['Date'] = {
				type: Date,
				deserialize: function(value){
					return dateStamp.fromISOString(value);
				}
			};
		}
		this._features = {'dojo.data.api.Read':true, 'dojo.data.api.Identity':true};
		this._itemsByIdentity = null;
		this._storeRefPropName = "_S"; // Default name for the store reference to attach to every item.
		this._itemNumPropName = "_0"; // Default Item Id for isItem to attach to every item.
		this._rootItemPropName = "_RI"; // Default Item Id for isItem to attach to every item.
		this._reverseRefMap = "_RRM"; // Default attribute for constructing a reverse reference map for use with reference integrity
		this._loadInProgress = false; //Got to track the initial load to prevent duelling loads of the dataset.
		this._queuedFetches = [];
		if(keywordParameters.urlPreventCache !== undefined){
			this.urlPreventCache = keywordParameters.urlPreventCache?true:false;
		}
		if(keywordParameters.hierarchical !== undefined){
			this.hierarchical = keywordParameters.hierarchical?true:false;
		}
		if(keywordParameters.clearOnClose){
			this.clearOnClose = true;
		}
		if("failOk" in keywordParameters){
			this.failOk = keywordParameters.failOk?true:false;
		}
	},

	url: "",	// use "" rather than undefined for the benefit of the parser (#3539)

	//Internal var, crossCheckUrl.  Used so that setting either url or _jsonFileUrl, can still trigger a reload
	//when clearOnClose and close is used.
	_ccUrl: "",

	data: null,	// define this so that the parser can populate it

	typeMap: null, //Define so parser can populate.

	// clearOnClose: Boolean
	//		Parameter to allow users to specify if a close call should force a reload or not.
	//		By default, it retains the old behavior of not clearing if close is called.  But
	//		if set true, the store will be reset to default state.  Note that by doing this,
	//		all item handles will become invalid and a new fetch must be issued.
	clearOnClose: false,

	// urlPreventCache: Boolean
	//		Parameter to allow specifying if preventCache should be passed to the xhrGet call or not when loading data from a url.
	//		Note this does not mean the store calls the server on each fetch, only that the data load has preventCache set as an option.
	//		Added for tracker: #6072
	urlPreventCache: false,

	// failOk: Boolean
	//		Parameter for specifying that it is OK for the xhrGet call to fail silently.
	failOk: false,

	// hierarchical: Boolean
	//		Parameter to indicate to process data from the url as hierarchical
	//		(data items can contain other data items in js form).  Default is true
	//		for backwards compatibility.  False means only root items are processed
	//		as items, all child objects outside of type-mapped objects and those in
	//		specific reference format, are left straight JS data objects.
	hierarchical: true,

	_assertIsItem: function(/* dojo/data/api/Item */ item){
		// summary:
		//		This function tests whether the item passed in is indeed an item in the store.
		// item:
		//		The item to test for being contained by the store.
		if(!this.isItem(item)){
			throw new Error(this.declaredClass + ": Invalid item argument.");
		}
	},

	_assertIsAttribute: function(/* attribute-name-string */ attribute){
		// summary:
		//		This function tests whether the item passed in is indeed a valid 'attribute' like type for the store.
		// attribute:
		//		The attribute to test for being contained by the store.
		if(typeof attribute !== "string"){
			throw new Error(this.declaredClass + ": Invalid attribute argument.");
		}
	},

	getValue: function(	/* dojo/data/api/Item */ item,
						   /* attribute-name-string */ attribute,
						   /* value? */ defaultValue){
		// summary:
		//		See dojo/data/api/Read.getValue()
		var values = this.getValues(item, attribute);
		return (values.length > 0)?values[0]:defaultValue; // mixed
	},

	getValues: function(/* dojo/data/api/Item */ item,
						/* attribute-name-string */ attribute){
		// summary:
		//		See dojo/data/api/Read.getValues()

		this._assertIsItem(item);
		this._assertIsAttribute(attribute);
		// Clone it before returning.  refs: #10474
		return (item[attribute] || []).slice(0); // Array
	},

	getAttributes: function(/* dojo/data/api/Item */ item){
		// summary:
		//		See dojo/data/api/Read.getAttributes()
		this._assertIsItem(item);
		var attributes = [];
		for(var key in item){
			// Save off only the real item attributes, not the special id marks for O(1) isItem.
			if((key !== this._storeRefPropName) && (key !== this._itemNumPropName) && (key !== this._rootItemPropName) && (key !== this._reverseRefMap)){
				attributes.push(key);
			}
		}
		return attributes; // Array
	},

	hasAttribute: function(	/* dojo/data/api/Item */ item,
							   /* attribute-name-string */ attribute){
		// summary:
		//		See dojo/data/api/Read.hasAttribute()
		this._assertIsItem(item);
		this._assertIsAttribute(attribute);
		return (attribute in item);
	},

	containsValue: function(/* dojo/data/api/Item */ item,
							/* attribute-name-string */ attribute,
							/* anything */ value){
		// summary:
		//		See dojo/data/api/Read.containsValue()
		var regexp = undefined;
		if(typeof value === "string"){
			regexp = filterUtil.patternToRegExp(value, false);
		}
		return this._containsValue(item, attribute, value, regexp); //boolean.
	},

	_containsValue: function(	/* dojo/data/api/Item */ item,
								 /* attribute-name-string */ attribute,
								 /* anything */ value,
								 /* RegExp?*/ regexp){
		// summary:
		//		Internal function for looking at the values contained by the item.
		// description:
		//		Internal function for looking at the values contained by the item.  This
		//		function allows for denoting if the comparison should be case sensitive for
		//		strings or not (for handling filtering cases where string case should not matter)
		// item:
		//		The data item to examine for attribute values.
		// attribute:
		//		The attribute to inspect.
		// value:
		//		The value to match.
		// regexp:
		//		Optional regular expression generated off value if value was of string type to handle wildcarding.
		//		If present and attribute values are string, then it can be used for comparison instead of 'value'
		return array.some(this.getValues(item, attribute), function(possibleValue){
			if(possibleValue !== null && !lang.isObject(possibleValue) && regexp){
				if(possibleValue.toString().match(regexp)){
					return true; // Boolean
				}
			}else if(value === possibleValue){
				return true; // Boolean
			}
		});
	},

	isItem: function(/* anything */ something){
		// summary:
		//		See dojo/data/api/Read.isItem()
		if(something && something[this._storeRefPropName] === this){
			if(this._arrayOfAllItems[something[this._itemNumPropName]] === something){
				return true;
			}
		}
		return false; // Boolean
	},

	isItemLoaded: function(/* anything */ something){
		// summary:
		//		See dojo/data/api/Read.isItemLoaded()
		return this.isItem(something); //boolean
	},

	loadItem: function(/* object */ keywordArgs){
		// summary:
		//		See dojo/data/api/Read.loadItem()
		this._assertIsItem(keywordArgs.item);
	},

	getFeatures: function(){
		// summary:
		//		See dojo/data/api/Read.getFeatures()
		return this._features; //Object
	},

	getLabel: function(/* dojo/data/api/Item */ item){
		// summary:
		//		See dojo/data/api/Read.getLabel()
		if(this._labelAttr && this.isItem(item)){
			return this.getValue(item,this._labelAttr); //String
		}
		return undefined; //undefined
	},

	getLabelAttributes: function(/* dojo/data/api/Item */ item){
		// summary:
		//		See dojo/data/api/Read.getLabelAttributes()
		if(this._labelAttr){
			return [this._labelAttr]; //array
		}
		return null; //null
	},

	filter: function(/* Object */ requestArgs, /* item[] */ arrayOfItems, /* Function */ findCallback){
		// summary:
		//		This method handles the basic filtering needs for ItemFile* based stores.
		var items = [],
			i, key;

		if(requestArgs.query){
			var value,
				ignoreCase = requestArgs.queryOptions ? requestArgs.queryOptions.ignoreCase : false;

			//See if there are any string values that can be regexp parsed first to avoid multiple regexp gens on the
			//same value for each item examined.  Much more efficient.
			var regexpList = {};
			for(key in requestArgs.query){
				value = requestArgs.query[key];
				if(typeof value === "string"){
					regexpList[key] = filterUtil.patternToRegExp(value, ignoreCase);
				}else if(value instanceof RegExp){
					regexpList[key] = value;
				}
			}
			for(i = 0; i < arrayOfItems.length; ++i){
				var match = true;
				var candidateItem = arrayOfItems[i];
				if(candidateItem === null){
					match = false;
				}else{
					for(key in requestArgs.query){
						value = requestArgs.query[key];
						if(!this._containsValue(candidateItem, key, value, regexpList[key])){
							match = false;
						}
					}
				}
				if(match){
					items.push(candidateItem);
				}
			}
			findCallback(items, requestArgs);
		}else{
			// We want a copy to pass back in case the parent wishes to sort the array.
			// We shouldn't allow resort of the internal list, so that multiple callers
			// can get lists and sort without affecting each other.  We also need to
			// filter out any null values that have been left as a result of deleteItem()
			// calls in ItemFileWriteStore.
			for(i = 0; i < arrayOfItems.length; ++i){
				var item = arrayOfItems[i];
				if(item !== null){
					items.push(item);
				}
			}
			findCallback(items, requestArgs);
		}
	},

	_fetchItems: function(	/* Object */ keywordArgs,
							  /* Function */ findCallback,
							  /* Function */ errorCallback){
		// summary:
		//		See dojo/data/util.simpleFetch.fetch()
		var self = this;

		if(this._loadFinished){
			this.filter(keywordArgs, this._getItemsArray(keywordArgs.queryOptions), findCallback);
		}else{
			//Do a check on the JsonFileUrl and crosscheck it.
			//If it doesn't match the cross-check, it needs to be updated
			//This allows for either url or _jsonFileUrl to he changed to
			//reset the store load location.  Done this way for backwards
			//compatibility.  People use _jsonFileUrl (even though officially
			//private.
			if(this._jsonFileUrl !== this._ccUrl){
				kernel.deprecated(this.declaredClass + ": ",
					"To change the url, set the url property of the store," +
						" not _jsonFileUrl.  _jsonFileUrl support will be removed in 2.0");
				this._ccUrl = this._jsonFileUrl;
				this.url = this._jsonFileUrl;
			}else if(this.url !== this._ccUrl){
				this._jsonFileUrl = this.url;
				this._ccUrl = this.url;
			}

			//See if there was any forced reset of data.
			if(this.data != null){
				this._jsonData = this.data;
				this.data = null;
			}

			if(this._jsonFileUrl){
				//If fetches come in before the loading has finished, but while
				//a load is in progress, we have to defer the fetching to be
				//invoked in the callback.
				if(this._loadInProgress){
					this._queuedFetches.push({args: keywordArgs, filter: lang.hitch(self, "filter"), findCallback: lang.hitch(self, findCallback)});
				}else{
					this._loadInProgress = true;
					var getArgs = {
						url: self._jsonFileUrl,
						handleAs: "json-comment-optional",
						preventCache: this.urlPreventCache,
						failOk: this.failOk
					};
					var getHandler = xhr.get(getArgs);
					getHandler.addCallback(function(data){
						try{
							self._getItemsFromLoadedData(data);
							self._loadFinished = true;
							self._loadInProgress = false;

							self.filter(keywordArgs, self._getItemsArray(keywordArgs.queryOptions), findCallback);
							self._handleQueuedFetches();
						}catch(e){
							self._loadFinished = true;
							self._loadInProgress = false;
							errorCallback(e, keywordArgs);
						}
					});
					getHandler.addErrback(function(error){
						self._loadInProgress = false;
						errorCallback(error, keywordArgs);
					});

					//Wire up the cancel to abort of the request
					//This call cancel on the deferred if it hasn't been called
					//yet and then will chain to the simple abort of the
					//simpleFetch keywordArgs
					var oldAbort = null;
					if(keywordArgs.abort){
						oldAbort = keywordArgs.abort;
					}
					keywordArgs.abort = function(){
						var df = getHandler;
						if(df && df.fired === -1){
							df.cancel();
							df = null;
						}
						if(oldAbort){
							oldAbort.call(keywordArgs);
						}
					};
				}
			}else if(this._jsonData){
				try{
					this._loadFinished = true;
					this._getItemsFromLoadedData(this._jsonData);
					this._jsonData = null;
					self.filter(keywordArgs, this._getItemsArray(keywordArgs.queryOptions), findCallback);
				}catch(e){
					errorCallback(e, keywordArgs);
				}
			}else{
				errorCallback(new Error(this.declaredClass + ": No JSON source data was provided as either URL or a nested Javascript object."), keywordArgs);
			}
		}
	},

	_handleQueuedFetches: function(){
		// summary:
		//		Internal function to execute delayed request in the store.
		
		//Execute any deferred fetches now.
		if(this._queuedFetches.length > 0){
			for(var i = 0; i < this._queuedFetches.length; i++){
				var fData = this._queuedFetches[i],
					delayedQuery = fData.args,
					delayedFilter = fData.filter,
					delayedFindCallback = fData.findCallback;
				if(delayedFilter){
					delayedFilter(delayedQuery, this._getItemsArray(delayedQuery.queryOptions), delayedFindCallback);
				}else{
					this.fetchItemByIdentity(delayedQuery);
				}
			}
			this._queuedFetches = [];
		}
	},

	_getItemsArray: function(/*object?*/queryOptions){
		// summary:
		//		Internal function to determine which list of items to search over.
		// queryOptions: The query options parameter, if any.
		if(queryOptions && queryOptions.deep){
			return this._arrayOfAllItems;
		}
		return this._arrayOfTopLevelItems;
	},

	close: function(/*dojo/data/api/Request|Object?*/ request){
		// summary:
		//		See dojo/data/api/Read.close()
		if(this.clearOnClose &&
			this._loadFinished &&
			!this._loadInProgress){
			//Reset all internalsback to default state.  This will force a reload
			//on next fetch.  This also checks that the data or url param was set
			//so that the store knows it can get data.  Without one of those being set,
			//the next fetch will trigger an error.

			if(((this._jsonFileUrl == "" || this._jsonFileUrl == null) &&
				(this.url == "" || this.url == null)
				) && this.data == null){
				console.debug(this.declaredClass + ": WARNING!  Data reload " +
					" information has not been provided." +
					"  Please set 'url' or 'data' to the appropriate value before" +
					" the next fetch");
			}
			this._arrayOfAllItems = [];
			this._arrayOfTopLevelItems = [];
			this._loadFinished = false;
			this._itemsByIdentity = null;
			this._loadInProgress = false;
			this._queuedFetches = [];
		}
	},

	_getItemsFromLoadedData: function(/* Object */ dataObject){
		// summary:
		//		Function to parse the loaded data into item format and build the internal items array.
		// description:
		//		Function to parse the loaded data into item format and build the internal items array.
		// dataObject:
		//		The JS data object containing the raw data to convery into item format.
		// returns: Array
		//		Array of items in store item format.

		// First, we define a couple little utility functions...
		var addingArrays = false,
			self = this;

		function valueIsAnItem(/* anything */ aValue){
			// summary:
			//		Given any sort of value that could be in the raw json data,
			//		return true if we should interpret the value as being an
			//		item itself, rather than a literal value or a reference.
			// example:
			// 	|	false == valueIsAnItem("Kermit");
			// 	|	false == valueIsAnItem(42);
			// 	|	false == valueIsAnItem(new Date());
			// 	|	false == valueIsAnItem({_type:'Date', _value:'1802-05-14'});
			// 	|	false == valueIsAnItem({_reference:'Kermit'});
			// 	|	true == valueIsAnItem({name:'Kermit', color:'green'});
			// 	|	true == valueIsAnItem({iggy:'pop'});
			// 	|	true == valueIsAnItem({foo:42});
			return (aValue !== null) &&
				(typeof aValue === "object") &&
				(!lang.isArray(aValue) || addingArrays) &&
				(!lang.isFunction(aValue)) &&
				(aValue.constructor == Object || lang.isArray(aValue)) &&
				(typeof aValue._reference === "undefined") &&
				(typeof aValue._type === "undefined") &&
				(typeof aValue._value === "undefined") &&
				self.hierarchical;
		}

		function addItemAndSubItemsToArrayOfAllItems(/* dojo/data/api/Item */ anItem){
			self._arrayOfAllItems.push(anItem);
			for(var attribute in anItem){
				var valueForAttribute = anItem[attribute];
				if(valueForAttribute){
					if(lang.isArray(valueForAttribute)){
						var valueArray = valueForAttribute;
						for(var k = 0; k < valueArray.length; ++k){
							var singleValue = valueArray[k];
							if(valueIsAnItem(singleValue)){
								addItemAndSubItemsToArrayOfAllItems(singleValue);
							}
						}
					}else{
						if(valueIsAnItem(valueForAttribute)){
							addItemAndSubItemsToArrayOfAllItems(valueForAttribute);
						}
					}
				}
			}
		}

		this._labelAttr = dataObject.label;

		// We need to do some transformations to convert the data structure
		// that we read from the file into a format that will be convenient
		// to work with in memory.

		// Step 1: Walk through the object hierarchy and build a list of all items
		var i,
			item;
		this._arrayOfAllItems = [];
		this._arrayOfTopLevelItems = dataObject.items;

		for(i = 0; i < this._arrayOfTopLevelItems.length; ++i){
			item = this._arrayOfTopLevelItems[i];
			if(lang.isArray(item)){
				addingArrays = true;
			}
			addItemAndSubItemsToArrayOfAllItems(item);
			item[this._rootItemPropName]=true;
		}

		// Step 2: Walk through all the attribute values of all the items,
		// and replace single values with arrays.  For example, we change this:
		//		{ name:'Miss Piggy', pets:'Foo-Foo'}
		// into this:
		//		{ name:['Miss Piggy'], pets:['Foo-Foo']}
		//
		// We also store the attribute names so we can validate our store
		// reference and item id special properties for the O(1) isItem
		var allAttributeNames = {},
			key;

		for(i = 0; i < this._arrayOfAllItems.length; ++i){
			item = this._arrayOfAllItems[i];
			for(key in item){
				if(key !== this._rootItemPropName){
					var value = item[key];
					if(value !== null){
						if(!lang.isArray(value)){
							item[key] = [value];
						}
					}else{
						item[key] = [null];
					}
				}
				allAttributeNames[key]=key;
			}
		}

		// Step 3: Build unique property names to use for the _storeRefPropName and _itemNumPropName
		// This should go really fast, it will generally never even run the loop.
		while(allAttributeNames[this._storeRefPropName]){
			this._storeRefPropName += "_";
		}
		while(allAttributeNames[this._itemNumPropName]){
			this._itemNumPropName += "_";
		}
		while(allAttributeNames[this._reverseRefMap]){
			this._reverseRefMap += "_";
		}

		// Step 4: Some data files specify an optional 'identifier', which is
		// the name of an attribute that holds the identity of each item.
		// If this data file specified an identifier attribute, then build a
		// hash table of items keyed by the identity of the items.
		var arrayOfValues;

		var identifier = dataObject.identifier;
		if(identifier){
			this._itemsByIdentity = {};
			this._features['dojo.data.api.Identity'] = identifier;
			for(i = 0; i < this._arrayOfAllItems.length; ++i){
				item = this._arrayOfAllItems[i];
				arrayOfValues = item[identifier];
				var identity = arrayOfValues[0];
				if(!Object.hasOwnProperty.call(this._itemsByIdentity, identity)){
					this._itemsByIdentity[identity] = item;
				}else{
					if(this._jsonFileUrl){
						throw new Error(this.declaredClass + ":  The json data as specified by: [" + this._jsonFileUrl + "] is malformed.  Items within the list have identifier: [" + identifier + "].  Value collided: [" + identity + "]");
					}else if(this._jsonData){
						throw new Error(this.declaredClass + ":  The json data provided by the creation arguments is malformed.  Items within the list have identifier: [" + identifier + "].  Value collided: [" + identity + "]");
					}
				}
			}
		}else{
			this._features['dojo.data.api.Identity'] = Number;
		}

		// Step 5: Walk through all the items, and set each item's properties
		// for _storeRefPropName and _itemNumPropName, so that store.isItem() will return true.
		for(i = 0; i < this._arrayOfAllItems.length; ++i){
			item = this._arrayOfAllItems[i];
			item[this._storeRefPropName] = this;
			item[this._itemNumPropName] = i;
		}

		// Step 6: We walk through all the attribute values of all the items,
		// looking for type/value literals and item-references.
		//
		// We replace item-references with pointers to items.  For example, we change:
		//		{ name:['Kermit'], friends:[{_reference:{name:'Miss Piggy'}}] }
		// into this:
		//		{ name:['Kermit'], friends:[miss_piggy] }
		// (where miss_piggy is the object representing the 'Miss Piggy' item).
		//
		// We replace type/value pairs with typed-literals.  For example, we change:
		//		{ name:['Nelson Mandela'], born:[{_type:'Date', _value:'1918-07-18'}] }
		// into this:
		//		{ name:['Kermit'], born:(new Date(1918, 6, 18)) }
		//
		// We also generate the associate map for all items for the O(1) isItem function.
		for(i = 0; i < this._arrayOfAllItems.length; ++i){
			item = this._arrayOfAllItems[i]; // example: { name:['Kermit'], friends:[{_reference:{name:'Miss Piggy'}}] }
			for(key in item){
				arrayOfValues = item[key]; // example: [{_reference:{name:'Miss Piggy'}}]
				for(var j = 0; j < arrayOfValues.length; ++j){
					value = arrayOfValues[j]; // example: {_reference:{name:'Miss Piggy'}}
					if(value !== null && typeof value == "object"){
						if(("_type" in value) && ("_value" in value)){
							var type = value._type; // examples: 'Date', 'Color', or 'ComplexNumber'
							var mappingObj = this._datatypeMap[type]; // examples: Date, dojo.Color, foo.math.ComplexNumber, {type: dojo.Color, deserialize(value){ return new dojo.Color(value)}}
							if(!mappingObj){
								throw new Error("dojo.data.ItemFileReadStore: in the typeMap constructor arg, no object class was specified for the datatype '" + type + "'");
							}else if(lang.isFunction(mappingObj)){
								arrayOfValues[j] = new mappingObj(value._value);
							}else if(lang.isFunction(mappingObj.deserialize)){
								arrayOfValues[j] = mappingObj.deserialize(value._value);
							}else{
								throw new Error("dojo.data.ItemFileReadStore: Value provided in typeMap was neither a constructor, nor a an object with a deserialize function");
							}
						}
						if(value._reference){
							var referenceDescription = value._reference; // example: {name:'Miss Piggy'}
							if(!lang.isObject(referenceDescription)){
								// example: 'Miss Piggy'
								// from an item like: { name:['Kermit'], friends:[{_reference:'Miss Piggy'}]}
								arrayOfValues[j] = this._getItemByIdentity(referenceDescription);
							}else{
								// example: {name:'Miss Piggy'}
								// from an item like: { name:['Kermit'], friends:[{_reference:{name:'Miss Piggy'}}] }
								for(var k = 0; k < this._arrayOfAllItems.length; ++k){
									var candidateItem = this._arrayOfAllItems[k],
										found = true;
									for(var refKey in referenceDescription){
										if(candidateItem[refKey] != referenceDescription[refKey]){
											found = false;
										}
									}
									if(found){
										arrayOfValues[j] = candidateItem;
									}
								}
							}
							if(this.referenceIntegrity){
								var refItem = arrayOfValues[j];
								if(this.isItem(refItem)){
									this._addReferenceToMap(refItem, item, key);
								}
							}
						}else if(this.isItem(value)){
							//It's a child item (not one referenced through _reference).
							//We need to treat this as a referenced item, so it can be cleaned up
							//in a write store easily.
							if(this.referenceIntegrity){
								this._addReferenceToMap(value, item, key);
							}
						}
					}
				}
			}
		}
	},

	_addReferenceToMap: function(/*item*/ refItem, /*item*/ parentItem, /*string*/ attribute){
		// summary:
		//		Method to add an reference map entry for an item and attribute.
		// description:
		//		Method to add an reference map entry for an item and attribute.
		// refItem:
		//		The item that is referenced.
		// parentItem:
		//		The item that holds the new reference to refItem.
		// attribute:
		//		The attribute on parentItem that contains the new reference.

		//Stub function, does nothing.  Real processing is in ItemFileWriteStore.
	},

	getIdentity: function(/* dojo/data/api/Item */ item){
		// summary:
		//		See dojo/data/api/Identity.getIdentity()
		var identifier = this._features['dojo.data.api.Identity'];
		if(identifier === Number){
			return item[this._itemNumPropName]; // Number
		}else{
			var arrayOfValues = item[identifier];
			if(arrayOfValues){
				return arrayOfValues[0]; // Object|String
			}
		}
		return null; // null
	},

	fetchItemByIdentity: function(/* Object */ keywordArgs){
		// summary:
		//		See dojo/data/api/Identity.fetchItemByIdentity()

		// Hasn't loaded yet, we have to trigger the load.
		var item,
			scope;
		if(!this._loadFinished){
			var self = this;
			//Do a check on the JsonFileUrl and crosscheck it.
			//If it doesn't match the cross-check, it needs to be updated
			//This allows for either url or _jsonFileUrl to he changed to
			//reset the store load location.  Done this way for backwards
			//compatibility.  People use _jsonFileUrl (even though officially
			//private.
			if(this._jsonFileUrl !== this._ccUrl){
				kernel.deprecated(this.declaredClass + ": ",
					"To change the url, set the url property of the store," +
						" not _jsonFileUrl.  _jsonFileUrl support will be removed in 2.0");
				this._ccUrl = this._jsonFileUrl;
				this.url = this._jsonFileUrl;
			}else if(this.url !== this._ccUrl){
				this._jsonFileUrl = this.url;
				this._ccUrl = this.url;
			}

			//See if there was any forced reset of data.
			if(this.data != null && this._jsonData == null){
				this._jsonData = this.data;
				this.data = null;
			}

			if(this._jsonFileUrl){

				if(this._loadInProgress){
					this._queuedFetches.push({args: keywordArgs});
				}else{
					this._loadInProgress = true;
					var getArgs = {
						url: self._jsonFileUrl,
						handleAs: "json-comment-optional",
						preventCache: this.urlPreventCache,
						failOk: this.failOk
					};
					var getHandler = xhr.get(getArgs);
					getHandler.addCallback(function(data){
						var scope = keywordArgs.scope?keywordArgs.scope:kernel.global;
						try{
							self._getItemsFromLoadedData(data);
							self._loadFinished = true;
							self._loadInProgress = false;
							item = self._getItemByIdentity(keywordArgs.identity);
							if(keywordArgs.onItem){
								keywordArgs.onItem.call(scope, item);
							}
							self._handleQueuedFetches();
						}catch(error){
							self._loadInProgress = false;
							if(keywordArgs.onError){
								keywordArgs.onError.call(scope, error);
							}
						}
					});
					getHandler.addErrback(function(error){
						self._loadInProgress = false;
						if(keywordArgs.onError){
							var scope = keywordArgs.scope?keywordArgs.scope:kernel.global;
							keywordArgs.onError.call(scope, error);
						}
					});
				}

			}else if(this._jsonData){
				// Passed in data, no need to xhr.
				self._getItemsFromLoadedData(self._jsonData);
				self._jsonData = null;
				self._loadFinished = true;
				item = self._getItemByIdentity(keywordArgs.identity);
				if(keywordArgs.onItem){
					scope = keywordArgs.scope?keywordArgs.scope:kernel.global;
					keywordArgs.onItem.call(scope, item);
				}
			}
		}else{
			// Already loaded.  We can just look it up and call back.
			item = this._getItemByIdentity(keywordArgs.identity);
			if(keywordArgs.onItem){
				scope = keywordArgs.scope?keywordArgs.scope:kernel.global;
				keywordArgs.onItem.call(scope, item);
			}
		}
	},

	_getItemByIdentity: function(/* Object */ identity){
		// summary:
		//		Internal function to look an item up by its identity map.
		var item = null;
		if(this._itemsByIdentity){
			// If this map is defined, we need to just try to get it.  If it fails
			// the item does not exist.
			if(Object.hasOwnProperty.call(this._itemsByIdentity, identity)){
				item = this._itemsByIdentity[identity];
			}
		}else if (Object.hasOwnProperty.call(this._arrayOfAllItems, identity)){
			item = this._arrayOfAllItems[identity];
		}
		if(item === undefined){
			item = null;
		}
		return item; // Object
	},

	getIdentityAttributes: function(/* dojo/data/api/Item */ item){
		// summary:
		//		See dojo/data/api/Identity.getIdentityAttributes()

		var identifier = this._features['dojo.data.api.Identity'];
		if(identifier === Number){
			// If (identifier === Number) it means getIdentity() just returns
			// an integer item-number for each item.  The dojo/data/api/Identity
			// spec says we need to return null if the identity is not composed
			// of attributes
			return null; // null
		}else{
			return [identifier]; // Array
		}
	},

	_forceLoad: function(){
		// summary:
		//		Internal function to force a load of the store if it hasn't occurred yet.  This is required
		//		for specific functions to work properly.
		var self = this;
		//Do a check on the JsonFileUrl and crosscheck it.
		//If it doesn't match the cross-check, it needs to be updated
		//This allows for either url or _jsonFileUrl to he changed to
		//reset the store load location.  Done this way for backwards
		//compatibility.  People use _jsonFileUrl (even though officially
		//private.
		if(this._jsonFileUrl !== this._ccUrl){
			kernel.deprecated(this.declaredClass + ": ",
				"To change the url, set the url property of the store," +
					" not _jsonFileUrl.  _jsonFileUrl support will be removed in 2.0");
			this._ccUrl = this._jsonFileUrl;
			this.url = this._jsonFileUrl;
		}else if(this.url !== this._ccUrl){
			this._jsonFileUrl = this.url;
			this._ccUrl = this.url;
		}

		//See if there was any forced reset of data.
		if(this.data != null){
			this._jsonData = this.data;
			this.data = null;
		}

		if(this._jsonFileUrl){
			var getArgs = {
				url: this._jsonFileUrl,
				handleAs: "json-comment-optional",
				preventCache: this.urlPreventCache,
				failOk: this.failOk,
				sync: true
			};
			var getHandler = xhr.get(getArgs);
			getHandler.addCallback(function(data){
				try{
					//Check to be sure there wasn't another load going on concurrently
					//So we don't clobber data that comes in on it.  If there is a load going on
					//then do not save this data.  It will potentially clobber current data.
					//We mainly wanted to sync/wait here.
					//TODO:  Revisit the loading scheme of this store to improve multi-initial
					//request handling.
					if(self._loadInProgress !== true && !self._loadFinished){
						self._getItemsFromLoadedData(data);
						self._loadFinished = true;
					}else if(self._loadInProgress){
						//Okay, we hit an error state we can't recover from.  A forced load occurred
						//while an async load was occurring.  Since we cannot block at this point, the best
						//that can be managed is to throw an error.
						throw new Error(this.declaredClass + ":  Unable to perform a synchronous load, an async load is in progress.");
					}
				}catch(e){
					console.log(e);
					throw e;
				}
			});
			getHandler.addErrback(function(error){
				throw error;
			});
		}else if(this._jsonData){
			self._getItemsFromLoadedData(self._jsonData);
			self._jsonData = null;
			self._loadFinished = true;
		}
	}
});
//Mix in the simple fetch implementation to this class.
lang.extend(ItemFileReadStore,simpleFetch);

return ItemFileReadStore;

});

},
'dojo/data/util/filter':function(){
define("dojo/data/util/filter", ["../../_base/lang"], function(lang){
	// module:
	//		dojo/data/util/filter
	// summary:
	//		TODOC

var filter = {};
lang.setObject("dojo.data.util.filter", filter);

filter.patternToRegExp = function(/*String*/pattern, /*boolean?*/ ignoreCase){
	// summary:
	//		Helper function to convert a simple pattern to a regular expression for matching.
	// description:
	//		Returns a regular expression object that conforms to the defined conversion rules.
	//		For example:
	//
	//		- ca*   -> /^ca.*$/
	//		- *ca*  -> /^.*ca.*$/
	//		- *c\*a*  -> /^.*c\*a.*$/
	//		- *c\*a?*  -> /^.*c\*a..*$/
	//
	//		and so on.
	// pattern: string
	//		A simple matching pattern to convert that follows basic rules:
	//
	//		- * Means match anything, so ca* means match anything starting with ca
	//		- ? Means match single character.  So, b?b will match to bob and bab, and so on.
	//		- \ is an escape character.  So for example, \* means do not treat * as a match, but literal character *.
	//
	//		To use a \ as a character in the string, it must be escaped.  So in the pattern it should be
	//		represented by \\ to be treated as an ordinary \ character instead of an escape.
	// ignoreCase:
	//		An optional flag to indicate if the pattern matching should be treated as case-sensitive or not when comparing
	//		By default, it is assumed case sensitive.

	var rxp = "^";
	var c = null;
	for(var i = 0; i < pattern.length; i++){
		c = pattern.charAt(i);
		switch(c){
			case '\\':
				rxp += c;
				i++;
				rxp += pattern.charAt(i);
				break;
			case '*':
				rxp += ".*"; break;
			case '?':
				rxp += "."; break;
			case '$':
			case '^':
			case '/':
			case '+':
			case '.':
			case '|':
			case '(':
			case ')':
			case '{':
			case '}':
			case '[':
			case ']':
				rxp += "\\"; //fallthrough
			default:
				rxp += c;
		}
	}
	rxp += "$";
	if(ignoreCase){
		return new RegExp(rxp,"mi"); //RegExp
	}else{
		return new RegExp(rxp,"m"); //RegExp
	}

};

return filter;
});

},
'commsy/popups/ClickMaterialPopup':function(){
define("commsy/popups/ClickMaterialPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/topic"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On, Topic, Tooltip) {
	return declare(ClickPopupHandler, {
		constructor: function() {

		},

		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "material";
			this.editType = customObject.editType;
			this.version_id = customObject.vid;
			this.contextId = customObject.contextId;

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
			/* setup bibliographic form elements */
			// get value from active bibliographic option
			var selectNode = query("select#bibliographic_select", this.contentNode)[0];

			if (selectNode) {
				// show / hude bibliographic div's
				this.showHideBibliographic(selectNode);

				// register handler for select
				On(selectNode, "change", lang.hitch(this, function(event) {
					this.showHideBibliographic(selectNode);
				}));
			}
		},

		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			var search = {
				tabs: [
				    { id: "rights_tab" },
				    { id: "buzzwords_tab", group: "buzzwords" },
				    { id: "tags_tab", group: "tags" },
				    { id: "workflow_tab" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) },
				    { query: query("input.tabStatus", this.contentNode) },
				    { query: query("select#bibliographic_select", this.contentNode) }
				]
			};

			// add visible bibliographic div
			// TODO: maybe there is a not-class selector?
			dojo.forEach(query("div#bibliographic div[id^='bib_content_']", this.contentNode), function(node, index, arr) {
				if(!dom_class.contains(node, "hidden")) {

					var nodeId = domAttr.get(node, "id");
					search.nodeLists.push({ query: query("div#" + nodeId, this.contentNode) });

					return false;
				}
			});

			this.submit(search, {part:customObject.part, version_id:this.version_id, contextId: this.contextId });
		},

		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					if (this.contextId) {
						this.close();

						Topic.publish("newOwnRoomItem", { itemId: item_id });
					} else {
						this.reload(item_id);
					}
				}));
			} else {
				if (this.contextId) {
					this.close();
					var aNode = query("a#listItem" + item_id)[0];
					if (aNode) {
						aNode.click();
					}
				} else {
					if(typeof(this.version_id) != 'undefined'){
						this.reload(item_id+"&version_id="+this.version_id);
					} else {
						this.reload(item_id);
					}
				}
			}
		},

		showHideBibliographic: function(selectNode) {
			var key = domAttr.get(selectNode, "value");

			// go through all bibliographic content div's and show the the one who's id matches "bib_content_" + key
			dojo.forEach(query("div#bibliographic div[id^='bib_content_']", this.contentNode), function(node) {
				if(domAttr.get(node, "id") === "bib_content_" + key) {
					// show
					dom_class.remove(node, "hidden");
				} else {
					// hide
					dom_class.add(node, "hidden");
				}
			});
		}
	});
});
},
'dojo/data/util/sorter':function(){
define("dojo/data/util/sorter", ["../../_base/lang"], function(lang){
	// module:
	//		dojo/data/util/sorter
	// summary:
	//		TODOC

var sorter = {};
lang.setObject("dojo.data.util.sorter", sorter);

sorter.basicComparator = function(	/*anything*/ a,
													/*anything*/ b){
	// summary:
	//		Basic comparison function that compares if an item is greater or less than another item
	// description:
	//		returns 1 if a > b, -1 if a < b, 0 if equal.
	//		'null' values (null, undefined) are treated as larger values so that they're pushed to the end of the list.
	//		And compared to each other, null is equivalent to undefined.

	//null is a problematic compare, so if null, we set to undefined.
	//Makes the check logic simple, compact, and consistent
	//And (null == undefined) === true, so the check later against null
	//works for undefined and is less bytes.
	var r = -1;
	if(a === null){
		a = undefined;
	}
	if(b === null){
		b = undefined;
	}
	if(a == b){
		r = 0;
	}else if(a > b || a == null){
		r = 1;
	}
	return r; //int {-1,0,1}
};

sorter.createSortFunction = function(	/* attributes[] */sortSpec, /*dojo/data/api/Read*/ store){
	// summary:
	//		Helper function to generate the sorting function based off the list of sort attributes.
	// description:
	//		The sort function creation will look for a property on the store called 'comparatorMap'.  If it exists
	//		it will look in the mapping for comparisons function for the attributes.  If one is found, it will
	//		use it instead of the basic comparator, which is typically used for strings, ints, booleans, and dates.
	//		Returns the sorting function for this particular list of attributes and sorting directions.
	// sortSpec:
	//		A JS object that array that defines out what attribute names to sort on and whether it should be descenting or asending.
	//		The objects should be formatted as follows:
	// |	{
	// |		attribute: "attributeName-string" || attribute,
	// |		descending: true|false;   // Default is false.
	// |	}
	// store:
	//		The datastore object to look up item values from.

	var sortFunctions=[];

	function createSortFunction(attr, dir, comp, s){
		//Passing in comp and s (comparator and store), makes this
		//function much faster.
		return function(itemA, itemB){
			var a = s.getValue(itemA, attr);
			var b = s.getValue(itemB, attr);
			return dir * comp(a,b); //int
		};
	}
	var sortAttribute;
	var map = store.comparatorMap;
	var bc = sorter.basicComparator;
	for(var i = 0; i < sortSpec.length; i++){
		sortAttribute = sortSpec[i];
		var attr = sortAttribute.attribute;
		if(attr){
			var dir = (sortAttribute.descending) ? -1 : 1;
			var comp = bc;
			if(map){
				if(typeof attr !== "string" && ("toString" in attr)){
					 attr = attr.toString();
				}
				comp = map[attr] || bc;
			}
			sortFunctions.push(createSortFunction(attr,
				dir, comp, store));
		}
	}
	return function(rowA, rowB){
		var i=0;
		while(i < sortFunctions.length){
			var ret = sortFunctions[i++](rowA, rowB);
			if(ret !== 0){
				return ret;//int
			}
		}
		return 0; //int
	}; // Function
};

return sorter;
});

},
'dijit/form/_ButtonMixin':function(){
define("dijit/form/_ButtonMixin", [
	"dojo/_base/declare", // declare
	"dojo/dom", // dom.setSelectable
	"dojo/_base/event", // event.stop
	"../registry"		// registry.byNode
], function(declare, dom, event, registry){

// module:
//		dijit/form/_ButtonMixin

return declare("dijit.form._ButtonMixin", null, {
	// summary:
	//		A mixin to add a thin standard API wrapper to a normal HTML button
	// description:
	//		A label should always be specified (through innerHTML) or the label attribute.
	//
	//		Attach points:
	//
	//		- focusNode (required): this node receives focus
	//		- valueNode (optional): this node's value gets submitted with FORM elements
	//		- containerNode (optional): this node gets the innerHTML assignment for label
	// example:
	// |	<button data-dojo-type="dijit/form/Button" onClick="...">Hello world</button>
	// example:
	// |	var button1 = new Button({label: "hello world", onClick: foo});
	// |	dojo.body().appendChild(button1.domNode);

	// label: HTML String
	//		Content to display in button.
	label: "",

	// type: [const] String
	//		Type of button (submit, reset, button, checkbox, radio)
	type: "button",

	_onClick: function(/*Event*/ e){
		// summary:
		//		Internal function to handle click actions
		if(this.disabled){
			event.stop(e);
			return false;
		}
		var preventDefault = this.onClick(e) === false; // user click actions
		if(!preventDefault && this.type == "submit" && !(this.valueNode||this.focusNode).form){ // see if a non-form widget needs to be signalled
			for(var node=this.domNode; node.parentNode; node=node.parentNode){
				var widget=registry.byNode(node);
				if(widget && typeof widget._onSubmit == "function"){
					widget._onSubmit(e);
					preventDefault = true;
					break;
				}
			}
		}
		if(preventDefault){
			e.preventDefault();
		}
		return !preventDefault;
	},

	postCreate: function(){
		this.inherited(arguments);
		dom.setSelectable(this.focusNode, false);
	},

	onClick: function(/*Event*/ /*===== e =====*/){
		// summary:
		//		Callback for when button is clicked.
		//		If type="submit", return true to perform submit, or false to cancel it.
		// type:
		//		callback
		return true;		// Boolean
	},

	_setLabelAttr: function(/*String*/ content){
		// summary:
		//		Hook for set('label', ...) to work.
		// description:
		//		Set the label (text) of the button; takes an HTML string.
		this._set("label", content);
		(this.containerNode||this.focusNode).innerHTML = content;
	}
});

});

},
'dijit/tree/_dndContainer':function(){
define("dijit/tree/_dndContainer", [
	"dojo/aspect",	// aspect.after
	"dojo/_base/declare", // declare
	"dojo/dom-class", // domClass.add domClass.remove domClass.replace
	"dojo/_base/event",	// event.stop
	"dojo/_base/lang", // lang.mixin lang.hitch
	"dojo/on",
	"dojo/touch"
], function(aspect, declare,domClass, event, lang, on, touch){

	// module:
	//		dijit/tree/_dndContainer

	/*=====
	 var __Args = {
		 // summary:
		 //		A dict of parameters for Tree source configuration.
		 // isSource: Boolean?
		 //		Can be used as a DnD source. Defaults to true.
		 // accept: String[]
		 //		List of accepted types (text strings) for a target; defaults to
		 //		["text", "treeNode"]
		 // copyOnly: Boolean?
		 //		Copy items, if true, use a state of Ctrl key otherwise,
		 // dragThreshold: Number
		 //		The move delay in pixels before detecting a drag; 0 by default
		 // betweenThreshold: Integer
		 //		Distance from upper/lower edge of node to allow drop to reorder nodes
	 };
	 =====*/

	return declare("dijit.tree._dndContainer", null, {

		// summary:
		//		This is a base class for `dijit/tree/_dndSelector`, and isn't meant to be used directly.
		//		It's modeled after `dojo/dnd/Container`.
		// tags:
		//		protected

		/*=====
		// current: DomNode
		//		The currently hovered TreeNode.rowNode (which is the DOM node
		//		associated w/a given node in the tree, excluding it's descendants)
		current: null,
		=====*/

		constructor: function(tree, params){
			// summary:
			//		A constructor of the Container
			// tree: Node
			//		Node or node's id to build the container on
			// params: __Args
			//		A dict of parameters, which gets mixed into the object
			// tags:
			//		private
			this.tree = tree;
			this.node = tree.domNode;	// TODO: rename; it's not a TreeNode but the whole Tree
			lang.mixin(this, params);

			// class-specific variables
			this.current = null;	// current TreeNode's DOM node

			// states
			this.containerState = "";
			domClass.add(this.node, "dojoDndContainer");

			// set up events
			this.events = [
				// Mouse (or touch) enter/leave on Tree itself
				on(this.node, touch.enter, lang.hitch(this, "onOverEvent")),
				on(this.node, touch.leave,	lang.hitch(this, "onOutEvent")),

				// switching between TreeNodes
				aspect.after(this.tree, "_onNodeMouseEnter", lang.hitch(this, "onMouseOver"), true),
				aspect.after(this.tree, "_onNodeMouseLeave", lang.hitch(this, "onMouseOut"), true),

				// cancel text selection and text dragging
				on(this.node, "dragstart", lang.hitch(event, "stop")),
				on(this.node, "selectstart", lang.hitch(event, "stop"))
			];
		},

		destroy: function(){
			// summary:
			//		Prepares this object to be garbage-collected

			var h;
			while(h = this.events.pop()){ h.remove(); }

			// this.clearItems();
			this.node = this.parent = null;
		},

		// mouse events
		onMouseOver: function(widget /*===== , evt =====*/){
			// summary:
			//		Called when mouse is moved over a TreeNode
			// widget: TreeNode
			// evt: Event
			// tags:
			//		protected
			this.current = widget;
		},

		onMouseOut: function(/*===== widget, evt =====*/){
			// summary:
			//		Called when mouse is moved away from a TreeNode
			// widget: TreeNode
			// evt: Event
			// tags:
			//		protected
			this.current = null;
		},

		_changeState: function(type, newState){
			// summary:
			//		Changes a named state to new state value
			// type: String
			//		A name of the state to change
			// newState: String
			//		new state
			var prefix = "dojoDnd" + type;
			var state = type.toLowerCase() + "State";
			//domClass.replace(this.node, prefix + newState, prefix + this[state]);
			domClass.replace(this.node, prefix + newState, prefix + this[state]);
			this[state] = newState;
		},

		_addItemClass: function(node, type){
			// summary:
			//		Adds a class with prefix "dojoDndItem"
			// node: Node
			//		A node
			// type: String
			//		A variable suffix for a class name
			domClass.add(node, "dojoDndItem" + type);
		},

		_removeItemClass: function(node, type){
			// summary:
			//		Removes a class with prefix "dojoDndItem"
			// node: Node
			//		A node
			// type: String
			//		A variable suffix for a class name
			domClass.remove(node, "dojoDndItem" + type);
		},

		onOverEvent: function(){
			// summary:
			//		This function is called once, when mouse is over our container
			// tags:
			//		protected
			this._changeState("Container", "Over");
		},

		onOutEvent: function(){
			// summary:
			//		This function is called once, when mouse is out of our container
			// tags:
			//		protected
			this._changeState("Container", "");
		}
	});
});

},
'dijit/form/_FormWidget':function(){
define("dijit/form/_FormWidget", [
	"dojo/_base/declare",	// declare
	"dojo/has",				// has("dijit-legacy-requires")
	"dojo/_base/kernel",	// kernel.deprecated
	"dojo/ready",
	"../_Widget",
	"../_CssStateMixin",
	"../_TemplatedMixin",
	"./_FormWidgetMixin"
], function(declare, has, kernel, ready, _Widget, _CssStateMixin, _TemplatedMixin, _FormWidgetMixin){


// module:
//		dijit/form/_FormWidget

// Back compat w/1.6, remove for 2.0
if(has("dijit-legacy-requires")){
	ready(0, function(){
		var requires = ["dijit/form/_FormValueWidget"];
		require(requires);	// use indirection so modules not rolled into a build
	});
}

return declare("dijit.form._FormWidget", [_Widget, _TemplatedMixin, _CssStateMixin, _FormWidgetMixin], {
	// summary:
	//		Base class for widgets corresponding to native HTML elements such as `<checkbox>` or `<button>`,
	//		which can be children of a `<form>` node or a `dijit/form/Form` widget.
	//
	// description:
	//		Represents a single HTML element.
	//		All these widgets should have these attributes just like native HTML input elements.
	//		You can set them during widget construction or afterwards, via `dijit/_WidgetBase.set()`.
	//
	//		They also share some common methods.

	setDisabled: function(/*Boolean*/ disabled){
		// summary:
		//		Deprecated.  Use set('disabled', ...) instead.
		kernel.deprecated("setDisabled("+disabled+") is deprecated. Use set('disabled',"+disabled+") instead.", "", "2.0");
		this.set('disabled', disabled);
	},

	setValue: function(/*String*/ value){
		// summary:
		//		Deprecated.  Use set('value', ...) instead.
		kernel.deprecated("dijit.form._FormWidget:setValue("+value+") is deprecated.  Use set('value',"+value+") instead.", "", "2.0");
		this.set('value', value);
	},

	getValue: function(){
		// summary:
		//		Deprecated.  Use get('value') instead.
		kernel.deprecated(this.declaredClass+"::getValue() is deprecated. Use get('value') instead.", "", "2.0");
		return this.get('value');
	},

	postMixInProperties: function(){
		// Setup name=foo string to be referenced from the template (but only if a name has been specified)
		// Unfortunately we can't use _setNameAttr to set the name due to IE limitations, see #8484, #8660.
		// Regarding escaping, see heading "Attribute values" in
		// http://www.w3.org/TR/REC-html40/appendix/notes.html#h-B.3.2
		this.nameAttrSetting = this.name ? ('name="' + this.name.replace(/"/g, "&quot;") + '"') : '';
		this.inherited(arguments);
	},

	// Override automatic assigning type --> focusNode, it causes exception on IE.
	// Instead, type must be specified as ${type} in the template, as part of the original DOM
	_setTypeAttr: null
});

});

},
'dojo/DeferredList':function(){
define("dojo/DeferredList", ["./_base/kernel", "./_base/Deferred", "./_base/array"], function(dojo, Deferred, darray){
	// module:
	//		dojo/DeferredList


dojo.DeferredList = function(/*Array*/ list, /*Boolean?*/ fireOnOneCallback, /*Boolean?*/ fireOnOneErrback, /*Boolean?*/ consumeErrors, /*Function?*/ canceller){
	// summary:
	//		Deprecated, use dojo/promise/all instead.
	//		Provides event handling for a group of Deferred objects.
	// description:
	//		DeferredList takes an array of existing deferreds and returns a new deferred of its own
	//		this new deferred will typically have its callback fired when all of the deferreds in
	//		the given list have fired their own deferreds.  The parameters `fireOnOneCallback` and
	//		fireOnOneErrback, will fire before all the deferreds as appropriate
	// list:
	//		The list of deferreds to be synchronizied with this DeferredList
	// fireOnOneCallback:
	//		Will cause the DeferredLists callback to be fired as soon as any
	//		of the deferreds in its list have been fired instead of waiting until
	//		the entire list has finished
	// fireonOneErrback:
	//		Will cause the errback to fire upon any of the deferreds errback
	// canceller:
	//		A deferred canceller function, see dojo.Deferred
	var resultList = [];
	Deferred.call(this);
	var self = this;
	if(list.length === 0 && !fireOnOneCallback){
		this.resolve([0, []]);
	}
	var finished = 0;
	darray.forEach(list, function(item, i){
		item.then(function(result){
			if(fireOnOneCallback){
				self.resolve([i, result]);
			}else{
				addResult(true, result);
			}
		},function(error){
			if(fireOnOneErrback){
				self.reject(error);
			}else{
				addResult(false, error);
			}
			if(consumeErrors){
				return null;
			}
			throw error;
		});
		function addResult(succeeded, result){
			resultList[i] = [succeeded, result];
			finished++;
			if(finished === list.length){
				self.resolve(resultList);
			}

		}
	});
};
dojo.DeferredList.prototype = new Deferred();

dojo.DeferredList.prototype.gatherResults = function(deferredList){
	// summary:
	//		Gathers the results of the deferreds for packaging
	//		as the parameters to the Deferred Lists' callback
	// deferredList: dojo/DeferredList
	//		The deferred list from which this function gathers results.
	// returns: dojo/DeferredList
	//		The newly created deferred list which packs results as
	//		parameters to its callback.

	var d = new dojo.DeferredList(deferredList, false, true, false);
	d.addCallback(function(results){
		var ret = [];
		darray.forEach(results, function(result){
			ret.push(result[1]);
		});
		return ret;
	});
	return d;
};

return dojo.DeferredList;
});

},
'commsy/WidgetPopupHandler':function(){
define("commsy/WidgetPopupHandler", [	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/_base/Deferred",
        	"dojo/DeferredList"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang, Deferred, DeferredList) {
	return declare(TogglePopupHandler, {
		widgetArray:	[],
		widgetHandles:	[],
		
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			
			this.widgetArray = [];
			this.widgetHandles = [];
			
			this.defWidgets = null;
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			// get configuration for this popup
			var action = "get" + this.ucFirst(this.module) + "Configuration";
			this.AJAXRequest("widgets", action, {},
				Lang.hitch(this, function(response) {
					// we recieved a list of widgets to display
					this.widgetArray = this.widgetArray.concat(response.displayConfig);
					
					// load widgets
					this.loadWidgets();
				})
			);
		},
		
		loadWidgets: function() {
			dojo.forEach(this.widgetArray, Lang.hitch(this, function(widget, index, arr) {
				// determ name of widget
				var split = widget.split("_");
				var widgetPath = "widgets/" + this.ucFirst(this.module) + this.ucFirst(split[1]);
				
				if(split[3] && split[3] == "preferences") widgetPath += "Preferences";
				
				this.loadWidget(widgetPath);
				
				
				/*
				// check if widget exists
				if (Lang.exists("widgets." + widgetName)) {
					
					console.log(widgetName + " found");
				}
				*/
			}));
		},
		
		loadWidgetsManual: function(widgetArray) {
			var defList = [];
			
			dojo.forEach(widgetArray, Lang.hitch(this, function(widget, index, arr) {
				defList.push(this.loadWidget(widget));
			}));
			
			return new DeferredList(defList);
		},
		
		loadWidget: function(widgetPath, mixin) {
			mixin = mixin || {};
			
			var deferred = new Deferred();
			
			require(["commsy/" + widgetPath], Lang.hitch(this, function(widgetObject) {
				// get template
				this.AJAXRequest("widgets", "getHTMLForWidget", { widgetPath: widgetPath },
					Lang.hitch(this, function(templateString) {
						var params = {
							templateString:		templateString,
							widgetHandler:		this
						};
						declare.safeMixin(params, mixin);
						
						// init widget
						var widgetHandler = new widgetObject(params);
						widgetHandler.startup();
						
						this.widgetHandles.push({ path: widgetPath, handler: widgetHandler });
						
						deferred.resolve({ handle: widgetHandler });
					})
				)
			}));
			
			return deferred;
		},
		
		getWidget: function(widgetPath) {
			var filtered = dojo.filter(this.widgetHandles, function(item, index, arr) {
				return item.path == widgetPath;
			});
			
			return filtered[0].handler || null;
		},
		
		onPopupSubmit: function(customObject) {
			// this popup will not be submitted
		},
		
		onPopupSubmitSuccess: function(item_id) {
		}
	});
});
},
'commsy/TogglePopupHandler':function(){
define("commsy/TogglePopupHandler", [	"dojo/_base/declare",
        	"commsy/PopupHandler",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-construct",
        	"dojo/dom-attr"], function(declare, PopupHandler, on, topic, lang, query, dom_class, DomConstruct, dom_attr) {
	return declare(PopupHandler, {
		is_loaded:				false,
		is_open:				false,
		popup_button_node:		null,
		
		/* "static" */ statics: { togglePopups: [] },
		
		constructor: function(args) {
			this.fct = "popup";
		},
		
		registerPopupClick: function() {
			on(this.popup_button_node, "click", lang.hitch(this, function(event) {
				this.open();
				event.preventDefault();
			}));
		},
		
		open: function() {
			if(this.is_loaded === false) {
				this.setupLoading();
				
				this.statics.togglePopups.push(this);
				
				// setup ajax request for getting html
				this.AJAXRequest("popup", "getHTML", { module: this.module} , lang.hitch(this, function(html) {
					// append html to node
					DomConstruct.place(html, this.contentNode, "last");
					
					this.setupTabs();
					this.setupFeatures();
					this.setupSpecific();
					this.onCreate();
					
					// register close
					on(query("a", this.contentNode)[0], "click", lang.hitch(this, function(event) {
						this.close();
						
						event.preventDefault();
					}));
					
					// register submit click
					on(query("input.submit", this.contentNode), "click", lang.hitch(this, function(event) {
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
			}
			
			this.onTogglePopup();
		},
		
		close: function() {
			this.inherited(arguments);
			
			this.onTogglePopup();
		}
	});
});
},
'commsy/popups/ClickBuzzwordsPopup':function(){
define("commsy/popups/ClickBuzzwordsPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/NodeList-traverse"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, DomAttr, DomStyle, On, Topic) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			//this.item_id = customObject.iid;
			this.module = "buzzwords";
			this.list = null;
			
			this.features = [ ];
			
			this.contextId = customObject.contextId;
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			// this will handle both select boxes in merge tab
			var selectOneNode = Query("select#buzzword_merge_one")[0];
			var selectTwoNode = Query("select#buzzword_merge_two")[0];
			
			On(selectOneNode, "change", Lang.hitch(this, function(event) {
				// when changing box one, disable the selected value in box two
				this.enableAllOptionsExceptOne(selectTwoNode, DomAttr.get(event.target, "value"));
			}));
			On(selectTwoNode, "change", Lang.hitch(this, function(event) {
				// when changing box two, disable the selected value in box one
				this.enableAllOptionsExceptOne(selectOneNode, DomAttr.get(event.target, "value"));
			}));
			
			// setup list
			require(["commsy/List"], Lang.hitch(this, function(List) {
				this.list = new List();
				this.list.init(this.cid, this.from_php.template.tpl_path, {
					activatorNode:	Query("a.list_activator")[0],
					module:			"buzzwords",
					roomId:			this.contextId,
					OnInitDone:		Lang.hitch(this, function() {
						this.list.performRequest();
					})
				});
				
				// set initial buzzword to first in edit tab
				var firstEditBuzzwordNode = Query("div#edit_tab input.buzzword_change_name:first-child")[0];
				if(firstEditBuzzwordNode) {
					var buzzwordId = DomAttr.get(firstEditBuzzwordNode, "id");
					this.list.requestData.item_id = buzzwordId;
					this.list.requestData.contextId = this.contextId;
				}
			}));
			
			// connect all assignment buttons in edit tab
			dojo.forEach(Query("input.buzzword_attach"), Lang.hitch(this, function(inputNode, index, arr) {
				On(inputNode, "click", Lang.hitch(this, function(event) {
					// get name and extract buzzword id
					var nameAttr = DomAttr.get(inputNode, "name");
					var buzzwordId = nameAttr.substr(10, nameAttr.length-11);
					
					// update reference id of list and perform a new request
					this.list.requestData.item_id = buzzwordId;
					this.list.requestData.contextId = this.contextId;
					this.list.performRequest();
					
					// update header
					var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
					DomAttr.set(Query("div.open_close_head span.text_important")[0], "innerHTML", "&bdquo;" + buzzwordName + "&rdquo;");
				}));
			}));
			
			// connect all change buttons in edit tab
			dojo.forEach(Query("input.buzzword_change"), Lang.hitch(this, function(inputNode, index, arr) {
				On(inputNode, "click", Lang.hitch(this, function(event) {
					// get name and extract buzzword id
					var nameAttr = DomAttr.get(inputNode, "name");
					var buzzwordId = nameAttr.substr(10, nameAttr.length-11);
					
					// get new buzzword name
					var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
					
					// perform ajax request
					this.AJAXRequest("buzzwords", "updateBuzzword", { buzzword_id: buzzwordId, buzzword: buzzwordName },
						Lang.hitch(this, function(response) {
							// update header if the buzzword was set in list
							if(this.list.requestData.item_id === buzzwordId) {
								DomAttr.set(Query("div.open_close_head span.text_important")[0], "innerHTML", "&bdquo;" + buzzwordName + "&rdquo;");
								
								if (this.contextId) {
									Topic.publish("newOwnRoomBuzzword", {});
								}
							}
						}),
						Lang.hitch(this, function(response) {
							
						})
					);
				}));
			}));
			
			// connect all delete buttons in edit tab
			dojo.forEach(Query("input.buzzword_delete"), Lang.hitch(this, function(inputNode, index, arr) {
				On(inputNode, "click", Lang.hitch(this, function(event) {
					// get name and extract buzzword id
					var nameAttr = DomAttr.get(inputNode, "name");
					var buzzwordId = nameAttr.substr(10, nameAttr.length-11);
					
					// get buzzword name
					var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
					
					// perform ajax request
					this.AJAXRequest("buzzwords", "deleteBuzzword", { buzzword_id: buzzwordId },
						Lang.hitch(this, function(response) {
							// remove buzzword from all lists, merge selects and edit tab
							this.removeBuzzwordFromLists(buzzwordName);
							this.removeBuzzwordFromMergeSelects(buzzwordName);
							this.removeBuzzwordFromEditTab(buzzwordName);
							
							if (this.contextId) {
								Topic.publish("newOwnRoomBuzzword", {});
							}
						}),
						Lang.hitch(this, function(response) {
							
						})
					);
				}));
			}));
		},
		
		enableAllOptionsExceptOne: function(selectNode, exception) {
			var optionNodes = Query("option", selectNode);
			
			// handle disabled state
			dojo.forEach(optionNodes, Lang.hitch(this, function(optionNode, index, arr) {
				if(DomAttr.get(optionNode, "value") === exception) {
					DomAttr.set(optionNode, "disabled", "disabled");
				} else {
					if(DomAttr.has(optionNode, "disabled")) {
						DomAttr.remove(optionNode, "disabled");
					}
				}
			}));
			
			// try to find a value, that is not the excepted one - this happens when it was selected before
			if(DomAttr.get(selectNode, "value") === exception) {
				var skip = false;
				dojo.some(optionNodes, Lang.hitch(this, function(optionNode, index, arr) {
					if(skip) return false;
					
					var optionValue = DomAttr.get(optionNode, "value");
					if(optionValue !== exception) {
						DomAttr.set(selectNode, "value", optionValue);
						skip = true;
					}
				}));
			}
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			if(part === "add") {
				this.OnAddNewBuzzword();
			} else if(part == "merge") {
				this.OnMergeBuzzwords();
			}
		},
		
		addBuzzwordToLists: function(buzzword) {
			dojo.forEach(Query("ul.popup_buzzword_list"), Lang.hitch(this, function(listNode, index, arr) {
				var clearNode = Query("div.clear", listNode)[0];
				
				DomConstruct.create("li", {
					className:		"ui-state-default popup_buzzword_item",
					innerHTML:		buzzword
				}, clearNode, "before");
			}));
		},
		
		removeBuzzwordFromLists: function(buzzword) {
			dojo.forEach(Query("li.popup_buzzword_item"), Lang.hitch(this, function(itemNode, index, arr) {
				if(DomAttr.get(itemNode, "innerHTML") === buzzword) {
					DomConstruct.destroy(itemNode);
				}
			}));
		},
		
		addBuzzwordToMergeSelects: function(id, buzzword) {
			var selectOneNode = Query("select#buzzword_merge_one")[0];
			var selectTwoNode = Query("select#buzzword_merge_two")[0];
			
			DomConstruct.create("option", {
				value:		id,
				innerHTML:	buzzword
			}, selectOneNode, "last");
			
			DomConstruct.create("option", {
				value:		id,
				innerHTML:	buzzword
			}, selectTwoNode, "last");
		},
		
		removeBuzzwordFromMergeSelects: function(buzzword) {
			var OptionNodes = Query("select#buzzword_merge_one option, select#buzzword_merge_two option");
			
			dojo.forEach(OptionNodes, Lang.hitch(this, function(optionNode, index, arr) {
				if(DomAttr.get(optionNode, "innerHTML") === buzzword) {
					DomConstruct.destroy(optionNode);
				}
			}));
		},
		
		addBuzzwordToEditTab: function(id, buzzword) {
			var divNode = Query("div#edit_tab div#content_row_one")[0];
			
			var rowDivNode = DomConstruct.create("div", {
				className:		"input_row"
			}, divNode, "last");
			
				DomConstruct.create("input", {
					className:		"buzzword_change_name size_200",
					type:			"text",
					value:			buzzword
				}, rowDivNode, "last");
				
				DomConstruct.create("input", {
					className:		"popup_button buzzword_change mandatory",
					type:			"button",
					value:			"Ändern",
					name:			"form_data[" + id + "]"
				}, rowDivNode, "last")
				
				DomConstruct.create("input", {
					className:		"popup_button buzzword_attach",
					type:			"button",
					value:			"Einträge zuordnen",
					name:			"form_data[" + id + "]"
				}, rowDivNode, "last")
				
				DomConstruct.create("input", {
					className:		"popup_button buzzword_delete",
					type:			"button",
					value:			"Löschen",
					name:			"form_data[" + id + "]"
				}, rowDivNode, "last")
		},
		
		removeBuzzwordFromEditTab: function(buzzword) {
			dojo.forEach(Query("input.buzzword_change_name"), Lang.hitch(this, function(inputNode, index, arr) {
				if (inputNode.value == buzzword) {
					var rowDivNode = new dojo.NodeList(inputNode).parents("div.input_row")[0];
					DomConstruct.destroy(rowDivNode);
					return true;
				}
			}));
		},
		
		OnAddNewBuzzword: function(roomId) {
			roomId = roomId || null;
			
			// get buzzword
			var buzzword = Lang.trim(DomAttr.get(Query("input#buzzword_create_name")[0], "value"));
			
			console.log(buzzword);
			
			
			if(buzzword !== "") {
				// send ajax request
				this.AJAXRequest("buzzwords", "createNewBuzzword", { buzzword: buzzword, roomId: this.contextId },
					Lang.hitch(this, function(response) {
						// add the new buzzword to all lists
						this.addBuzzwordToLists(buzzword);
						
						// add the new buzzwords to the merge select boxes
						this.addBuzzwordToMergeSelects(response.id, buzzword);
						
						// add the new buzzword to the edit tab
						this.addBuzzwordToEditTab(response.id, buzzword);
						
						this.destroyLoading();
						
						if (this.contextId) {
							Topic.publish("newOwnRoomBuzzword", {});
						}
					}),
					
					Lang.hitch(this, function(response) {
						// an error means the buzzwords is empty or already exists, so highlight things a little bit
						if(response.code == "107") {
							dojo.forEach(Query("li.popup_buzzword_item"), Lang.hitch(this, function(buzzwordNode, index, arr) {
								var buzzName = DomAttr.get(buzzwordNode, "innerHTML");
								
								DomStyle.set(buzzwordNode, "color", (buzzName === buzzword) ? "red" : "#393939");
							}));
						}
						
						this.destroyLoading();
					})
				);
			}
		},
		
		OnMergeBuzzwords: function() {
			// get the two ids to merge
			var mergeIdOne = DomAttr.get(Query("select#buzzword_merge_one")[0], "value");
			var mergeIdTwo = DomAttr.get(Query("select#buzzword_merge_two")[0], "value");
			
			if(mergeIdOne !== mergeIdTwo) {
				// send ajax request
				this.AJAXRequest("buzzwords", "mergeBuzzwords", { idOne: mergeIdOne, idTwo: mergeIdTwo },
					Lang.hitch(this, function(response) {
						// remove both buzzwords from all lists and add the new one
						this.removeBuzzwordFromLists(response.buzzwordOne);
						this.removeBuzzwordFromLists(response.buzzwordTwo);
						this.addBuzzwordToLists(response.newBuzzword);
						
						// remove both buzzwords from the merge select boxes and add the new one
						this.removeBuzzwordFromMergeSelects(response.buzzwordOne);
						this.removeBuzzwordFromMergeSelects(response.buzzwordTwo);
						this.addBuzzwordToMergeSelects(mergeIdOne, response.newBuzzword);
						
						this.destroyLoading();
						
						if (this.contextId) {
							Topic.publish("newOwnRoomBuzzword", {});
						}
					}),
					
					Lang.hitch(this, function(response) {
						this.destroyLoading();
					})
				);
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
		}
	});
});
},
'commsy/popups/ClickDiscussionPopup':function(){
define("commsy/popups/ClickDiscussionPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/topic"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On, Topic) {
	return declare(ClickPopupHandler, {
		constructor: function() {

		},

		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "discussion";
			this.editType = customObject.editType;
			this.contextId = customObject.contextId;

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
		},

		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			var search = {
				tabs: [
					{ id: "rights_tab" },
					{ id: "buzzwords_tab", group: "buzzwords" },
					{ id: "tags_tab", group: "tags" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[discussion_type]']", this.contentNode) },
				    { query: query("input[name='form_data[subject]']", this.contentNode) },
				    { query: query("input.tabStatus", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) }
				]
			};

			this.submit(search, { contextId: this.contextId } );
		},

		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					if (this.contextId) {
						this.close();
						Topic.publish("newOwnRoomItem", { itemId: item_id });
					} else {
						this.reload(item_id);
					}
				}));
			} else {
				if (this.contextId) {
					this.close();
					var aNode = query("a#listItem" + item_id)[0];
					if (aNode) {
						aNode.click();
					}
				} else {
					this.reload(item_id);
				}
			}
		}
	});
});
},
'commsy/popups/ClickTagsPopup':function(){
define("commsy/popups/ClickTagsPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on",
        	"dojo/NodeList-traverse"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, DomAttr, DomStyle, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			//this.item_id = customObject.iid;
			this.module = "tags";
			this.tree= null;
			
			this.features = [ ];
			
			this.contextId = customObject.contextId;
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			require(["commsy/EditTree"], Lang.hitch(this, function(EditTree) {
				this.tree = new EditTree({
					followUrl:		false,
					checkboxes:		false,
					room_id:		this.contextId,
					expanded:		false,
					item_id:		this.item_id
				});
				this.tree.setupTree(Query("div.tree", this.contentNode)[0], Lang.hitch(this, function(tree) {					
					On(tree.tree, "open", Lang.hitch(this, function(item, node) {
						this.tree.addCreateAndRenameToAllLabels();
					}));
				}));
			}));
			
			// this will handle both select boxes in merge tab
			var selectOneNode = Query("select#tag_merge_one")[0];
			var selectTwoNode = Query("select#tag_merge_two")[0];
			
			On(selectOneNode, "change", Lang.hitch(this, function(event) {
				// when changing box one, disable the selected value in box two
				this.enableAllOptionsExceptOne(selectTwoNode, DomAttr.get(event.target, "value"));
			}));
			On(selectTwoNode, "change", Lang.hitch(this, function(event) {
				// when changing box two, disable the selected value in box one
				this.enableAllOptionsExceptOne(selectOneNode, DomAttr.get(event.target, "value"));
			}));
			
			// setup list
			require(["commsy/List"], Lang.hitch(this, function(List) {
				this.list = new List();
				this.list.init(this.cid, this.from_php.template.tpl_path, {
					activatorNode:	Query("a.list_activator")[0],
					module:			"tags",
					roomId:			this.contextId,
					OnInitDone:		Lang.hitch(this, function() {
						this.list.performRequest();
					})
				});
				
				// set initial buzzword to first in attach tab
				var firstAttachTagNode = Query("div#attach_tab input.tag_attach")[0];
				if (firstAttachTagNode) {
					var tagId = DomAttr.get(firstAttachTagNode, "id");
					this.list.requestData.item_id = tagId;
					this.list.requestData.contextId = this.contextId;
				}
			}));
			
			// connect all assignment buttons in attach tab
			dojo.forEach(Query("input.tag_attach"), Lang.hitch(this, function(inputNode, index, arr) {
				On(inputNode, "click", Lang.hitch(this, function(event) {
					// get name and extract buzzword id
					var nameAttr = DomAttr.get(inputNode, "name");
					var tagId = nameAttr.substr(10, nameAttr.length-11);
					
					// update reference id of list and perform a new request
					this.list.requestData.item_id = tagId;
					this.list.requestData.contextId = this.contextId;
					this.list.performRequest();
					
					// update header
					var tagName = DomAttr.get(new dojo.NodeList(inputNode).siblings("label")[0], "innerHTML");
					DomAttr.set(Query("div.open_close_head span.text_important")[0], "innerHTML", "&bdquo;" + tagName + "&rdquo;");
				}));
			}));
		},
		
		enableAllOptionsExceptOne: function(selectNode, exception) {
			var optionNodes = Query("option", selectNode);
			
			// handle disabled state
			dojo.forEach(optionNodes, Lang.hitch(this, function(optionNode, index, arr) {
				if(DomAttr.get(optionNode, "value") === exception) {
					DomAttr.set(optionNode, "disabled", "disabled");
				} else {
					if(DomAttr.has(optionNode, "disabled")) {
						DomAttr.remove(optionNode, "disabled");
					}
				}
			}));
			
			// try to find a value, that is not the excepted one - this happens when it was selected before
			if(DomAttr.get(selectNode, "value") === exception) {
				var skip = false;
				dojo.some(optionNodes, Lang.hitch(this, function(optionNode, index, arr) {
					if(skip) return false;
					
					var optionValue = DomAttr.get(optionNode, "value");
					if(optionValue !== exception) {
						DomAttr.set(selectNode, "value", optionValue);
						skip = true;
					}
				}));
			}
		},
		
		addTagToLists: function(tag) {
			dojo.forEach(Query("ul.popup_tag_list"), Lang.hitch(this, function(listNode, index, arr) {
				var clearNode = Query("div.clear", listNode)[0];
				
				DomConstruct.create("li", {
					className:		"ui-state-default popup_buzzword_item",
					innerHTML:		tag
				}, clearNode, "before");
			}));
		},
		
		removeTagFromLists: function(tag) {
			dojo.forEach(Query("li.popup_tag_item"), Lang.hitch(this, function(itemNode, index, arr) {
				if(DomAttr.get(itemNode, "innerHTML") === tag) {
					DomConstruct.destroy(itemNode);
				}
			}));
		},
		
		addTagToMergeSelects: function(id, tag) {
			var selectOneNode = Query("select#tag_merge_one")[0];
			var selectTwoNode = Query("select#tag_merge_two")[0];
			
			DomConstruct.create("option", {
				value:		id,
				innerHTML:	tag
			}, selectOneNode, "last");
			
			DomConstruct.create("option", {
				value:		id,
				innerHTML:	tag
			}, selectTwoNode, "last");
		},
		
		removeTagFromMergeSelects: function(tag) {
			var OptionNodes = Query("select#tag_merge_one option, select#tag_merge_two option");
			
			dojo.forEach(OptionNodes, Lang.hitch(this, function(optionNode, index, arr) {
				if(DomAttr.get(optionNode, "innerHTML") === tag) {
					DomConstruct.destroy(optionNode);
				}
			}));
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			if(part === "sort_abc") {
				this.onSortABC();
			} else if(part == "merge") {
				this.onMergeTags();
			}
		},
		
		onSortABC: function() {
			this.AJAXRequest("tags", "sortABC", { roomId: this.contextId },
				Lang.hitch(this, function(response) {
					this.close();
				})
			);
		},
		
		onMergeTags: function() {
			// get the two ids to merge
			var mergeIdOne = DomAttr.get(Query("select#tag_merge_one")[0], "value");
			var mergeIdTwo = DomAttr.get(Query("select#tag_merge_two")[0], "value");
			
			if(mergeIdOne !== mergeIdTwo) {
				// send ajax request
				this.AJAXRequest("tags", "mergeTags", { idOne: mergeIdOne, idTwo: mergeIdTwo },
					Lang.hitch(this, function(response) {
						// remove both tags from all lists and add the new one
						//this.removeTagFromLists(response.tagOne);
						//this.removeTagFromLists(response.tagTwo);
						//this.addTagToLists(response.newTag);
						
						// remove both tags from the merge select boxes and add the new one
						/*
						this.removeTagFromMergeSelects(response.tagOne);
						this.removeTagFromMergeSelects(response.tagTwo);
						this.addTagToMergeSelects(mergeIdOne, response.newTag);
						*/
						
						this.close();
					}),
					
					Lang.hitch(this, function(response) {
						
					})
				);
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
			location.reload();
		}
	});
});
},
'dijit/form/_ToggleButtonMixin':function(){
define("dijit/form/_ToggleButtonMixin", [
	"dojo/_base/declare", // declare
	"dojo/dom-attr" // domAttr.set
], function(declare, domAttr){

// module:
//		dijit/form/_ToggleButtonMixin

return declare("dijit.form._ToggleButtonMixin", null, {
	// summary:
	//		A mixin to provide functionality to allow a button that can be in two states (checked or not).

	// checked: Boolean
	//		Corresponds to the native HTML `<input>` element's attribute.
	//		In markup, specified as "checked='checked'" or just "checked".
	//		True if the button is depressed, or the checkbox is checked,
	//		or the radio button is selected, etc.
	checked: false,

	// aria-pressed for toggle buttons, and aria-checked for checkboxes
	_aria_attr: "aria-pressed",

	_onClick: function(/*Event*/ evt){
		var original = this.checked;
		this._set('checked', !original); // partially set the toggled value, assuming the toggle will work, so it can be overridden in the onclick handler
		var ret = this.inherited(arguments); // the user could reset the value here
		this.set('checked', ret ? this.checked : original); // officially set the toggled or user value, or reset it back
		return ret;
	},

	_setCheckedAttr: function(/*Boolean*/ value, /*Boolean?*/ priorityChange){
		this._set("checked", value);
		domAttr.set(this.focusNode || this.domNode, "checked", value);
		(this.focusNode || this.domNode).setAttribute(this._aria_attr, value ? "true" : "false"); // aria values should be strings
		this._handleOnChange(value, priorityChange);
	},

	reset: function(){
		// summary:
		//		Reset the widget's value to what it was at initialization time

		this._hasBeenBlurred = false;

		// set checked state to original setting
		this.set('checked', this.params.checked || false);
	}
});

});

},
'dojo/dnd/Container':function(){
define("dojo/dnd/Container", [
	"../_base/array",
	"../_base/declare",
	"../_base/event",
	"../_base/kernel",
	"../_base/lang",
	"../_base/window",
	"../dom",
	"../dom-class",
	"../dom-construct",
	"../Evented",
	"../has",
	"../on",
	"../query",
	"../ready",
	"../touch",
	"./common"
], function(
	array, declare, event, kernel, lang, win,
	dom, domClass, domConstruct, Evented, has, on, query, ready, touch, dnd){

// module:
//		dojo/dnd/Container

/*
	Container states:
		""		- normal state
		"Over"	- mouse over a container
	Container item states:
		""		- normal state
		"Over"	- mouse over a container item
*/



var Container = declare("dojo.dnd.Container", Evented, {
	// summary:
	//		a Container object, which knows when mouse hovers over it,
	//		and over which element it hovers

	// object attributes (for markup)
	skipForm: false,
	// allowNested: Boolean
	//		Indicates whether to allow dnd item nodes to be nested within other elements.
	//		By default this is false, indicating that only direct children of the container can
	//		be draggable dnd item nodes
	allowNested: false,
	/*=====
	// current: DomNode
	//		The DOM node the mouse is currently hovered over
	current: null,

	// map: Hash<String, Container.Item>
	//		Map from an item's id (which is also the DOMNode's id) to
	//		the dojo/dnd/Container.Item itself.
	map: {},
	=====*/

	constructor: function(node, params){
		// summary:
		//		a constructor of the Container
		// node: Node
		//		node or node's id to build the container on
		// params: Container.__ContainerArgs
		//		a dictionary of parameters
		this.node = dom.byId(node);
		if(!params){ params = {}; }
		this.creator = params.creator || null;
		this.skipForm = params.skipForm;
		this.parent = params.dropParent && dom.byId(params.dropParent);

		// class-specific variables
		this.map = {};
		this.current = null;

		// states
		this.containerState = "";
		domClass.add(this.node, "dojoDndContainer");

		// mark up children
		if(!(params && params._skipStartup)){
			this.startup();
		}

		// set up events
		this.events = [
			on(this.node, touch.over, lang.hitch(this, "onMouseOver")),
			on(this.node, touch.out,  lang.hitch(this, "onMouseOut")),
			// cancel text selection and text dragging
			on(this.node, "dragstart",   lang.hitch(this, "onSelectStart")),
			on(this.node, "selectstart", lang.hitch(this, "onSelectStart"))
		];
	},

	// object attributes (for markup)
	creator: function(){
		// summary:
		//		creator function, dummy at the moment
	},

	// abstract access to the map
	getItem: function(/*String*/ key){
		// summary:
		//		returns a data item by its key (id)
		return this.map[key];	// Container.Item
	},
	setItem: function(/*String*/ key, /*Container.Item*/ data){
		// summary:
		//		associates a data item with its key (id)
		this.map[key] = data;
	},
	delItem: function(/*String*/ key){
		// summary:
		//		removes a data item from the map by its key (id)
		delete this.map[key];
	},
	forInItems: function(/*Function*/ f, /*Object?*/ o){
		// summary:
		//		iterates over a data map skipping members that
		//		are present in the empty object (IE and/or 3rd-party libraries).
		o = o || kernel.global;
		var m = this.map, e = dnd._empty;
		for(var i in m){
			if(i in e){ continue; }
			f.call(o, m[i], i, this);
		}
		return o;	// Object
	},
	clearItems: function(){
		// summary:
		//		removes all data items from the map
		this.map = {};
	},

	// methods
	getAllNodes: function(){
		// summary:
		//		returns a list (an array) of all valid child nodes
		return query((this.allowNested ? "" : "> ") + ".dojoDndItem", this.parent);	// NodeList
	},
	sync: function(){
		// summary:
		//		sync up the node list with the data map
		var map = {};
		this.getAllNodes().forEach(function(node){
			if(node.id){
				var item = this.getItem(node.id);
				if(item){
					map[node.id] = item;
					return;
				}
			}else{
				node.id = dnd.getUniqueId();
			}
			var type = node.getAttribute("dndType"),
				data = node.getAttribute("dndData");
			map[node.id] = {
				data: data || node.innerHTML,
				type: type ? type.split(/\s*,\s*/) : ["text"]
			};
		}, this);
		this.map = map;
		return this;	// self
	},
	insertNodes: function(data, before, anchor){
		// summary:
		//		inserts an array of new nodes before/after an anchor node
		// data: Array
		//		a list of data items, which should be processed by the creator function
		// before: Boolean
		//		insert before the anchor, if true, and after the anchor otherwise
		// anchor: Node
		//		the anchor node to be used as a point of insertion
		if(!this.parent.firstChild){
			anchor = null;
		}else if(before){
			if(!anchor){
				anchor = this.parent.firstChild;
			}
		}else{
			if(anchor){
				anchor = anchor.nextSibling;
			}
		}
		var i, t;
		if(anchor){
			for(i = 0; i < data.length; ++i){
				t = this._normalizedCreator(data[i]);
				this.setItem(t.node.id, {data: t.data, type: t.type});
				anchor.parentNode.insertBefore(t.node, anchor);
			}
		}else{
			for(i = 0; i < data.length; ++i){
				t = this._normalizedCreator(data[i]);
				this.setItem(t.node.id, {data: t.data, type: t.type});
				this.parent.appendChild(t.node);
			}
		}
		return this;	// self
	},
	destroy: function(){
		// summary:
		//		prepares this object to be garbage-collected
		array.forEach(this.events, function(handle){ handle.remove(); });
		this.clearItems();
		this.node = this.parent = this.current = null;
	},

	// markup methods
	markupFactory: function(params, node, Ctor){
		params._skipStartup = true;
		return new Ctor(node, params);
	},
	startup: function(){
		// summary:
		//		collects valid child items and populate the map

		// set up the real parent node
		if(!this.parent){
			// use the standard algorithm, if not assigned
			this.parent = this.node;
			if(this.parent.tagName.toLowerCase() == "table"){
				var c = this.parent.getElementsByTagName("tbody");
				if(c && c.length){ this.parent = c[0]; }
			}
		}
		this.defaultCreator = dnd._defaultCreator(this.parent);

		// process specially marked children
		this.sync();
	},

	// mouse events
	onMouseOver: function(e){
		// summary:
		//		event processor for onmouseover or touch, to mark that element as the current element
		// e: Event
		//		mouse event
		var n = e.relatedTarget;
		while(n){
			if(n == this.node){ break; }
			try{
				n = n.parentNode;
			}catch(x){
				n = null;
			}
		}
		if(!n){
			this._changeState("Container", "Over");
			this.onOverEvent();
		}
		n = this._getChildByEvent(e);
		if(this.current == n){ return; }
		if(this.current){ this._removeItemClass(this.current, "Over"); }
		if(n){ this._addItemClass(n, "Over"); }
		this.current = n;
	},
	onMouseOut: function(e){
		// summary:
		//		event processor for onmouseout
		// e: Event
		//		mouse event
		for(var n = e.relatedTarget; n;){
			if(n == this.node){ return; }
			try{
				n = n.parentNode;
			}catch(x){
				n = null;
			}
		}
		if(this.current){
			this._removeItemClass(this.current, "Over");
			this.current = null;
		}
		this._changeState("Container", "");
		this.onOutEvent();
	},
	onSelectStart: function(e){
		// summary:
		//		event processor for onselectevent and ondragevent
		// e: Event
		//		mouse event
		if(!this.skipForm || !dnd.isFormElement(e)){
			event.stop(e);
		}
	},

	// utilities
	onOverEvent: function(){
		// summary:
		//		this function is called once, when mouse is over our container
	},
	onOutEvent: function(){
		// summary:
		//		this function is called once, when mouse is out of our container
	},
	_changeState: function(type, newState){
		// summary:
		//		changes a named state to new state value
		// type: String
		//		a name of the state to change
		// newState: String
		//		new state
		var prefix = "dojoDnd" + type;
		var state  = type.toLowerCase() + "State";
		//domClass.replace(this.node, prefix + newState, prefix + this[state]);
		domClass.replace(this.node, prefix + newState, prefix + this[state]);
		this[state] = newState;
	},
	_addItemClass: function(node, type){
		// summary:
		//		adds a class with prefix "dojoDndItem"
		// node: Node
		//		a node
		// type: String
		//		a variable suffix for a class name
		domClass.add(node, "dojoDndItem" + type);
	},
	_removeItemClass: function(node, type){
		// summary:
		//		removes a class with prefix "dojoDndItem"
		// node: Node
		//		a node
		// type: String
		//		a variable suffix for a class name
		domClass.remove(node, "dojoDndItem" + type);
	},
	_getChildByEvent: function(e){
		// summary:
		//		gets a child, which is under the mouse at the moment, or null
		// e: Event
		//		a mouse event
		var node = e.target;
		if(node){
			for(var parent = node.parentNode; parent; node = parent, parent = node.parentNode){
				if((parent == this.parent || this.allowNested) && domClass.contains(node, "dojoDndItem")){ return node; }
			}
		}
		return null;
	},
	_normalizedCreator: function(/*Container.Item*/ item, /*String*/ hint){
		// summary:
		//		adds all necessary data to the output of the user-supplied creator function
		var t = (this.creator || this.defaultCreator).call(this, item, hint);
		if(!lang.isArray(t.type)){ t.type = ["text"]; }
		if(!t.node.id){ t.node.id = dnd.getUniqueId(); }
		domClass.add(t.node, "dojoDndItem");
		return t;
	}
});

dnd._createNode = function(tag){
	// summary:
	//		returns a function, which creates an element of given tag
	//		(SPAN by default) and sets its innerHTML to given text
	// tag: String
	//		a tag name or empty for SPAN
	if(!tag){ return dnd._createSpan; }
	return function(text){	// Function
		return domConstruct.create(tag, {innerHTML: text});	// Node
	};
};

dnd._createTrTd = function(text){
	// summary:
	//		creates a TR/TD structure with given text as an innerHTML of TD
	// text: String
	//		a text for TD
	var tr = domConstruct.create("tr");
	domConstruct.create("td", {innerHTML: text}, tr);
	return tr;	// Node
};

dnd._createSpan = function(text){
	// summary:
	//		creates a SPAN element with given text as its innerHTML
	// text: String
	//		a text for SPAN
	return domConstruct.create("span", {innerHTML: text});	// Node
};

// dnd._defaultCreatorNodes: Object
//		a dictionary that maps container tag names to child tag names
dnd._defaultCreatorNodes = {ul: "li", ol: "li", div: "div", p: "div"};

dnd._defaultCreator = function(node){
	// summary:
	//		takes a parent node, and returns an appropriate creator function
	// node: Node
	//		a container node
	var tag = node.tagName.toLowerCase();
	var c = tag == "tbody" || tag == "thead" ? dnd._createTrTd :
			dnd._createNode(dnd._defaultCreatorNodes[tag]);
	return function(item, hint){	// Function
		var isObj = item && lang.isObject(item), data, type, n;
		if(isObj && item.tagName && item.nodeType && item.getAttribute){
			// process a DOM node
			data = item.getAttribute("dndData") || item.innerHTML;
			type = item.getAttribute("dndType");
			type = type ? type.split(/\s*,\s*/) : ["text"];
			n = item;	// this node is going to be moved rather than copied
		}else{
			// process a DnD item object or a string
			data = (isObj && item.data) ? item.data : item;
			type = (isObj && item.type) ? item.type : ["text"];
			n = (hint == "avatar" ? dnd._createSpan : c)(String(data));
		}
		if(!n.id){
			n.id = dnd.getUniqueId();
		}
		return {node: n, data: data, type: type};
	};
};

/*=====
Container.__ContainerArgs = declare([], {
	creator: function(){
		// summary:
		//		a creator function, which takes a data item, and returns an object like that:
		//		{node: newNode, data: usedData, type: arrayOfStrings}
	},

	// skipForm: Boolean
	//		don't start the drag operation, if clicked on form elements
	skipForm: false,

	// dropParent: Node||String
	//		node or node's id to use as the parent node for dropped items
	//		(must be underneath the 'node' parameter in the DOM)
	dropParent: null,

	// _skipStartup: Boolean
	//		skip startup(), which collects children, for deferred initialization
	//		(this is used in the markup mode)
	_skipStartup: false
});

Container.Item = function(){
	// summary:
	//		Represents (one of) the source node(s) being dragged.
	//		Contains (at least) the "type" and "data" attributes.
	// type: String[]
	//		Type(s) of this item, by default this is ["text"]
	// data: Object
	//		Logical representation of the object being dragged.
	//		If the drag object's type is "text" then data is a String,
	//		if it's another type then data could be a different Object,
	//		perhaps a name/value hash.

	this.type = type;
	this.data = data;
};
=====*/

return Container;
});

},
'commsy/popups/ClickGroupPopup':function(){
define("commsy/popups/ClickGroupPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		sendImages: [],

		constructor: function() {
			this.sendImages = [];
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "group";
			this.editType = customObject.editType;

			this.features = [ "editor", "upload", "netnavigation", "calendar", "upload-single" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
			dojo.ready(lang.hitch(this, function() {
				// setup callback for single upload
				this.featureHandles["upload-single"][0].setCallback(lang.hitch(this, function(fileInfo) {
					// setup preview
					var formNode = this.featureHandles["upload-single"][0].uploader.form;
					var previewNode = query("div.filePreview", formNode)[0];

					domConstruct.empty(previewNode);

					domConstruct.create("img", {
						src:		"commsy.php?cid=" + this.uri_object.cid + "&mod=picture&fct=getTemp&fileName=" + fileInfo.file
					}, previewNode, "last");

					this.sendImages.push({ part: "upload_picture", fileInfo: fileInfo });
				}));
			}));
		},

		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			var search = {
				tabs: [
					{ id: "rights_tab" },
					{ id: "grouproom_tab" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("div#popup_content", this.contentNode) }
				]
			};

			this.submit(search);
		},

		onPopupSubmitSuccess: function(item_id) {
			if (this.sendImages.length > 0) {
				// send ajax request
				var data = {
					module:			"group",
					additional: {
						action:		this.sendImages[0].part,
					    fileInfo:	this.sendImages[0].fileInfo,
					    iid:		item_id
					}
				};
			}

			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					if (this.sendImages.length > 0) {
						this.AJAXRequest("popup", "save", data, lang.hitch(this, function(response) {
							this.reload(item_id);
						}));
					} else {
						this.reload(item_id);
					}
				}));
			} else {
				if (this.sendImages.length > 0) {
					this.AJAXRequest("popup", "save", data, lang.hitch(this, function(response) {
						this.reload(item_id);
					}));
				} else {
					this.reload(item_id);
				}
			}
		}
	});
});
},
'commsy/popups/ClickMailtomodPopup':function(){
define("commsy/popups/ClickMailtomodPopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "mailtomod";
			this.editType = customObject.mailType;
			
			this.features = [ ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
		},
		
		onPopupSubmit: function(customObject) {
			// setup data to send via ajax
			var search = {
				tabs: [ ],
				nodeLists: [
				    { query: query("div#reciever", this.contentNode), group: "reciever"},
				    { query: query("textarea[name='form_data[mailcontent]']", this.contentNode) },
				    { query: query("input[name='form_data[subject]']", this.contentNode) }
				]
			};
			
			this.submit(search);
		},
		
		onPopupSubmitSuccess: function(item_id) {
			this.close();
		}
	});
});
},
'commsy/popups/ClickDatePopup':function(){
define("commsy/popups/ClickDatePopup", [	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/topic"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On, Topic) {
	return declare(ClickPopupHandler, {
		constructor: function() {

		},

		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "date";
			this.editType = customObject.editType;
			this.contextId = customObject.contextId;
			this.date_new =  customObject.date_new;

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
			// recurring dates
			var selectNode = query("select[name='form_data[recurring_select]']")[0];
			var recurringDetailNodes = query("div[id^='recurring_details_']");

			if (selectNode) {
				On(selectNode, "change", lang.hitch(this, function(event) {
					var value = domAttr.get(selectNode, "value");

					// hide all
					dojo.forEach(recurringDetailNodes, lang.hitch(this, function(node, index, arr) {
						dom_class.add(node, "hidden");
					}));

					// display specific
					dom_class.remove(query("div#recurring_details_" + value)[0], "hidden");
				}));
			}
		},

		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			var search = {
				tabs: [
					{ id: "rights_tab" },
					{ id: "addon_tab" },
					{ id: "buzzwords_tab", group: "buzzwords" },
					{ id: "tags_tab", group: "tags" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[dayStart]']", this.contentNode) },
				    { query: query("input[name='form_data[timeStart]']", this.contentNode) },
				    { query: query("input[name='form_data[dayEnd]']", this.contentNode) },
				    { query: query("input[name='form_data[timeEnd]']", this.contentNode) },
				    { query: query("input[name='form_data[place]']", this.contentNode) },
				    { query: query("input.tabStatus", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) }
				]
			};

			this.submit(search, {part:customObject.part, contextId: this.contextId });
		},

		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					if (this.contextId) {
						this.close();
						Topic.publish("newOwnRoomItem", { itemId: item_id });
					} else {
						this.reload(item_id);
					}
				}));
			} else {
				if (this.contextId) {
					this.close();
					var aNode = query("a#listItem" + item_id)[0];
					if (aNode) {
						aNode.click();
					}
				} else {
					this.reload(item_id);
				}
			}
		}
	});
});
},
'dijit/Tree':function(){
require({cache:{
'url:dijit/templates/TreeNode.html':"<div class=\"dijitTreeNode\" role=\"presentation\"\n\t><div data-dojo-attach-point=\"rowNode\" class=\"dijitTreeRow dijitInline\" role=\"presentation\"\n\t\t><div data-dojo-attach-point=\"indentNode\" class=\"dijitInline\"></div\n\t\t><img src=\"${_blankGif}\" alt=\"\" data-dojo-attach-point=\"expandoNode\" class=\"dijitTreeExpando\" role=\"presentation\"\n\t\t/><span data-dojo-attach-point=\"expandoNodeText\" class=\"dijitExpandoText\" role=\"presentation\"\n\t\t></span\n\t\t><span data-dojo-attach-point=\"contentNode\"\n\t\t\tclass=\"dijitTreeContent\" role=\"presentation\">\n\t\t\t<img src=\"${_blankGif}\" alt=\"\" data-dojo-attach-point=\"iconNode\" class=\"dijitIcon dijitTreeIcon\" role=\"presentation\"\n\t\t\t/><span data-dojo-attach-point=\"labelNode\" class=\"dijitTreeLabel\" role=\"treeitem\" tabindex=\"-1\" aria-selected=\"false\"></span>\n\t\t</span\n\t></div>\n\t<div data-dojo-attach-point=\"containerNode\" class=\"dijitTreeContainer\" role=\"presentation\" style=\"display: none;\"></div>\n</div>\n",
'url:dijit/templates/Tree.html':"<div class=\"dijitTree dijitTreeContainer\" role=\"tree\">\n\t<div class=\"dijitInline dijitTreeIndent\" style=\"position: absolute; top: -9999px\" data-dojo-attach-point=\"indentDetector\"></div>\n</div>\n"}});
define("dijit/Tree", [
	"dojo/_base/array", // array.filter array.forEach array.map
	"dojo/_base/connect",	// connect.isCopyKey()
	"dojo/cookie", // cookie
	"dojo/_base/declare", // declare
	"dojo/Deferred", // Deferred
	"dojo/DeferredList", // DeferredList
	"dojo/dom", // dom.isDescendant
	"dojo/dom-class", // domClass.add domClass.remove domClass.replace domClass.toggle
	"dojo/dom-geometry", // domGeometry.setMarginBox domGeometry.position
	"dojo/dom-style",// domStyle.set
	"dojo/_base/event", // event.stop
	"dojo/errors/create",	// createError
	"dojo/fx", // fxUtils.wipeIn fxUtils.wipeOut
	"dojo/_base/kernel", // kernel.deprecated
	"dojo/keys",	// arrows etc.
	"dojo/_base/lang", // lang.getObject lang.mixin lang.hitch
	"dojo/on",		// on(), on.selector()
	"dojo/topic",
	"dojo/touch",
	"dojo/when",
	"./focus",
	"./registry",	// registry.byNode(), registry.getEnclosingWidget()
	"./_base/manager",	// manager.defaultDuration
	"./_Widget",
	"./_TemplatedMixin",
	"./_Container",
	"./_Contained",
	"./_CssStateMixin",
	"dojo/text!./templates/TreeNode.html",
	"dojo/text!./templates/Tree.html",
	"./tree/TreeStoreModel",
	"./tree/ForestStoreModel",
	"./tree/_dndSelector"
], function(array, connect, cookie, declare, Deferred, DeferredList,
			dom, domClass, domGeometry, domStyle, event, createError, fxUtils, kernel, keys, lang, on, topic, touch, when,
			focus, registry, manager, _Widget, _TemplatedMixin, _Container, _Contained, _CssStateMixin,
			treeNodeTemplate, treeTemplate, TreeStoreModel, ForestStoreModel, _dndSelector){

// module:
//		dijit/Tree

// Back-compat shim
Deferred = declare(Deferred, {
	addCallback: function(callback){ this.then(callback); },
	addErrback: function(errback){ this.then(null, errback); }
});

var TreeNode = declare(
	"dijit._TreeNode",
	[_Widget, _TemplatedMixin, _Container, _Contained, _CssStateMixin],
{
	// summary:
	//		Single node within a tree.   This class is used internally
	//		by Tree and should not be accessed directly.
	// tags:
	//		private

	// item: [const] Item
	//		the dojo.data entry this tree represents
	item: null,

	// isTreeNode: [protected] Boolean
	//		Indicates that this is a TreeNode.   Used by `dijit.Tree` only,
	//		should not be accessed directly.
	isTreeNode: true,

	// label: String
	//		Text of this tree node
	label: "",
	_setLabelAttr: {node: "labelNode", type: "innerText"},

	// isExpandable: [private] Boolean
	//		This node has children, so show the expando node (+ sign)
	isExpandable: null,

	// isExpanded: [readonly] Boolean
	//		This node is currently expanded (ie, opened)
	isExpanded: false,

	// state: [private] String
	//		Dynamic loading-related stuff.
	//		When an empty folder node appears, it is "UNCHECKED" first,
	//		then after dojo.data query it becomes "LOADING" and, finally "LOADED"
	state: "UNCHECKED",

	templateString: treeNodeTemplate,

	baseClass: "dijitTreeNode",

	// For hover effect for tree node, and focus effect for label
	cssStateNodes: {
		rowNode: "dijitTreeRow"
	},

	// Tooltip is defined in _WidgetBase but we need to handle the mapping to DOM here
	_setTooltipAttr: {node: "rowNode", type: "attribute", attribute: "title"},

	buildRendering: function(){
		this.inherited(arguments);

		// set expand icon for leaf
		this._setExpando();

		// set icon and label class based on item
		this._updateItemClasses(this.item);

		if(this.isExpandable){
			this.labelNode.setAttribute("aria-expanded", this.isExpanded);
		}

		//aria-selected should be false on all selectable elements.
		this.setSelected(false);
	},

	_setIndentAttr: function(indent){
		// summary:
		//		Tell this node how many levels it should be indented
		// description:
		//		0 for top level nodes, 1 for their children, 2 for their
		//		grandchildren, etc.

		// Math.max() is to prevent negative padding on hidden root node (when indent == -1)
		var pixels = (Math.max(indent, 0) * this.tree._nodePixelIndent) + "px";

		domStyle.set(this.domNode, "backgroundPosition", pixels + " 0px");	// TODOC: what is this for???
		domStyle.set(this.indentNode, this.isLeftToRight() ? "paddingLeft" : "paddingRight", pixels);

		array.forEach(this.getChildren(), function(child){
			child.set("indent", indent+1);
		});

		this._set("indent", indent);
	},

	markProcessing: function(){
		// summary:
		//		Visually denote that tree is loading data, etc.
		// tags:
		//		private
		this.state = "LOADING";
		this._setExpando(true);
	},

	unmarkProcessing: function(){
		// summary:
		//		Clear markup from markProcessing() call
		// tags:
		//		private
		this._setExpando(false);
	},

	_updateItemClasses: function(item){
		// summary:
		//		Set appropriate CSS classes for icon and label dom node
		//		(used to allow for item updates to change respective CSS)
		// tags:
		//		private
		var tree = this.tree, model = tree.model;
		if(tree._v10Compat && item === model.root){
			// For back-compat with 1.0, need to use null to specify root item (TODO: remove in 2.0)
			item = null;
		}
		this._applyClassAndStyle(item, "icon", "Icon");
		this._applyClassAndStyle(item, "label", "Label");
		this._applyClassAndStyle(item, "row", "Row");

		this.tree._startPaint(true);		// signifies paint started and finished (synchronously)
	},

	_applyClassAndStyle: function(item, lower, upper){
		// summary:
		//		Set the appropriate CSS classes and styles for labels, icons and rows.
		//
		// item:
		//		The data item.
		//
		// lower:
		//		The lower case attribute to use, e.g. 'icon', 'label' or 'row'.
		//
		// upper:
		//		The upper case attribute to use, e.g. 'Icon', 'Label' or 'Row'.
		//
		// tags:
		//		private

		var clsName = "_" + lower + "Class";
		var nodeName = lower + "Node";
		var oldCls = this[clsName];

		this[clsName] = this.tree["get" + upper + "Class"](item, this.isExpanded);
		domClass.replace(this[nodeName], this[clsName] || "", oldCls || "");

		domStyle.set(this[nodeName], this.tree["get" + upper + "Style"](item, this.isExpanded) || {});
	},

	_updateLayout: function(){
		// summary:
		//		Set appropriate CSS classes for this.domNode
		// tags:
		//		private
		var parent = this.getParent();
		if(!parent || !parent.rowNode || parent.rowNode.style.display == "none"){
			/* if we are hiding the root node then make every first level child look like a root node */
			domClass.add(this.domNode, "dijitTreeIsRoot");
		}else{
			domClass.toggle(this.domNode, "dijitTreeIsLast", !this.getNextSibling());
		}
	},

	_setExpando: function(/*Boolean*/ processing){
		// summary:
		//		Set the right image for the expando node
		// tags:
		//		private

		var styles = ["dijitTreeExpandoLoading", "dijitTreeExpandoOpened",
						"dijitTreeExpandoClosed", "dijitTreeExpandoLeaf"],
			_a11yStates = ["*","-","+","*"],
			idx = processing ? 0 : (this.isExpandable ?	(this.isExpanded ? 1 : 2) : 3);

		// apply the appropriate class to the expando node
		domClass.replace(this.expandoNode, styles[idx], styles);

		// provide a non-image based indicator for images-off mode
		this.expandoNodeText.innerHTML = _a11yStates[idx];

	},

	expand: function(){
		// summary:
		//		Show my children
		// returns:
		//		Deferred that fires when expansion is complete

		// If there's already an expand in progress or we are already expanded, just return
		if(this._expandDeferred){
			return this._expandDeferred;		// dojo/_base/Deferred
		}

		// cancel in progress collapse operation
		if(this._collapseDeferred){
			this._collapseDeferred.cancel();
			delete this._collapseDeferred;
		}

		// All the state information for when a node is expanded, maybe this should be
		// set when the animation completes instead
		this.isExpanded = true;
		this.labelNode.setAttribute("aria-expanded", "true");
		if(this.tree.showRoot || this !== this.tree.rootNode){
			this.containerNode.setAttribute("role", "group");
		}
		domClass.add(this.contentNode,'dijitTreeContentExpanded');
		this._setExpando();
		this._updateItemClasses(this.item);
		
		if(this == this.tree.rootNode && this.tree.showRoot){
			this.tree.domNode.setAttribute("aria-expanded", "true");
		}

		var def,
			wipeIn = fxUtils.wipeIn({
				node: this.containerNode,
				duration: manager.defaultDuration,
				onEnd: function(){
					def.resolve(true);
				}
			});

		// Deferred that fires when expand is complete
		def = (this._expandDeferred = new Deferred(function(){
			// Canceller
			wipeIn.stop();
		}));

		wipeIn.play();

		return def;		// dojo/_base/Deferred
	},

	collapse: function(){
		// summary:
		//		Collapse this node (if it's expanded)

		if(this._collapseDeferred){
			// Node is already collapsed, or there's a collapse in progress, just return that Deferred
			return this._collapseDeferred;
		}

		// cancel in progress expand operation
		if(this._expandDeferred){
			this._expandDeferred.cancel();
			delete this._expandDeferred;
		}

		this.isExpanded = false;
		this.labelNode.setAttribute("aria-expanded", "false");
		if(this == this.tree.rootNode && this.tree.showRoot){
			this.tree.domNode.setAttribute("aria-expanded", "false");
		}
		domClass.remove(this.contentNode,'dijitTreeContentExpanded');
		this._setExpando();
		this._updateItemClasses(this.item);

		var def,
			wipeOut = fxUtils.wipeOut({
				node: this.containerNode,
				duration: manager.defaultDuration,
				onEnd: function(){
					def.resolve(true);
				}
			});

		// Deferred that fires when expand is complete
		def = (this._collapseDeferred = new Deferred(function(){
			// Canceller
			wipeOut.stop();
		}));

		wipeOut.play();

		return def;		// dojo/_base/Deferred
	},

	// indent: Integer
	//		Levels from this node to the root node
	indent: 0,

	setChildItems: function(/* Object[] */ items){
		// summary:
		//		Sets the child items of this node, removing/adding nodes
		//		from current children to match specified items[] array.
		//		Also, if this.persist == true, expands any children that were previously
		//		opened.
		// returns:
		//		Deferred object that fires after all previously opened children
		//		have been expanded again (or fires instantly if there are no such children).

		var tree = this.tree,
			model = tree.model,
			defs = [];	// list of deferreds that need to fire before I am complete


		// Orphan all my existing children.
		// If items contains some of the same items as before then we will reattach them.
		// Don't call this.removeChild() because that will collapse the tree etc.
		var oldChildren = this.getChildren();
		array.forEach(oldChildren, function(child){
			_Container.prototype.removeChild.call(this, child);
		}, this);

		// All the old children of this TreeNode are subject for destruction if
		//		1) they aren't listed in the new children array (items)
		//		2) they aren't immediately adopted by another node (DnD)
		this.defer(function(){
			array.forEach(oldChildren, function(node){
				if(!node._destroyed && !node.getParent()){
					// If node is in selection then remove it.
					tree.dndController.removeTreeNode(node);

					// Deregister mapping from item id --> this node
					var id = model.getIdentity(node.item),
						ary = tree._itemNodesMap[id];
					if(ary.length == 1){
						delete tree._itemNodesMap[id];
					}else{
						var index = array.indexOf(ary, node);
						if(index != -1){
							ary.splice(index, 1);
						}
					}

					// And finally we can destroy the node
					node.destroyRecursive();
				}
			});
		});

		this.state = "LOADED";

		if(items && items.length > 0){
			this.isExpandable = true;

			// Create _TreeNode widget for each specified tree node, unless one already
			// exists and isn't being used (presumably it's from a DnD move and was recently
			// released
			array.forEach(items, function(item){	// MARKER: REUSE NODE
				var id = model.getIdentity(item),
					existingNodes = tree._itemNodesMap[id],
					node;
				if(existingNodes){
					for(var i=0;i<existingNodes.length;i++){
						if(existingNodes[i] && !existingNodes[i].getParent()){
							node = existingNodes[i];
							node.set('indent', this.indent+1);
							break;
						}
					}
				}
				if(!node){
					node = this.tree._createTreeNode({
						item: item,
						tree: tree,
						isExpandable: model.mayHaveChildren(item),
						label: tree.getLabel(item),
						tooltip: tree.getTooltip(item),
						ownerDocument: tree.ownerDocument,
						dir: tree.dir,
						lang: tree.lang,
						textDir: tree.textDir,
						indent: this.indent + 1
					});
					if(existingNodes){
						existingNodes.push(node);
					}else{
						tree._itemNodesMap[id] = [node];
					}
				}
				this.addChild(node);

				// If node was previously opened then open it again now (this may trigger
				// more data store accesses, recursively)
				if(this.tree.autoExpand || this.tree._state(node)){
					defs.push(tree._expandNode(node));
				}
			}, this);

			// note that updateLayout() needs to be called on each child after
			// _all_ the children exist
			array.forEach(this.getChildren(), function(child){
				child._updateLayout();
			});
		}else{
			this.isExpandable=false;
		}

		if(this._setExpando){
			// change expando to/from dot or + icon, as appropriate
			this._setExpando(false);
		}

		// Set leaf icon or folder icon, as appropriate
		this._updateItemClasses(this.item);

		// On initial tree show, make the selected TreeNode as either the root node of the tree,
		// or the first child, if the root node is hidden
		if(this == tree.rootNode){
			var fc = this.tree.showRoot ? this : this.getChildren()[0];
			if(fc){
				fc.setFocusable(true);
				tree.lastFocused = fc;
			}else{
				// fallback: no nodes in tree so focus on Tree <div> itself
				tree.domNode.setAttribute("tabIndex", "0");
			}
		}

		var def =  new DeferredList(defs);
		this.tree._startPaint(def);		// to reset TreeNode widths after an item is added/removed from the Tree
		return def;		// dojo/_base/Deferred
	},

	getTreePath: function(){
		var node = this;
		var path = [];
		while(node && node !== this.tree.rootNode){
				path.unshift(node.item);
				node = node.getParent();
		}
		path.unshift(this.tree.rootNode.item);

		return path;
	},

	getIdentity: function(){
		return this.tree.model.getIdentity(this.item);
	},

	removeChild: function(/* treeNode */ node){
		this.inherited(arguments);

		var children = this.getChildren();
		if(children.length == 0){
			this.isExpandable = false;
			this.collapse();
		}

		array.forEach(children, function(child){
				child._updateLayout();
		});
	},

	makeExpandable: function(){
		// summary:
		//		if this node wasn't already showing the expando node,
		//		turn it into one and call _setExpando()

		// TODO: hmm this isn't called from anywhere, maybe should remove it for 2.0

		this.isExpandable = true;
		this._setExpando(false);
	},

	setSelected: function(/*Boolean*/ selected){
		// summary:
		//		A Tree has a (single) currently selected node.
		//		Mark that this node is/isn't that currently selected node.
		// description:
		//		In particular, setting a node as selected involves setting tabIndex
		//		so that when user tabs to the tree, focus will go to that node (only).
		this.labelNode.setAttribute("aria-selected", selected ? "true" : "false");
		domClass.toggle(this.rowNode, "dijitTreeRowSelected", selected);
	},

	setFocusable: function(/*Boolean*/ selected){
		// summary:
		//		A Tree has a (single) node that's focusable.
		//		Mark that this node is/isn't that currently focsuable node.
		// description:
		//		In particular, setting a node as selected involves setting tabIndex
		//		so that when user tabs to the tree, focus will go to that node (only).

		this.labelNode.setAttribute("tabIndex", selected ? "0" : "-1");
	},


	_setTextDirAttr: function(textDir){
		if(textDir &&((this.textDir != textDir) || !this._created)){
			this._set("textDir", textDir);
			this.applyTextDir(this.labelNode, this.labelNode.innerText || this.labelNode.textContent || "");
			array.forEach(this.getChildren(), function(childNode){
				childNode.set("textDir", textDir);
			}, this);
		}
	}
});

var Tree = declare("dijit.Tree", [_Widget, _TemplatedMixin], {
	// summary:
	//		This widget displays hierarchical data from a store.

	// store: [deprecated] String|dojo/data/Store
	//		Deprecated.  Use "model" parameter instead.
	//		The store to get data to display in the tree.
	store: null,

	// model: dijit/tree/model
	//		Interface to read tree data, get notifications of changes to tree data,
	//		and for handling drop operations (i.e drag and drop onto the tree)
	model: null,

	// query: [deprecated] anything
	//		Deprecated.  User should specify query to the model directly instead.
	//		Specifies datastore query to return the root item or top items for the tree.
	query: null,

	// label: [deprecated] String
	//		Deprecated.  Use dijit/tree/ForestStoreModel directly instead.
	//		Used in conjunction with query parameter.
	//		If a query is specified (rather than a root node id), and a label is also specified,
	//		then a fake root node is created and displayed, with this label.
	label: "",

	// showRoot: [const] Boolean
	//		Should the root node be displayed, or hidden?
	showRoot: true,

	// childrenAttr: [deprecated] String[]
	//		Deprecated.   This information should be specified in the model.
	//		One ore more attributes that holds children of a tree node
	childrenAttr: ["children"],

	// paths: String[][] or Item[][]
	//		Full paths from rootNode to selected nodes expressed as array of items or array of ids.
	//		Since setting the paths may be asynchronous (because of waiting on dojo.data), set("paths", ...)
	//		returns a Deferred to indicate when the set is complete.
	paths: [],

	// path: String[] or Item[]
	//		Backward compatible singular variant of paths.
	path: [],

	// selectedItems: [readonly] Item[]
	//		The currently selected items in this tree.
	//		This property can only be set (via set('selectedItems', ...)) when that item is already
	//		visible in the tree.   (I.e. the tree has already been expanded to show that node.)
	//		Should generally use `paths` attribute to set the selected items instead.
	selectedItems: null,

	// selectedItem: [readonly] Item
	//		Backward compatible singular variant of selectedItems.
	selectedItem: null,

	// openOnClick: Boolean
	//		If true, clicking a folder node's label will open it, rather than calling onClick()
	openOnClick: false,

	// openOnDblClick: Boolean
	//		If true, double-clicking a folder node's label will open it, rather than calling onDblClick()
	openOnDblClick: false,

	templateString: treeTemplate,

	// persist: Boolean
	//		Enables/disables use of cookies for state saving.
	persist: true,

	// autoExpand: Boolean
	//		Fully expand the tree on load.   Overrides `persist`.
	autoExpand: false,

	// dndController: [protected] Function|String
	//		Class to use as as the dnd controller.  Specifying this class enables DnD.
	//		Generally you should specify this as dijit/tree/dndSource.
	//		Setting of dijit/tree/_dndSelector handles selection only (no actual DnD).
	dndController: _dndSelector,

	// parameters to pull off of the tree and pass on to the dndController as its params
	dndParams: ["onDndDrop","itemCreator","onDndCancel","checkAcceptance", "checkItemAcceptance", "dragThreshold", "betweenThreshold"],

	//declare the above items so they can be pulled from the tree's markup

	// onDndDrop: [protected] Function
	//		Parameter to dndController, see `dijit/tree/dndSource.onDndDrop()`.
	//		Generally this doesn't need to be set.
	onDndDrop: null,

	itemCreator: null,
	/*=====
	itemCreator: function(nodes, target, source){
		// summary:
		//		Returns objects passed to `Tree.model.newItem()` based on DnD nodes
		//		dropped onto the tree.   Developer must override this method to enable
		//		dropping from external sources onto this Tree, unless the Tree.model's items
		//		happen to look like {id: 123, name: "Apple" } with no other attributes.
		//
		//		For each node in nodes[], which came from source, create a hash of name/value
		//		pairs to be passed to Tree.model.newItem().  Returns array of those hashes.
		// nodes: DomNode[]
		//		The DOMNodes dragged from the source container
		// target: DomNode
		//		The target TreeNode.rowNode
		// source: dojo/dnd/Source
		//		The source container the nodes were dragged from, perhaps another Tree or a plain dojo/dnd/Source
		// returns: Object[]
		//		Array of name/value hashes for each new item to be added to the Tree, like:
		// |	[
		// |		{ id: 123, label: "apple", foo: "bar" },
		// |		{ id: 456, label: "pear", zaz: "bam" }
		// |	]
		// tags:
		//		extension
		return [{}];
	},
	=====*/

	// onDndCancel: [protected] Function
	//		Parameter to dndController, see `dijit/tree/dndSource.onDndCancel()`.
	//		Generally this doesn't need to be set.
	onDndCancel: null,

/*=====
	checkAcceptance: function(source, nodes){
		// summary:
		//		Checks if the Tree itself can accept nodes from this source
		// source: dijit/tree/dndSource
		//		The source which provides items
		// nodes: DOMNode[]
		//		Array of DOM nodes corresponding to nodes being dropped, dijitTreeRow nodes if
		//		source is a dijit/Tree.
		// tags:
		//		extension
		return true;	// Boolean
	},
=====*/
	checkAcceptance: null,

/*=====
	checkItemAcceptance: function(target, source, position){
		// summary:
		//		Stub function to be overridden if one wants to check for the ability to drop at the node/item level
		// description:
		//		In the base case, this is called to check if target can become a child of source.
		//		When betweenThreshold is set, position="before" or "after" means that we
		//		are asking if the source node can be dropped before/after the target node.
		// target: DOMNode
		//		The dijitTreeRoot DOM node inside of the TreeNode that we are dropping on to
		//		Use registry.getEnclosingWidget(target) to get the TreeNode.
		// source: dijit/tree/dndSource
		//		The (set of) nodes we are dropping
		// position: String
		//		"over", "before", or "after"
		// tags:
		//		extension
		return true;	// Boolean
	},
=====*/
	checkItemAcceptance: null,

	// dragThreshold: Integer
	//		Number of pixels mouse moves before it's considered the start of a drag operation
	dragThreshold: 5,

	// betweenThreshold: Integer
	//		Set to a positive value to allow drag and drop "between" nodes.
	//
	//		If during DnD mouse is over a (target) node but less than betweenThreshold
	//		pixels from the bottom edge, dropping the the dragged node will make it
	//		the next sibling of the target node, rather than the child.
	//
	//		Similarly, if mouse is over a target node but less that betweenThreshold
	//		pixels from the top edge, dropping the dragged node will make it
	//		the target node's previous sibling rather than the target node's child.
	betweenThreshold: 0,

	// _nodePixelIndent: Integer
	//		Number of pixels to indent tree nodes (relative to parent node).
	//		Default is 19 but can be overridden by setting CSS class dijitTreeIndent
	//		and calling resize() or startup() on tree after it's in the DOM.
	_nodePixelIndent: 19,

	_publish: function(/*String*/ topicName, /*Object*/ message){
		// summary:
		//		Publish a message for this widget/topic
		topic.publish(this.id, lang.mixin({tree: this, event: topicName}, message || {}));	// publish
	},

	postMixInProperties: function(){
		this.tree = this;

		if(this.autoExpand){
			// There's little point in saving opened/closed state of nodes for a Tree
			// that initially opens all it's nodes.
			this.persist = false;
		}

		this._itemNodesMap = {};

		if(!this.cookieName && this.id){
			this.cookieName = this.id + "SaveStateCookie";
		}

		// Deferred that fires when all the children have loaded.
		this.expandChildrenDeferred  = new Deferred();

		// Deferred that fires when all pending operations complete.
		this.pendingCommandsDeferred = this.expandChildrenDeferred;

		this.inherited(arguments);
	},

	postCreate: function(){
		this._initState();

		// Catch events on TreeNodes
		var self = this;
		this.own(
			on(this.domNode, on.selector(".dijitTreeNode", touch.enter), function(evt){
				self._onNodeMouseEnter(registry.byNode(this), evt);
			}),
			on(this.domNode, on.selector(".dijitTreeNode", touch.leave), function(evt){
				self._onNodeMouseLeave(registry.byNode(this), evt);
			}),
			on(this.domNode, on.selector(".dijitTreeNode", "click"), function(evt){
				self._onClick(registry.byNode(this), evt);
			}),
			on(this.domNode, on.selector(".dijitTreeNode", "dblclick"), function(evt){
				self._onDblClick(registry.byNode(this), evt);
			}),
			on(this.domNode, on.selector(".dijitTreeNode", "keypress"), function(evt){
				self._onKeyPress(registry.byNode(this), evt);
			}),
			on(this.domNode, on.selector(".dijitTreeNode", "keydown"), function(evt){
				self._onKeyDown(registry.byNode(this), evt);
			}),
			on(this.domNode, on.selector(".dijitTreeRow", "focusin"), function(evt){
				self._onNodeFocus(registry.getEnclosingWidget(this), evt);
			})
		);

		// Create glue between store and Tree, if not specified directly by user
		if(!this.model){
			this._store2model();
		}

		// monitor changes to items
		this.connect(this.model, "onChange", "_onItemChange");
		this.connect(this.model, "onChildrenChange", "_onItemChildrenChange");
		this.connect(this.model, "onDelete", "_onItemDelete");

		this.inherited(arguments);

		if(this.dndController){
			if(lang.isString(this.dndController)){
				this.dndController = lang.getObject(this.dndController);
			}
			var params={};
			for(var i=0; i<this.dndParams.length;i++){
				if(this[this.dndParams[i]]){
					params[this.dndParams[i]] = this[this.dndParams[i]];
				}
			}
			this.dndController = new this.dndController(this, params);
		}

		this._load();

		// If no path was specified to the constructor, use path saved in cookie
		if(!this.params.path && !this.params.paths && this.persist){
			this.set("paths", this.dndController._getSavedPaths());
		}

		// onLoadDeferred should fire when all commands that are part of initialization have completed.
		// It will include all the set("paths", ...) commands that happen during initialization.
		this.onLoadDeferred = this.pendingCommandsDeferred;
				
		this.onLoadDeferred.then(lang.hitch(this, "onLoad"));
	},

	_store2model: function(){
		// summary:
		//		User specified a store&query rather than model, so create model from store/query
		this._v10Compat = true;
		kernel.deprecated("Tree: from version 2.0, should specify a model object rather than a store/query");

		var modelParams = {
			id: this.id + "_ForestStoreModel",
			store: this.store,
			query: this.query,
			childrenAttrs: this.childrenAttr
		};

		// Only override the model's mayHaveChildren() method if the user has specified an override
		if(this.params.mayHaveChildren){
			modelParams.mayHaveChildren = lang.hitch(this, "mayHaveChildren");
		}

		if(this.params.getItemChildren){
			modelParams.getChildren = lang.hitch(this, function(item, onComplete, onError){
				this.getItemChildren((this._v10Compat && item === this.model.root) ? null : item, onComplete, onError);
			});
		}
		this.model = new ForestStoreModel(modelParams);

		// For backwards compatibility, the visibility of the root node is controlled by
		// whether or not the user has specified a label
		this.showRoot = Boolean(this.label);
	},

	onLoad: function(){
		// summary:
		//		Called when tree finishes loading and expanding.
		// description:
		//		If persist == true the loading may encompass many levels of fetches
		//		from the data store, each asynchronous.   Waits for all to finish.
		// tags:
		//		callback
	},

	_load: function(){
		// summary:
		//		Initial load of the tree.
		//		Load root node (possibly hidden) and it's children.
		this.model.getRoot(
			lang.hitch(this, function(item){
				var rn = (this.rootNode = this.tree._createTreeNode({
					item: item,
					tree: this,
					isExpandable: true,
					label: this.label || this.getLabel(item),
					textDir: this.textDir,
					indent: this.showRoot ? 0 : -1
				}));
				
				if(!this.showRoot){
					rn.rowNode.style.display="none";
					// if root is not visible, move tree role to the invisible
					// root node's containerNode, see #12135
					this.domNode.setAttribute("role", "presentation");
					this.domNode.removeAttribute("aria-expanded");
					this.domNode.removeAttribute("aria-multiselectable");
					
					rn.labelNode.setAttribute("role", "presentation");
					rn.containerNode.setAttribute("role", "tree");
					rn.containerNode.setAttribute("aria-expanded","true");
					rn.containerNode.setAttribute("aria-multiselectable", !this.dndController.singular);
				}else{
				  this.domNode.setAttribute("aria-multiselectable", !this.dndController.singular);
				}
				
				this.domNode.appendChild(rn.domNode);
				var identity = this.model.getIdentity(item);
				if(this._itemNodesMap[identity]){
					this._itemNodesMap[identity].push(rn);
				}else{
					this._itemNodesMap[identity] = [rn];
				}

				rn._updateLayout();		// sets "dijitTreeIsRoot" CSS classname

				// Load top level children, and if persist==true, all nodes that were previously opened
				this._expandNode(rn).then(lang.hitch(this, function(){
					// Then, select the nodes that were selected last time, or
					// the ones specified by params.paths[].

					this.expandChildrenDeferred.resolve(true);
				}));
			}),
			lang.hitch(this, function(err){
				console.error(this, ": error loading root: ", err);
			})
		);
	},

	getNodesByItem: function(/*Item or id*/ item){
		// summary:
		//		Returns all tree nodes that refer to an item
		// returns:
		//		Array of tree nodes that refer to passed item

		if(!item){ return []; }
		var identity = lang.isString(item) ? item : this.model.getIdentity(item);
		// return a copy so widget don't get messed up by changes to returned array
		return [].concat(this._itemNodesMap[identity]);
	},

	_setSelectedItemAttr: function(/*Item or id*/ item){
		this.set('selectedItems', [item]);
	},

	_setSelectedItemsAttr: function(/*Items or ids*/ items){
		// summary:
		//		Select tree nodes related to passed items.
		//		WARNING: if model use multi-parented items or desired tree node isn't already loaded
		//		behavior is undefined. Use set('paths', ...) instead.
		var tree = this;
		return this.pendingCommandsDeferred = this.pendingCommandsDeferred.then( lang.hitch(this, function(){
			var identities = array.map(items, function(item){
				return (!item || lang.isString(item)) ? item : tree.model.getIdentity(item);
			});
			var nodes = [];
			array.forEach(identities, function(id){
				nodes = nodes.concat(tree._itemNodesMap[id] || []);
			});
			this.set('selectedNodes', nodes);
		}));
	},

	_setPathAttr: function(/*Item[]|String[]*/ path){
		// summary:
		//		Singular variant of _setPathsAttr
		if(path.length){
			return this.set("paths", [path]);
		}else{
			// Empty list is interpreted as "select nothing"
			return this.set("paths", []);
		}
	},

	_setPathsAttr: function(/*Item[][]|String[][]*/ paths){
		// summary:
		//		Select the tree nodes identified by passed paths.
		// paths:
		//		Array of arrays of items or item id's
		// returns:
		//		Deferred to indicate when the set is complete

		var tree = this;

		// Let any previous set("path", ...) commands complete before this one starts.
		return this.pendingCommandsDeferred = this.pendingCommandsDeferred.then(function(){
			// We may need to wait for some nodes to expand, so setting
			// each path will involve a Deferred. We bring those deferreds
			// together with a DeferredList.
			return new DeferredList(array.map(paths, function(path){
				var d = new Deferred();

				// normalize path to use identity
				path = array.map(path, function(item){
					return lang.isString(item) ? item : tree.model.getIdentity(item);
				});

				if(path.length){
					// Wait for the tree to load, if it hasn't already.
					selectPath(path, [tree.rootNode], d);
				}else{
					d.reject(new Tree.PathError("Empty path"));
				}
				return d;
			}));
		}).then(setNodes);

		function selectPath(path, nodes, def){
			// Traverse path; the next path component should be among "nodes".
			var nextPath = path.shift();
			var nextNode = array.filter(nodes, function(node){
				return node.getIdentity() == nextPath;
			})[0];
			if(!!nextNode){
				if(path.length){
					tree._expandNode(nextNode).then(function(){ selectPath(path, nextNode.getChildren(), def); });
				}else{
					// Successfully reached the end of this path
					def.resolve(nextNode);
				}
			}else{
				def.reject(new Tree.PathError("Could not expand path at " + nextPath));
			}
		}

		function setNodes(newNodes){
			// After all expansion is finished, set the selection to
			// the set of nodes successfully found.
			tree.set("selectedNodes", array.map(
				array.filter(newNodes,function(x){return x[0];}),
				function(x){return x[1];}));
		}
	},

	_setSelectedNodeAttr: function(node){
		this.set('selectedNodes', [node]);
	},
	_setSelectedNodesAttr: function(nodes){
		// summary:
		//		Marks the specified TreeNodes as selected.
		// nodes: TreeNode[]
		//		TreeNodes to mark.
		this.dndController.setSelection(nodes);
	},


	expandAll: function(){
		// summary:
		//		Expand all nodes in the tree
		// returns:
		//		Deferred that fires when all nodes have expanded

		var _this = this;

		function expand(node){
			var def = new dojo.Deferred();

			// Expand the node
			_this._expandNode(node).then(function(){
				// When node has expanded, call expand() recursively on each non-leaf child
				var childBranches = array.filter(node.getChildren() || [], function(node){
						return node.isExpandable;
					}),
					defs = array.map(childBranches, expand);

				// And when all those recursive calls finish, signal that I'm finished
				new dojo.DeferredList(defs).then(function(){
					def.resolve(true);
				});
			});

			return def;
		}

		return expand(this.rootNode);
	},

	collapseAll: function(){
		// summary:
		//		Collapse all nodes in the tree
		// returns:
		//		Deferred that fires when all nodes have collapsed

		var _this = this;

		function collapse(node){
			var def = new dojo.Deferred();
			def.label = "collapseAllDeferred";

			// Collapse children first
			var childBranches = array.filter(node.getChildren() || [], function(node){
					return node.isExpandable;
				}),
				defs = array.map(childBranches, collapse);

			// And when all those recursive calls finish, collapse myself, unless I'm the invisible root node,
			// in which case collapseAll() is finished
			new dojo.DeferredList(defs).then(function(){
				if(!node.isExpanded || (node == _this.rootNode && !_this.showRoot)){
					def.resolve(true);
				}else{
					_this._collapseNode(node).then(function(){
						// When node has collapsed, signal that call is finished
						def.resolve(true);
					});
				}
			});


			return def;
		}

		return collapse(this.rootNode);
	},

	////////////// Data store related functions //////////////////////
	// These just get passed to the model; they are here for back-compat

	mayHaveChildren: function(/*dojo/data/Item*/ /*===== item =====*/){
		// summary:
		//		Deprecated.   This should be specified on the model itself.
		//
		//		Overridable function to tell if an item has or may have children.
		//		Controls whether or not +/- expando icon is shown.
		//		(For efficiency reasons we may not want to check if an element actually
		//		has children until user clicks the expando node)
		// tags:
		//		deprecated
	},

	getItemChildren: function(/*===== parentItem, onComplete =====*/){
		// summary:
		//		Deprecated.   This should be specified on the model itself.
		//
		//		Overridable function that return array of child items of given parent item,
		//		or if parentItem==null then return top items in tree
		// tags:
		//		deprecated
	},

	///////////////////////////////////////////////////////
	// Functions for converting an item to a TreeNode
	getLabel: function(/*dojo/data/Item*/ item){
		// summary:
		//		Overridable function to get the label for a tree node (given the item)
		// tags:
		//		extension
		return this.model.getLabel(item);	// String
	},

	getIconClass: function(/*dojo/data/Item*/ item, /*Boolean*/ opened){
		// summary:
		//		Overridable function to return CSS class name to display icon
		// tags:
		//		extension
		return (!item || this.model.mayHaveChildren(item)) ? (opened ? "dijitFolderOpened" : "dijitFolderClosed") : "dijitLeaf"
	},

	getLabelClass: function(/*===== item, opened =====*/){
		// summary:
		//		Overridable function to return CSS class name to display label
		// item: dojo/data/Item
		// opened: Boolean
		// returns: String
		//		CSS class name
		// tags:
		//		extension
	},

	getRowClass: function(/*===== item, opened =====*/){
		// summary:
		//		Overridable function to return CSS class name to display row
		// item: dojo/data/Item
		// opened: Boolean
		// returns: String
		//		CSS class name
		// tags:
		//		extension
	},

	getIconStyle: function(/*===== item, opened =====*/){
		// summary:
		//		Overridable function to return CSS styles to display icon
		// item: dojo/data/Item
		// opened: Boolean
		// returns: Object
		//		Object suitable for input to dojo.style() like {backgroundImage: "url(...)"}
		// tags:
		//		extension
	},

	getLabelStyle: function(/*===== item, opened =====*/){
		// summary:
		//		Overridable function to return CSS styles to display label
		// item: dojo/data/Item
		// opened: Boolean
		// returns:
		//		Object suitable for input to dojo.style() like {color: "red", background: "green"}
		// tags:
		//		extension
	},

	getRowStyle: function(/*===== item, opened =====*/){
		// summary:
		//		Overridable function to return CSS styles to display row
		// item: dojo/data/Item
		// opened: Boolean
		// returns:
		//		Object suitable for input to dojo.style() like {background-color: "#bbb"}
		// tags:
		//		extension
	},

	getTooltip: function(/*dojo/data/Item*/ /*===== item =====*/){
		// summary:
		//		Overridable function to get the tooltip for a tree node (given the item)
		// tags:
		//		extension
		return "";	// String
	},

	/////////// Keyboard and Mouse handlers ////////////////////

	_onKeyPress: function(/*TreeNode*/ treeNode, /*Event*/ e){
		// summary:
		//		Handles keystrokes for printable keys, doing search navigation

		if(e.charCode <= 32){
			// Avoid duplicate events on firefox (this is an arrow key that will be handled by keydown handler)
			return;
		}

		if(!e.altKey && !e.ctrlKey && !e.shiftKey && !e.metaKey){
			var c = String.fromCharCode(e.charCode);
			this._onLetterKeyNav( { node: treeNode, key: c.toLowerCase() } );
			event.stop(e);
		}
	},

	_onKeyDown: function(/*TreeNode*/ treeNode, /*Event*/ e){
		// summary:
		//		Handles arrow, space, and enter keys

		var key = e.keyCode;

		var map = this._keyHandlerMap;
		if(!map){
			// Setup table mapping keys to events.
			// On WebKit based browsers, the combination ctrl-enter does not get passed through. To allow accessible
			// multi-select on those browsers, the space key is also used for selection.
			// Therefore, also allow space key for keyboard "click" operation.
			map = {};
			map[keys.ENTER] = map[keys.SPACE] = map[" "] = "_onEnterKey";
			map[this.isLeftToRight() ? keys.LEFT_ARROW : keys.RIGHT_ARROW] = "_onLeftArrow";
			map[this.isLeftToRight() ? keys.RIGHT_ARROW : keys.LEFT_ARROW] = "_onRightArrow";
			map[keys.UP_ARROW] = "_onUpArrow";
			map[keys.DOWN_ARROW] = "_onDownArrow";
			map[keys.HOME] = "_onHomeKey";
			map[keys.END] = "_onEndKey";
			this._keyHandlerMap = map;
		}

		if(this._keyHandlerMap[key]){
			// clear record of recent printables (being saved for multi-char letter navigation),
			// because "a", down-arrow, "b" shouldn't search for "ab"
			if(this._curSearch){
				this._curSearch.timer.remove();
				delete this._curSearch;
			}

			this[this._keyHandlerMap[key]]( { node: treeNode, item: treeNode.item, evt: e } );
			event.stop(e);
		}
	},

	_onEnterKey: function(/*Object*/ message){
		this._publish("execute", { item: message.item, node: message.node } );
		this.dndController.userSelect(message.node, connect.isCopyKey( message.evt ), message.evt.shiftKey);
		this.onClick(message.item, message.node, message.evt);
	},

	_onDownArrow: function(/*Object*/ message){
		// summary:
		//		down arrow pressed; get next visible node, set focus there
		var node = this._getNextNode(message.node);
		if(node && node.isTreeNode){
			this.focusNode(node);
		}
	},

	_onUpArrow: function(/*Object*/ message){
		// summary:
		//		Up arrow pressed; move to previous visible node

		var node = message.node;

		// if younger siblings
		var previousSibling = node.getPreviousSibling();
		if(previousSibling){
			node = previousSibling;
			// if the previous node is expanded, dive in deep
			while(node.isExpandable && node.isExpanded && node.hasChildren()){
				// move to the last child
				var children = node.getChildren();
				node = children[children.length-1];
			}
		}else{
			// if this is the first child, return the parent
			// unless the parent is the root of a tree with a hidden root
			var parent = node.getParent();
			if(!(!this.showRoot && parent === this.rootNode)){
				node = parent;
			}
		}

		if(node && node.isTreeNode){
			this.focusNode(node);
		}
	},

	_onRightArrow: function(/*Object*/ message){
		// summary:
		//		Right arrow pressed; go to child node
		var node = message.node;

		// if not expanded, expand, else move to 1st child
		if(node.isExpandable && !node.isExpanded){
			this._expandNode(node);
		}else if(node.hasChildren()){
			node = node.getChildren()[0];
			if(node && node.isTreeNode){
				this.focusNode(node);
			}
		}
	},

	_onLeftArrow: function(/*Object*/ message){
		// summary:
		//		Left arrow pressed.
		//		If not collapsed, collapse, else move to parent.

		var node = message.node;

		if(node.isExpandable && node.isExpanded){
			this._collapseNode(node);
		}else{
			var parent = node.getParent();
			if(parent && parent.isTreeNode && !(!this.showRoot && parent === this.rootNode)){
				this.focusNode(parent);
			}
		}
	},

	_onHomeKey: function(){
		// summary:
		//		Home key pressed; get first visible node, and set focus there
		var node = this._getRootOrFirstNode();
		if(node){
			this.focusNode(node);
		}
	},

	_onEndKey: function(){
		// summary:
		//		End key pressed; go to last visible node.

		var node = this.rootNode;
		while(node.isExpanded){
			var c = node.getChildren();
			node = c[c.length - 1];
		}

		if(node && node.isTreeNode){
			this.focusNode(node);
		}
	},

	// multiCharSearchDuration: Number
	//		If multiple characters are typed where each keystroke happens within
	//		multiCharSearchDuration of the previous keystroke,
	//		search for nodes matching all the keystrokes.
	//
	//		For example, typing "ab" will search for entries starting with
	//		"ab" unless the delay between "a" and "b" is greater than multiCharSearchDuration.
	multiCharSearchDuration: 250,

	_onLetterKeyNav: function(message){
		// summary:
		//		Called when user presses a prinatable key; search for node starting with recently typed letters.
		// message: Object
		//		Like { node: TreeNode, key: 'a' } where key is the key the user pressed.

		// Branch depending on whether this key starts a new search, or modifies an existing search
		var cs = this._curSearch;
		if(cs){
			// We are continuing a search.  Ex: user has pressed 'a', and now has pressed
			// 'b', so we want to search for nodes starting w/"ab".
			cs.pattern = cs.pattern + message.key;
			cs.timer.remove();
		}else{
			// We are starting a new search
			cs = this._curSearch = {
					pattern: message.key,
					startNode: message.node
			};
		}

		// set/reset timer to forget recent keystrokes
		cs.timer = this.defer(function(){
			delete this._curSearch;
		}, this.multiCharSearchDuration);

		// Navigate to TreeNode matching keystrokes [entered so far].
		var node = cs.startNode;
		do{
			node = this._getNextNode(node);
			//check for last node, jump to first node if necessary
			if(!node){
				node = this._getRootOrFirstNode();
			}
		}while(node !== cs.startNode && (node.label.toLowerCase().substr(0, cs.pattern.length) != cs.pattern));
		if(node && node.isTreeNode){
			// no need to set focus if back where we started
			if(node !== cs.startNode){
				this.focusNode(node);
			}
		}
	},

	isExpandoNode: function(node, widget){
		// summary:
		//		check whether a dom node is the expandoNode for a particular TreeNode widget
		return dom.isDescendant(node, widget.expandoNode);
	},
	_onClick: function(/*TreeNode*/ nodeWidget, /*Event*/ e){
		// summary:
		//		Translates click events into commands for the controller to process

		var domElement = e.target,
			isExpandoClick = this.isExpandoNode(domElement, nodeWidget);

		if( (this.openOnClick && nodeWidget.isExpandable) || isExpandoClick ){
			// expando node was clicked, or label of a folder node was clicked; open it
			if(nodeWidget.isExpandable){
				this._onExpandoClick({node:nodeWidget});
			}
		}else{
			this._publish("execute", { item: nodeWidget.item, node: nodeWidget, evt: e } );
			this.onClick(nodeWidget.item, nodeWidget, e);
			this.focusNode(nodeWidget);
		}
		event.stop(e);
	},
	_onDblClick: function(/*TreeNode*/ nodeWidget, /*Event*/ e){
		// summary:
		//		Translates double-click events into commands for the controller to process

		var domElement = e.target,
			isExpandoClick = (domElement == nodeWidget.expandoNode || domElement == nodeWidget.expandoNodeText);

		if( (this.openOnDblClick && nodeWidget.isExpandable) ||isExpandoClick ){
			// expando node was clicked, or label of a folder node was clicked; open it
			if(nodeWidget.isExpandable){
				this._onExpandoClick({node:nodeWidget});
			}
		}else{
			this._publish("execute", { item: nodeWidget.item, node: nodeWidget, evt: e } );
			this.onDblClick(nodeWidget.item, nodeWidget, e);
			this.focusNode(nodeWidget);
		}
		event.stop(e);
	},

	_onExpandoClick: function(/*Object*/ message){
		// summary:
		//		User clicked the +/- icon; expand or collapse my children.
		var node = message.node;

		// If we are collapsing, we might be hiding the currently focused node.
		// Also, clicking the expando node might have erased focus from the current node.
		// For simplicity's sake just focus on the node with the expando.
		this.focusNode(node);

		if(node.isExpanded){
			this._collapseNode(node);
		}else{
			this._expandNode(node);
		}
	},

	onClick: function(/*===== item, node, evt =====*/){
		// summary:
		//		Callback when a tree node is clicked
		// item: Object
		//		Object from the dojo/store corresponding to this TreeNode
		// node: TreeNode
		//		The TreeNode itself
		// evt: Event
		//		The event
		// tags:
		//		callback
	},
	onDblClick: function(/*===== item, node, evt =====*/){
		// summary:
		//		Callback when a tree node is double-clicked
		// item: Object
		//		Object from the dojo/store corresponding to this TreeNode
		// node: TreeNode
		//		The TreeNode itself
		// evt: Event
		//		The event
		// tags:
		//		callback
	},
	onOpen: function(/*===== item, node =====*/){
		// summary:
		//		Callback when a node is opened
		// item: dojo/data/Item
		// node: TreeNode
		// tags:
		//		callback
	},
	onClose: function(/*===== item, node =====*/){
		// summary:
		//		Callback when a node is closed
		// item: Object
		//		Object from the dojo/store corresponding to this TreeNode
		// node: TreeNode
		//		The TreeNode itself
		// tags:
		//		callback
	},

	_getNextNode: function(node){
		// summary:
		//		Get next visible node

		if(node.isExpandable && node.isExpanded && node.hasChildren()){
			// if this is an expanded node, get the first child
			return node.getChildren()[0];		// TreeNode
		}else{
			// find a parent node with a sibling
			while(node && node.isTreeNode){
				var returnNode = node.getNextSibling();
				if(returnNode){
					return returnNode;		// TreeNode
				}
				node = node.getParent();
			}
			return null;
		}
	},

	_getRootOrFirstNode: function(){
		// summary:
		//		Get first visible node
		return this.showRoot ? this.rootNode : this.rootNode.getChildren()[0];
	},

	_collapseNode: function(/*TreeNode*/ node){
		// summary:
		//		Called when the user has requested to collapse the node
		// returns:
		//		Deferred that fires when the node is closed

		if(node._expandNodeDeferred){
			delete node._expandNodeDeferred;
		}

		if(node.state == "LOADING"){
			// ignore clicks while we are in the process of loading data
			return;
		}

		if(node.isExpanded){
			var ret = node.collapse();

			this.onClose(node.item, node);
			this._state(node, false);

			this._startPaint(ret);	// after this finishes, need to reset widths of TreeNodes

			return ret;
		}
	},

	_expandNode: function(/*TreeNode*/ node){
		// summary:
		//		Called when the user has requested to expand the node
		// returns:
		//		Deferred that fires when the node is loaded and opened and (if persist=true) all it's descendants
		//		that were previously opened too

		// Signal that this call is complete
		var def = new Deferred();

		if(node._expandNodeDeferred){
			// there's already an expand in progress, or completed, so just return
			return node._expandNodeDeferred;	// dojo/_base/Deferred
		}

		var model = this.model,
			item = node.item,
			_this = this;

		// Load data if it's not already loaded
		if(!node._loadDeferred){
			// need to load all the children before expanding
			node.markProcessing();

			// Setup deferred to signal when the load and expand are finished.
			// Save that deferred in this._expandDeferred as a flag that operation is in progress.
			node._loadDeferred = new Deferred();

			// Get the children
			model.getChildren(
				item,
				function(items){
					node.unmarkProcessing();

					// Display the children and also start expanding any children that were previously expanded
					// (if this.persist == true).   The returned Deferred will fire when those expansions finish.
					node.setChildItems(items).then(function(){
						node._loadDeferred.resolve(items);
					});
				},
				function(err){
					console.error(_this, ": error loading " + node.label + " children: ", err);
					node._loadDeferred.reject(err);
				}
			);
		}

		// Expand the node after data has loaded
		node._loadDeferred.then(lang.hitch(this, function(){
			node.expand().then(function(){
				def.resolve(true);	// signal that this _expandNode() call is complete
			});

			// seems like these should be inside of then(), but left here for back-compat about
			// when this.isOpen flag gets set (ie, at the beginning of the animation)
			this.onOpen(node.item, node);
			this._state(node, true);
		}));

		this._startPaint(def);	// after this finishes, need to reset widths of TreeNodes

		return def;	// dojo/_base/Deferred
	},

	////////////////// Miscellaneous functions ////////////////

	focusNode: function(/* _tree.Node */ node){
		// summary:
		//		Focus on the specified node (which must be visible)
		// tags:
		//		protected

		// set focus so that the label will be voiced using screen readers
		focus.focus(node.labelNode);
	},

	_onNodeFocus: function(/*dijit/_WidgetBase*/ node){
		// summary:
		//		Called when a TreeNode gets focus, either by user clicking
		//		it, or programatically by arrow key handling code.
		// description:
		//		It marks that the current node is the selected one, and the previously
		//		selected node no longer is.

		if(node && node != this.lastFocused){
			if(this.lastFocused && !this.lastFocused._destroyed){
				// mark that the previously focsable node is no longer focusable
				this.lastFocused.setFocusable(false);
			}

			// mark that the new node is the currently selected one
			node.setFocusable(true);
			this.lastFocused = node;
		}
	},

	_onNodeMouseEnter: function(/*dijit/_WidgetBase*/ /*===== node =====*/){
		// summary:
		//		Called when mouse is over a node (onmouseenter event),
		//		this is monitored by the DND code
	},

	_onNodeMouseLeave: function(/*dijit/_WidgetBase*/ /*===== node =====*/){
		// summary:
		//		Called when mouse leaves a node (onmouseleave event),
		//		this is monitored by the DND code
	},

	//////////////// Events from the model //////////////////////////

	_onItemChange: function(/*Item*/ item){
		// summary:
		//		Processes notification of a change to an item's scalar values like label
		var model = this.model,
			identity = model.getIdentity(item),
			nodes = this._itemNodesMap[identity];

		if(nodes){
			var label = this.getLabel(item),
				tooltip = this.getTooltip(item);
			array.forEach(nodes, function(node){
				node.set({
					item: item,		// theoretically could be new JS Object representing same item
					label: label,
					tooltip: tooltip
				});
				node._updateItemClasses(item);
			});
		}
	},

	_onItemChildrenChange: function(/*dojo/data/Item*/ parent, /*dojo/data/Item[]*/ newChildrenList){
		// summary:
		//		Processes notification of a change to an item's children
		var model = this.model,
			identity = model.getIdentity(parent),
			parentNodes = this._itemNodesMap[identity];

		if(parentNodes){
			array.forEach(parentNodes,function(parentNode){
				parentNode.setChildItems(newChildrenList);
			});
		}
	},

	_onItemDelete: function(/*Item*/ item){
		// summary:
		//		Processes notification of a deletion of an item.
		//		Not called from new dojo.store interface but there's cleanup code in setChildItems() instead.

		var model = this.model,
			identity = model.getIdentity(item),
			nodes = this._itemNodesMap[identity];

		if(nodes){
			array.forEach(nodes,function(node){
				// Remove node from set of selected nodes (if it's selected)
				this.dndController.removeTreeNode(node);

				var parent = node.getParent();
				if(parent){
					// if node has not already been orphaned from a _onSetItem(parent, "children", ..) call...
					parent.removeChild(node);
				}
				node.destroyRecursive();
			}, this);
			delete this._itemNodesMap[identity];
		}
	},

	/////////////// Miscellaneous funcs

	_initState: function(){
		// summary:
		//		Load in which nodes should be opened automatically
		this._openedNodes = {};
		if(this.persist && this.cookieName){
			var oreo = cookie(this.cookieName);
			if(oreo){
				array.forEach(oreo.split(','), function(item){
					this._openedNodes[item] = true;
				}, this);
			}
		}
	},
	_state: function(node, expanded){
		// summary:
		//		Query or set expanded state for an node
		if(!this.persist){
			return false;
		}
		var path = array.map(node.getTreePath(), function(item){
				return this.model.getIdentity(item);
			}, this).join("/");
		if(arguments.length === 1){
			return this._openedNodes[path];
		}else{
			if(expanded){
				this._openedNodes[path] = true;
			}else{
				delete this._openedNodes[path];
			}
			if(this.persist && this.cookieName){
				var ary = [];
				for(var id in this._openedNodes){
					ary.push(id);
				}
				cookie(this.cookieName, ary.join(","), {expires:365});
			}
		}
	},

	destroy: function(){
		if(this._curSearch){
			this._curSearch.timer.remove();
			delete this._curSearch;
		}
		if(this.rootNode){
			this.rootNode.destroyRecursive();
		}
		if(this.dndController && !lang.isString(this.dndController)){
			this.dndController.destroy();
		}
		this.rootNode = null;
		this.inherited(arguments);
	},

	destroyRecursive: function(){
		// A tree is treated as a leaf, not as a node with children (like a grid),
		// but defining destroyRecursive for back-compat.
		this.destroy();
	},

	resize: function(changeSize){
		if(changeSize){
			domGeometry.setMarginBox(this.domNode, changeSize);
		}

		// The main JS sizing involved w/tree is the indentation, which is specified
		// in CSS and read in through this dummy indentDetector node (tree must be
		// visible and attached to the DOM to read this).
		// If the Tree is hidden domGeometry.position(this.tree.indentDetector).w will return 0, in which case just
		// keep the default value.
		this._nodePixelIndent = domGeometry.position(this.tree.indentDetector).w || this._nodePixelIndent;

		// resize() may be called before this.rootNode is created, so wait until it's available
		this.expandChildrenDeferred.then(lang.hitch(this, function(){
			// If tree has already loaded, then reset indent for all the nodes
			this.rootNode.set('indent', this.showRoot ? 0 : -1);

			// Also, adjust widths of all rows to match width of Tree
			this._adjustWidths();
		}));
	},

	_outstandingPaintOperations: 0,
	_startPaint: function(/*Promise|Boolean*/ p){
		// summary:
		//		Called at the start of an operation that will change what's displayed.
		// p:
		//		Promise that tells when the operation will complete.  Alternately, if it's just a Boolean, it signifies
		//		that the operation was synchronous, and already completed.

		this._outstandingPaintOperations++;
		if(this._adjustWidthsTimer){
			this._adjustWidthsTimer.remove();
			delete this._adjustWidthsTimer;
		}

		var oc = lang.hitch(this, function(){
			this._outstandingPaintOperations--;

			if(this._outstandingPaintOperations <= 0 && !this._adjustWidthsTimer && this._started){
				// Use defer() to avoid a width adjustment when another operation will immediately follow,
				// such as a sequence of opening a node, then it's children, then it's grandchildren, etc.
				this._adjustWidthsTimer = this.defer("_adjustWidths");
			}
		});
		when(p, oc, oc);
	},

	_adjustWidths: function(){
		// summary:
		//		Get width of widest TreeNode, or the width of the Tree itself, whichever is greater,
		//		and then set all TreeNodes to that width, so that selection/hover highlighting
		//		extends to the edge of the Tree (#13141)

		if(this._adjustWidthsTimer){
			this._adjustWidthsTimer.remove();
			delete this._adjustWidthsTimer;
		}

		var maxWidth = 0,
			nodes = [];
		function collect(/*TreeNode*/ parent){
			var node = parent.rowNode;
			node.style.width = "auto";		// erase setting from previous run
			maxWidth = Math.max(maxWidth, node.clientWidth);
			nodes.push(node);
			if(parent.isExpanded){
				array.forEach(parent.getChildren(), collect);
			}
		}
		collect(this.rootNode);
		maxWidth = Math.max(maxWidth, domGeometry.getContentBox(this.domNode).w);	// do after node.style.width="auto"
		array.forEach(nodes, function(node){
			node.style.width = maxWidth + "px";		// assumes no horizontal padding, border, or margin on rowNode
		});
	},

	_createTreeNode: function(/*Object*/ args){
		// summary:
		//		creates a TreeNode
		// description:
		//		Developers can override this method to define their own TreeNode class;
		//		However it will probably be removed in a future release in favor of a way
		//		of just specifying a widget for the label, rather than one that contains
		//		the children too.
		return new TreeNode(args);
	},

	_setTextDirAttr: function(textDir){
		if(textDir && this.textDir!= textDir){
			this._set("textDir",textDir);
			this.rootNode.set("textDir", textDir);
		}
	}
});

Tree.PathError = createError("TreePathError");
Tree._TreeNode = TreeNode;	// for monkey patching or creating subclasses of TreeNode

return Tree;
});

},
'cbtree/models/ForestStoreModel':function(){
//
// Copyright (c) 2010-2012, Peter Jekel
// All rights reserved.
//
//	The Checkbox Tree (cbtree), also known as the 'Dijit Tree with Multi State Checkboxes'
//	is released under to following three licenses:
//
//	1 - BSD 2-Clause							 (http://thejekels.com/cbtree/LICENSE)
//	2 - The "New" BSD License			 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L13)
//	3 - The Academic Free License	 (http://trac.dojotoolkit.org/browser/dojo/trunk/LICENSE#L43)
//
//	In case of doubt, the BSD 2-Clause license takes precedence.
//
define("cbtree/models/ForestStoreModel", [
	"dojo/_base/array",	 // array.indexOf array.some
	"dojo/_base/declare", // declare
	"dojo/_base/lang",		// lang.hitch
	"dojo/_base/window",	// win.global
	"./TreeStoreModel"
], function(array, declare, lang, win, TreeStoreModel){

		// module:
		//		cbtree/models/ForestStoreModel
		// summary:
		//		Interface between a CheckBox Tree and a dojo.data store that doesn't have a 
		//		root item, a.k.a. a store that has multiple "top level" items.

	return declare([TreeStoreModel], {
		// summary:
		//		Interface between a cbTree.Tree and a dojo.data store that doesn't have a root item,
		//		a.k.a. a store that has multiple "top level" items.
		//
		// description
		//		Use this class to wrap a dojo.data store, making all the items matching the specified query
		//		appear as children of a fabricated "root item".	If no query is specified then all the
		//		items returned by fetch() on the underlying store become children of the root item.
		//		This class allows cbTree.Tree to assume a single root item, even if the store doesn't have one.

		//=================================
		// Parameters to constructor

		// rootLabel: String
		//		Label of fabricated root item
		rootLabel: "ROOT",

		// rootId: String
		//		ID of fabricated root item
		rootId: "$root$",

		// End of parameters to constructor
		//=================================
		
		moduleName: "cbTree/models/ForestStoreModel",

		constructor: function (params) {
			// summary:
			//		Sets up variables, etc.
			// tags:
			//		private

			// Make dummy root item
			this.root = {
				store: this,
				root: true,
				id: this.rootId,
				label: this.rootLabel,
				children: params.rootChildren	// optional param
			};
			this.root[this.checkedAttr] = this.checkedState;
			this.hasFakeRoot = true;
		},

		// =======================================================================
		// Methods for traversing hierarchy

		getChildren: function(/*dojo.data.Item*/ parentItem, /*function(items)*/ callback, /*function*/ onError, 
													 /*(String|String[])?*/ childrenLists ){
			// summary:
			//		 Calls onComplete() with array of child items of given parent item, all loaded.
			if(parentItem === this.root){
				if(this.root.children){
					// already loaded, just return
					callback(this.root.children);
				}else{
					this.store.fetch( this._mixinFetch(
						{
							query: this.query,
							onComplete: lang.hitch(this, function(items){
								this.root.children = items;
								callback(items);
							}),
							onError: onError
						})
					);
				}
			}else{
				this.inherited(arguments);
			}
		},

		getParents: function (/*dojo.data.item*/ storeItem) {
			// summary:
			//		Get the parent(s) of a store item.	
			// storeItem:
			//		The dojo.data.item whose parent(s) will be returned.
			// tags:
			//		private
			var parents = [];

			if (storeItem) {
				if (storeItem !== this.root) {
					parents = this.store.getParents(storeItem);
					if (!parents.length) {
						return [this.root];
					}
				}
				return parents;
			}
		},

		mayHaveChildren: function(/*dojo.data.Item*/ item){
			// summary:
			//		Tells if an item has or may have children.	Implementing logic here
			//		avoids showing +/- expando icon for nodes that we know don't have children.
			//		(For efficiency reasons we may not want to check if an element actually
			//		has children until user clicks the expando node)
			// tags:
			//		extension
			return item === this.root || this.inherited(arguments);
		},

		// =======================================================================
		// Inspecting items

		fetchItemByIdentity: function(/* object */ keywordArgs){
			if(keywordArgs.identity == this.root.id){
				var scope = keywordArgs.scope?keywordArgs.scope:win.global;
				if(keywordArgs.onItem){
					keywordArgs.onItem.call(scope, this.root);
				}
			}else{
				this.inherited(arguments);
			}
		},

		getIcon: function(/* item */ item){
			if (this.iconAttr) {
				if (item !== this.root) {
					return this.store.getValue(item, this.iconAttr);
				}
				return this.root[this.iconAttr];
			}
		},

		getIdentity: function(/* item */ item){
			return (item === this.root) ? this.root.id : this.inherited(arguments);
		},

		getLabel: function(/* item */ item){
			return	(item === this.root) ? this.root.label : this.inherited(arguments);
		},

		isItem: function(/* anything */ something){
			return (something === this.root) ? true : this.inherited(arguments);
		},

		isChildOf: function (/*dojo.data.item*/ parent,/*dojo.data.item*/ item) {
			if (parent === this.root) {
				if (array.indexOf(this.root.children,item) !== -1) {
					return true;
				}
			} else {
				return this.inherited(arguments);
			}			
		},
		
		// =======================================================================
		// Write interface

		deleteItem: function(/*item*/ item) {
			// summary:
			//		Delete an item from the file store.
			// item:
			//		A valid store item
			// tag:
			//		Public
			if (item === this.root) {
				var children = this.root.children || [];
				var i;
				
				for(i=0;i<children.length; i++) {
					this.store.deleteItem( children[i] );
				}
			} else {
				return this.store.deleteItem(item);
			}
		},

		newItem: function(/* dojo.dnd.Item */ args, /*Item*/ parent, /*int?*/ insertIndex, /*String?*/ childrenAttr){
			// summary:
			//		Creates a new item.	 See dojo.data.api.Write for details on args.
			//		Used in drag & drop when item from external source dropped onto tree.
			if(parent === this.root){
				var newItem = this.store.newItem(args);
				this._updateCheckedParent(newItem);
				return newItem;
			}else{
				return this.inherited(arguments);
			}
		},

		pasteItem: function (/*dojo.data.item*/ childItem, /*dojo.data.item*/ oldParentItem, /*dojo.data.item*/ newParentItem, 
												 /*Boolean*/ bCopy, /*int?*/ insertIndex, /*String?*/ childrenAttr){
			// summary:
			//		Move or copy an item from one parent item to another.
			//		Used in drag & drop
			// tags:
			//		extension

			if (oldParentItem === this.root){
				if (!bCopy){
					this.store.detachFromRoot(childItem);
				}
			}
			if (newParentItem === this.root){
				this.store.attachToRoot(childItem);
			}
			this.inherited(arguments, [childItem,
				oldParentItem === this.root ? null : oldParentItem,
				newParentItem === this.root ? null : newParentItem,
				bCopy,
				insertIndex,
				childrenAttr
			]);
		},

		// =======================================================================
		// Events from data store

		onDeleteItem: function(/*Object*/ item){
			// summary:
			//		Handler for delete notifications from underlying store

			// check if this was a child of root, and if so send notification that root's children
			// have changed
			if(array.indexOf(this.root.children, item) != -1){
				this._requeryTop();
			}
			this.inherited(arguments);
		},

		onNewItem: function(/* dojo.data.item */ item, /* Object */ parentInfo){
			// summary:
			//		Handler for when new items appear in the store, either from a drop operation
			//		or some other way.	 Updates the tree view (if necessary).
			// description:
			//		If the new item is a child of an existing item,
			//		calls onChildrenChange() with the new list of children
			//		for that existing item.
			//
			// tags:
			//		extension
		
			// Call onChildrenChange() on parent (ie, existing) item with new list of children
			// In the common case, the new list of children is simply parentInfo.newValue or
			// [ parentInfo.newValue ], although if items in the store has multiple
			// child attributes (see `childrenAttr`), then it's a superset of parentInfo.newValue,
			// so call getChildren() to be sure to get right answer.
			if(parentInfo){
				this.getChildren(parentInfo.item, lang.hitch(this, function(children){
					this.onChildrenChange(parentInfo.item, children);
				}));
				this._updateCheckedParent(item, true);
			} else {
				this._requeryTop();
			}
		},

		onSetItem: function (/*dojo.data.item*/ storeItem, /*string*/ attribute, /*AnyType*/ oldValue, 
													/*AnyType*/ newValue){
			// summary:
			//		Updates the tree view according to changes in the data store.
			// description:
			//		Handles updates to a store item's children by calling onChildrenChange(), and
			//		other updates to a store item by calling onChange().
			// storeItem: 
			//		Store item
			// attribute: 
			//		attribute-name-string
			// oldValue:
			//		Old attribute value
			// newValue:
			//		New attribute value.
			// tags:
			//		extension

			if (this._queryAttrs.length && array.indexOf(this._queryAttrs, attribute) != -1) {
				this._requeryTop();
			}
			this.inherited(arguments);
		},

		onRootChange: function (/*dojo.data.item*/ storeItem, /*string*/ action) {
			// summary:
			//		Handler for any changes to the stores top level items.
			// description:
			//		Users can extend this method to modify a new element that's being
			//		added to the root of the tree, for example to make sure the new item
			//		matches the tree root query. Remember, even though the item is added
			//		as a top level item in the store it does not quarentee it will match
			//		your tree query unless your query is simply the store identifier.
			//		Therefore, in case of a store root detach event (evt.detach=true) we
			//		only require if the item is a known child of the tree root.
			// storeItem:
			//		The store item that was attached to, or detached from, the root.
			// evt:
			//		Object detailing the type of event { attach: boolean, detach: boolean }.
			// tag:
			//		callback, public

			// Only handle "attach" and "detach" here as store item creation or deletion
			// will be handled by onNewItem() and onDeleteItem()
			if (action === "attach" || action === "detach") {
				this._requeryTop();
			}
		},

		_requeryTop: function (){
			// summary:
			//		Reruns the query for the children of the root node, sending out an
			//		onChildrenChange notification if those children have changed.
			// tags:
			//		private

			var oldChildren = this.root.children || [];
			this.store.fetch( this._mixinFetch(
				{
					query: this.query,
					onComplete: lang.hitch(this, function (newChildren){
						this.root.children = newChildren;
						// If the list of children or the order of children has changed...
						if (oldChildren.length != newChildren.length ||
							array.some(oldChildren, function (item, idx){ 
									return newChildren[idx] != item;
								})) {
							this.onChildrenChange(this.root, newChildren);
							this._updateCheckedParent(newChildren[0]);
						}
					}) /* end hitch() */
				}) /* end _mixinFetch() */
			); /* end fetch() */
		}

	});

});

}}});
define("final/commsy", [], 1);
