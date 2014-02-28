define([	"dojo/_base/declare",
        	"commsy/WidgetPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, WidgetPopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(WidgetPopupHandler, {
		constructor: function(button_node, content_node, widgetManager) {
			// parent constructor is called automatically
			this.module = "stack";
			
			this.features = [ ];
			
			this.widgetManager = widgetManager;
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
			    "widgets/StackStack",
			    "widgets/StackNew",
			    "widgets/StackTagView",
			    "widgets/StackBuzzwordView"
			];
			
			this.loadWidgetsManual(widgetArray).then(
				Lang.hitch(this, function(results) {
					// old method
					
					// place widgets
					dojo.forEach(results, Lang.hitch(this, function(result, index, arr) {
						if (index === 0) {
							result.handle.placeAt(Query("div.widgetAreaLeft", this.contentNode)[0]);
						} else {
							result.handle.placeAt(Query("div.widgetAreaRight", this.contentNode)[0]);
						}
					}));
					
					if ( this.from_php.ownRoom.withPortfolio ) {
						// new method
						this.widgetManager.GetInstances([
						    [ "commsy/widgets/Stack/StackPortfolioMini", {}, true ]
						]).then(Lang.hitch(this, function(deferred) {
							var stackPortfolioMini = deferred[0].instance;
							
							stackPortfolioMini.placeAt(Query("div.widgetAreaRight div.CommSyWidget", this.contentNode)[0], "after");
						}));
					}
				})
			);
		}
	});
});