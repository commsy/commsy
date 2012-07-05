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
        	"cbtree/models/StoreModel-API"], function(declare, domConstruct, ioQuery, BaseClass, lang, Tree, Query, ForestStoreModel, ItemFileWriteStore, CheckBox) {
	return declare(BaseClass, {
		followUrl:			true,
		autoExpandLevel:	3,
		checkboxes:			false,
		expanded:			false,
		item_id:			null,
		tree:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setupTree: function(node, callback) {
			callback = callback || function() {};
			
			// get results from ajax call
			this.AJAXRequest('tagtree', 'getTreeData', { item_id: this.item_id }, lang.hitch(this, function(results) {
				var store = new ItemFileWriteStore({
					data: {
						identifier:		"item_id",
						label:			"title",
						items:			results
					}
				});
				
				var model = new ForestStoreModel({
					store:			store,
					checkedAttr:	"match"
				});
				
				// create tree
				this.tree = new Tree({
					autoExpand:			this.expanded,
					model:				model,
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
				
				domConstruct.empty(node);
				this.tree.placeAt(node);
				
				callback();
				
				// auto expand
				//this.autoExpandToLevel(tree);
			}));
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
		}
	});
});