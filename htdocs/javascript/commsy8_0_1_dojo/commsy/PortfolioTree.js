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
				persist:			false,
				dndController:		DndSource,
				checkBoxes:			this.checkboxes,
				onClick:			Lang.hitch(this, function(item, node, evt) {
					this.popup.onTagSelected(item.item_id[0]);
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
				rootLabel:		""

				// event handling
			});
		},

		/************************************************************************************
		 *** main setup routine
		 ************************************************************************************/
		setupTree: function(node, callback) {
			callback = callback || function() {};

			// call parent method - overwrite arguments(add a callback function, when loading is done)
			this.inherited(arguments, [node, Lang.hitch(this, function() {
				// loading is done - now we can safely access this.tree

				callback(this);
			}), true]);
		}

		/************************************************************************************
		 *** event handler
		 ************************************************************************************/
		
		/************************************************************************************
		 *** Tree Actions
		 ************************************************************************************/
		
		/************************************************************************************
		 *** Helper Functions
		 ************************************************************************************/
	});
});