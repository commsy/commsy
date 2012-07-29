define([	"dojo/_base/declare",
        	"commsy/WidgetPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, WidgetPopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(WidgetPopupHandler, {
		constructor: function(button_node, content_node) {
			// parent constructor is called automatically
			this.module = "widgets";
			
			this.features = [ ];
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_widgets_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_widgets_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// add some widgets hardcoded
			//this.widgetArray.push("widget_new");
			
			// call parent
			this.inherited(arguments);
			
			/*
			// setup clipboard functions
			require(["commsy/Clipboard"], Lang.hitch(this, function(Clipboard) {
				var clipboard = new Clipboard();
				clipboard.init(this.cid, this.from_php.template.tpl_path);
			}));
			*/
		}
	});
});