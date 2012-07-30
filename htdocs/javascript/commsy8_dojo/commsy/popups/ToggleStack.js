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
			this.module = "stack";
			
			this.features = [ ];
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_stack_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_stack_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// add some widgets hardcoded
			var widgetArray = [
			    "widgets/StackNew",
			    "widgets/StackStack",
			    "widgets/StackBuzzwordView",
			    "widgets/StackTagView"
			];
			
			this.loadWidgetsManual(widgetArray).then(
				Lang.hitch(this, function(results) {
					// place widgets
					dojo.forEach(results, Lang.hitch(this, function(result, index, arr) {
						result[1].handle.placeAt(Query("div.widgetArea", this.contentNode)[0]);
						
						if (index === 3) {
							// this will insert a float clear
							DomConstruct.create("div", {
								className:		"clear"
							}, Query("div.widgetArea", this.contentNode)[0], "last");
						}
					}));
				})
			);
			
			
			//this.widgetArray.push("widget_new");
			
			// call parent
			//this.inherited(arguments);
		}
	});
});