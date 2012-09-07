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
		
		itemId:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			this.itemId = this.from_php.ownRoom.id;
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			require(["commsy/popups/ClickMaterialPopup"], Lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(this.createMaterialNode, { iid: "NEW", module: "material", contextId: this.itemId });
			}));
			
			require(["commsy/popups/ClickDatePopup"], Lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(this.createDateNode, { iid: "NEW", module: "date", contextId: this.itemId });
			}));
			
			require(["commsy/popups/ClickDiscussionPopup"], Lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(this.createDiscussionNode, { iid: "NEW", module: "discussion", contextId: this.itemId });
			}));
			
			require(["commsy/popups/ClickTodoPopup"], Lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(this.createTodoNode, { iid: "NEW", module: "todo", contextId: this.itemId });
			}));
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});