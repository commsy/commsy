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