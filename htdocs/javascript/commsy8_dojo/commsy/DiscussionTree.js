define([	"dojo/_base/declare",
        	"dojo/dom-construct",
        	"dojo/io-query",
        	"commsy/tree",
        	"dojo/_base/lang",
        	"dijit/Dialog",
        	"cbtree/Tree",
        	"dijit/form/TextBox",
        	"dijit/form/Button",
        	"dojo/query",
        	"cbtree/models/ForestStoreModel",
        	"dojo/data/ItemFileWriteStore",
        	"dojo/dom-attr",
        	"cbtree/CheckBox",
        	"dojo/on",
        	"cbtree/models/StoreModel-API",
        	"dojo/NodeList-traverse"], function(declare, DomConstruct, ioQuery, TreeClass, Lang, Dialog, Tree, TextBox, Button, Query, ForestStoreModel, ItemFileWriteStore, DomAttr, CheckBox, On) {
	return declare(TreeClass, {
		textbox:	null,
		dialog:		null,
		button:		null,
		
		constructor: function(options) {
			// parent constructor is called automatically
		},
		
		
		
		/************************************************************************************
		 *** overwritten tree methods
		 ************************************************************************************/
		createTree: function() {
			return new Tree({
				autoExpand:			this.expanded,
				model:				this.model,
				showRoot:			false,
				checkBoxes:			false,
				onClick:			Lang.hitch(this, function(item, node, evt) {
				})
			});
		},
		
		createModel: function() {
			return new ForestStoreModel({
				store:			this.store,
				checkedAttr:	"match",
			});
		},
		
		/************************************************************************************
		 *** main setup routine
		 *
		 * this is completly overwritten, because the store data contains discussion details
		 * and not tags
		 ************************************************************************************/
		setupTree: function(node) {
			/*
			 * First of all, we need to load the store data via ajax. It will contain all
			 * the needed information for building the discussion tree
			 */
			this.AJAXRequest("threaded_discussion", "getTreeData", { discussionId: this.uri_object.iid }, Lang.hitch(this, function(results) {
				
				this.store = new ItemFileWriteStore({
					data: {
						identifier:		"item_id",
						label:			"subject",
						items:			results
					}
				});
				
				// setup html markup for labels
				dijit._TreeNode.prototype._setLabelAttr = {node: "labelNode", type: "innerHTML"};
				
				// create model
				this.model = this.createModel();
				
				// create tree
				this.tree = this.createTree();
				
				DomConstruct.empty(node);
				this.tree.placeAt(node);
				
				// register handler
				var expandAllNode = Query("a#discussionShortExpandAll")[0];
				var collapseAllNode = Query("a#discussionShortCollapseAll")[0];
				
				if (expandAllNode) {
					On(expandAllNode, "click", Lang.hitch(this, function(event) {
						this.onClickExpandAll();
					}));
				}
				
				if (collapseAllNode) {
					On(collapseAllNode, "click", Lang.hitch(this, function(event) {
						this.onClickCollapseAll();
					}));
				}
				
				// auto expand
				if (this.getNumItems(this.tree) > 10) {
					this.autoExpandToLevel(this.tree, 1);
				} else {
					this.autoExpandToLevel(this.tree, this.autoExpandLevel);
				}
			}));
		},
		
		/************************************************************************************
		 *** event handler
		 ************************************************************************************/
		onClickExpandAll: function() {
			this.autoExpandToLevel(this.tree, 0, true);
		},
		
		onClickCollapseAll: function() {
			this.autoExpandToLevel(this.tree, 0);
		}
		
		/************************************************************************************
		 *** Helper Functions	
		 ************************************************************************************/
	});
});