define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, Query, On) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,
		items:				[],
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.AJAXRequest("widget_released_entries", "getListContent", { },
				Lang.hitch(this, function(response) {
					// save items
					this.items = response;
					
					// update lists
					this.updateLists();
				})
			);
		},
		
		updateLists: function() {
			dojo.forEach(this.items.releasedItems, Lang.hitch(this, function(item, index, arr) {
				// empty list
				DomConstruct.empty(this.releasedListNode);
				
				// fill list
				// create list entries
				var liNode = DomConstruct.create("li", {
				}, this.releasedListNode, "last");
				
					DomConstruct.create("img", {
						src:		this.from_php.template.tpl_path + "img/netnavigation/" + item.image.img,
						title:		item.image.text
					}, liNode, "last");
					
					var aNode = DomConstruct.create("a", {
						innerHTML:		item.title,
						href:			"#",
						className:		"open_popup"
					}, liNode, "last");
				
				DomAttr.set(aNode, "data-custom", "cid: " + item.contextId + ", iid: " + item.itemId + ", module: '" + item.module + "'");
				On(aNode, "click", Lang.hitch(this, function(event) {
					this.onClickListEntry(event.target);
				}));
			}));
			
			dojo.forEach(this.items.viewableItems, Lang.hitch(this, function(item, index, arr) {
				// empty list
				DomConstruct.empty(this.viewableListNode);
				
				// fill list
				// create list entries
				var liNode = DomConstruct.create("li", {
				}, this.viewableListNode, "last");
				
					DomConstruct.create("img", {
						src:		this.from_php.template.tpl_path + "img/netnavigation/" + item.image.img,
						title:		item.image.text
					}, liNode, "last");
					
					var aNode = DomConstruct.create("a", {
						innerHTML:		item.title,
						href:			"#",
						className:		"open_popup"
					}, liNode, "last");
				
				DomAttr.set(aNode, "data-custom", "cid: " + item.contextId + ", iid: " + item.itemId + ", module: '" + item.module + "'");
				On(aNode, "click", Lang.hitch(this, function(event) {
					this.onClickListEntry(event.target);
				}));
			}));
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickListEntry: function(aNode) {
			var customObject = this.getAttrAsObject(aNode, "data-custom");
					
			this.reload(customObject.iid, customObject.module, customObject.cid);
		}
	});
});