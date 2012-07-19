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
        	"dojo/dom-attr",
        	"cbtree/CheckBox",
        	"dojo/on",
        	"dijit/tree/dndSource",
        	"cbtree/models/StoreModel-API",
        	"dojo/NodeList-traverse"], function(declare, DomConstruct, ioQuery, TreeClass, Lang, Dialog, Tree, TextBox, Button, Query, ForestStoreModel, DomAttr, CheckBox, On, DndSource) {
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
				showRoot:			true,
				dndController:		DndSource,
				checkBoxes:			this.checkboxes,
				onClick:			Lang.hitch(this, function(item, node, evt) {
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
		
		createModel: function() {
			return new ForestStoreModel({
				store:			this.store,
				checkedAttr:	"match",
				
				// event handling
				/*
				onChildrenChange:	Lang.hitch(this, function(parent, newChildrenList) {
					this.onChildrenChange(parent, newChildrenList);
				}),
				
				onDelete:	Lang.hitch(this, function(item) {
					this.onDelete(item);
				})
				*/
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
		
		/************************************************************************************
		 *** event handler
		 ************************************************************************************/
		onStoreNew: function(newItem, parentInfo) {
			console.log("new");
		},
		
		onStoreSet: function(item, attribute, oldValue, newValue) {
			// we are only interested in changes of child relationship
			if(attribute === "children") {
				// get tag id of item
				var parentId = this.model.getItemAttr(item, "item_id");
				
				// get array of children ids
				var childrenIds = dojo.map(newValue, Lang.hitch(this, function(child, index, arr) {
					return this.model.getItemAttr(child, "item_id");
				}));
				
				// send ajax request
				this.AJAXRequest("tags", "updateTreeStructure", { parentId: parentId, children: childrenIds },
					Lang.hitch(this, function(response) {
						
					})
				);
			}
		},
		
		/************************************************************************************
		 *** Tree Actions
		 ************************************************************************************/
		createNewTreeEntry: function(parentId) {
			var model = this.tree.model;
			
			// get parent item
			var parentItem = model.fetchItem({ item_id: parentId });
			
			// create a new dialog with an input field for naming
			this.createNewInputDialog(Lang.hitch(this, function(tagName) {
				// create tag and update tree
				this.AJAXRequest("tags", "createNewTag", { tagName: tagName, parentId: parentId }, Lang.hitch(this, function(response) {
					model.newItem( { title: tagName, item_id: response.tagId, children: [] }, parentItem);
					this.addCreateAndRenameToAllLabels();
				}));
			}));
		},
		
		renameTagEntry: function(itemId) {
			var model = this.tree.model;
			
			// get item
			var item = model.fetchItem({ item_id: itemId });
			
			// create a new dialog with an input field for renaming
			this.createNewInputDialog(Lang.hitch(this, function(newTagName) {
				// update tag and update tree
				this.AJAXRequest("tags", "renameTag", { newTagName: newTagName, tagId: itemId }, Lang.hitch(this, function(response) {
					model.setItemAttr(item, "title", newTagName);
				}));
			}), model.getItemAttr(item, "title"));
		},
		
		/************************************************************************************
		 *** Helper Functions	
		 ************************************************************************************/
		createNewInputDialog: function(submitCallback, value) {
			value = value || "";
			
			// create the text box
			this.textbox = new dijit.form.TextBox({
				value:		value
			});
			
			// create the button
			this.button = new dijit.form.Button({
				label:		"Ok",
				onClick:	Lang.hitch(this, function(event) {
					// return the textbox value through callback
					submitCallback(this.textbox.get("value"));
					
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			// create and show the dialog
			this.dialog = new dijit.Dialog({
				title:		"Name"
			});
			dojo.place(this.textbox.domNode, this.dialog.containerNode, "last");
			dojo.place(this.button.domNode, this.dialog.containerNode, "last");
			
			this.dialog.show();
		},
		
		addCreateAndRenameToAllLabels: function() {
			// create a link after all labels and connect event handling
			dojo.forEach(Query("div#popup_tabcontent span.dijitTreeLabel"), Lang.hitch(this, function(spanNode, index, arr) {
				
				// check if link wasn't already created
				var nodeCreatorNode = Query("a.nodeCreator", spanNode.parentNode)[0];
				if(!nodeCreatorNode) {
					var createLinkNode = DomConstruct.create("a", {
						className:		"nodeCreator",
						href:			"#",
						innerHTML:		"+"
					}, spanNode, "after");
					
					var renameLinkNode = DomConstruct.create("a", {
						href:			"#",
						innerHTML:		"rename"
					}, createLinkNode, "after");
					
					// get widget id from appropriated dijitTreeNode
					var treeNode = new dojo.NodeList(createLinkNode).parents("div.dijitTreeNode")[0];
					if(treeNode) {
						var widgetId = DomAttr.get(treeNode, "widgetid");
						
						// extract item id
						var widget = dijit.byId(widgetId);
						var itemId = parseInt(this.tree.model.getItemAttr(widget.item, "item_id"));
						
						On(createLinkNode, "click", Lang.hitch(this, function(event) {
							this.createNewTreeEntry(itemId);
						}));
						
						On(renameLinkNode, "click", Lang.hitch(this, function(event) {
							this.renameTagEntry(itemId);
						}));
					}
				}
			}));
			
			/*
			var model = this.tree.model;
			
			model.getRoot(Lang.hitch(this, function(rootItem) {
				this.iterateCallback(rootItem, function(item) {
					// do not process factored root item
					if(model.getItemAttr(item, "root") !== true) {
						// get current label
						
						console.log(item);
						var label = model.getItemAttr(item, "label");
						
						// update label and set
						var createLinkNode = DomConstruct.create("a", {
							href:			"#",
							innerHTML:		"+"
						});
						
						console.log(createLinkNode);
						
						label += createLinkNode;
						model.setItemAttr(item, "label", label);
					}
				});
			}));
			*/
		}
	});
});