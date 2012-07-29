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
			/* we need to reinvoke popup handling */
			Query(".open_popup", this.widgetBodyNode).forEach(Lang.hitch(this, function(node, index, arr) {
				// get custom data object
				var customObject = this.getAttrAsObject(node, "data-custom");
				
				var module = customObject.module;
				
				require(["commsy/popups/Click" + this.ucFirst(module) + "Popup"], function(ClickPopup) {
					var handler = new ClickPopup();
					handler.init(node, customObject);
				});
			}));
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});