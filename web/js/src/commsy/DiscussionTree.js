define([	"dojo/_base/declare",
        	"dojo/dom-construct",
        	"dojo/io-query",
        	"commsy/tree",
        	"dojo/_base/lang",
        	"commsy/request",
        	"dijit/Dialog",
        	"cbtree/Tree",
        	"dijit/form/TextBox",
        	"dijit/form/Button",
        	"dojo/query",
        	"dojo/dom-class",
        	"cbtree/models/ForestStoreModel",
        	"dojo/data/ItemFileWriteStore",
        	"dojo/dom-attr",
        	"cbtree/CheckBox",
        	"dojo/on",
        	"cbtree/models/StoreModel-API",
        	"dojo/NodeList-traverse"], function(declare, DomConstruct, ioQuery, TreeClass, lang, request, Dialog, Tree, TextBox, Button, Query, DomClass, ForestStoreModel, ItemFileWriteStore, DomAttr, CheckBox, On) {
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
				onClick:			lang.hitch(this, function(item, node, evt) {
				})
			});
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
				persist:			false,
				onClick:			lang.hitch(this, function(item, node, evt) {
					// follow item url
					if(this.followUrl) {
						var anchorNode = Query("a[name='article" + item.item_id + "']")[0];
						if (anchorNode) {
							this.scrollToNodeAnimated(anchorNode);
						}
						//location.href = this.replaceOrSetAnchor("#article" + item.item_id);
					}
				})
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
			request.ajax({
				query: {
					cid:		this.uri_object.cid,
					mod:		'ajax',
					fct:		'threaded_discussion',
					action:		'getTreeData'
				},
				data: {
					discussionId: (this.item_id || this.uri_object.iid)
				}
			}).then(lang.hitch(this, function(response) {
				this.store = new ItemFileWriteStore({
					data: {
						identifier:		"item_id",
						label:			"subject",
						items:			response.data
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
					On(expandAllNode, "click", lang.hitch(this, function(event) {
						this.onClickExpandAll();
					}));
				}
				
				if (collapseAllNode) {
					On(collapseAllNode, "click", lang.hitch(this, function(event) {
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
			
			// hide expand and show collapse
			DomClass.add(Query("a#discussionShortExpandAll")[0], "hidden");
			DomClass.remove(Query("a#discussionShortCollapseAll")[0], "hidden");
		},
		
		onClickCollapseAll: function() {
			this.autoExpandToLevel(this.tree, 0);
			
			// hide collapse and show expand
			DomClass.add(Query("a#discussionShortCollapseAll")[0], "hidden");
			DomClass.remove(Query("a#discussionShortExpandAll")[0], "hidden");
		}
		
		/************************************************************************************
		 *** Helper Functions	
		 ************************************************************************************/
	});
});