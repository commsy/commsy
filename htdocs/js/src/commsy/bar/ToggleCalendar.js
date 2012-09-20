define([	"dojo/_base/declare",
        	"commsy/WidgetPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/parser",
        	"dojo/_base/lang"], function(declare, WidgetPopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Parser, Lang) {
	return declare(WidgetPopupHandler, {
		constructor: function(button_node, content_node) {
			// parent constructor is called automatically
			this.module = "calendar";
			
			this.features = [ ];
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_calendar_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_calendar_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// add some widgets hardcoded
			var widgetArray = [
			    "bar/Calendar",
			    "bar/CalendarConfig"
			];
			
			this.loadWidgetsManual(widgetArray).then(
				Lang.hitch(this, function(results) {
					// place widgets
					dojo.forEach(results, Lang.hitch(this, function(result, index, arr) {
						if (index === 0) {
							result[1].handle.placeAt(Query("div.widgetAreaLeft", this.contentNode)[0]);
						} else {
							result[1].handle.placeAt(Query("div.widgetAreaRight", this.contentNode)[0]);
						}
						dojo.parser.parse(this.contentNode);
						result[1].handle.afterParse();	
					}));
				})
			);
		}
	});
});