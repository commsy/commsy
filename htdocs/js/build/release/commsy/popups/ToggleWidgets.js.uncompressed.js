define("commsy/popups/ToggleWidgets", [	"dojo/_base/declare",
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
			var widgetArray = [
			    "widgets/WidgetsNewEntries",
			    "widgets/WidgetsReleasedEntries",
			    "widgets/WidgetsRssTicker"/*,
			    "widgets/WidgetsExtensions"*/
			];
			
			this.loadWidgetsManual(widgetArray).then(
				Lang.hitch(this, function(results) {
					// place widgets
					dojo.forEach(results, Lang.hitch(this, function(result, index, arr) {
						if (index < 2) {
							result[1].handle.placeAt(Query("div.widgetAreaLeft", this.contentNode)[0]);
						} else {
							result[1].handle.placeAt(Query("div.widgetAreaRight", this.contentNode)[0]);
						}
					}));
				})
			);
		}
	});
});