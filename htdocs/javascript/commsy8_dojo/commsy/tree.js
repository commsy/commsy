define([	"dojo/_base/declare",
        	"dojo/dom-construct",
        	"dojo/io-query",
        	"commsy/base",
        	"dojo/_base/lang",
        	"cbtree/Tree",
        	"dojo/query",
        	"cbtree/models/ForestStoreModel",
        	"dojo/data/ItemFileWriteStore",
        	"cbtree/CheckBox",
        	"cbtree/models/StoreModel-API"], function(declare, domConstruct, ioQuery, BaseClass, lang, Tree, Query, ForestStoreModel, ItemFileWriteStore, CheckBox, DndSource) {
	return declare(BaseClass, {
		followUrl:			true,
		autoExpandLevel:	3,
		checkboxes:			false,
		expanded:			false,
		item_id:			null,
		tree:				null,
		store:				null,
		model:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setupTree: function(node, callback) {
			callback = callback || function() {};
			
			// get results from ajax call
			this.AJAXRequest('tagtree', 'getTreeData', { item_id: this.item_id }, lang.hitch(this, function(results) {
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
				
				callback();
				
				// auto expand
				//this.autoExpandToLevel(tree);
			}));
		},
		
		createModel: function() {
			return new ForestStoreModel({
				store:			this.store,
				checkedAttr:	"match"
			});
		},
		
		createTree: function() {
			return new Tree({
				autoExpand:			this.expanded,
				model:				this.model,
				showRoot:			false,
				checkBoxes:			this.checkboxes,
				onClick:			lang.hitch(this, function(item, node, evt) {
					// follow item url
					if(this.followUrl) {
						location.href = 'commsy.php?' + ioQuery.objectToQuery(this.replaceOrSetURIParam('seltag', item.item_id));
					} else {
						// if click doesn't come from checkbox
						if(evt.target.nodeName !== "INPUT") {
							if(model.getChecked(item) === true) {
								model.setChecked(item, false);
							} else {
								model.setChecked(item, true);
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
		
		autoExpandToLevel: function(tree) {
			this.walkHelper(tree, tree.rootNode.item, 0);
		},
		
		walkHelper: function(tree, item, level) {
			if(item.item_id) {
				var itemId = item.item_id[0];
				
				var node = tree.getNodesByItem(itemId);
				
				console.log(node);
			}
			//tree._expandNode(tree.rootNode);
			
			/*if(level <= this.autoExpandLevel && node.isExpandable) {
				
				console.log(node);	
				tree._expandNode(node);
			}
			*/
			for(var id in item.children) {
				this.walkHelper(tree, item.children[id], level+1);
			}
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
		}
	});
});