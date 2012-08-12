define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/_base/xhr",
        	"dojo/query",
        	"dojo/on",
        	"dojo/topic",
        	"dijit/layout/ContentPane"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, xhr, Query, On, Topic, ContentPane) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyPortfolioItemWidget",
		widgetHandler:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.isInitialized = false;
			this.description = "";
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
		},
		
		init: function() {
			if (this.isInitialized === false) {
				this.update();
				
				require(["commsy/popups/ClickPortfolioPopup"], Lang.hitch(this, function(ClickPopup) {
					var handler = new ClickPopup();
					handler.init(this.editPortfolioNode, { iid: this.portfolioId, module: "portfolioItem" });
				}));
				
				this.isInitialized = true;
			}
		},
		
		update: function() {
			this.AJAXRequest("portfolio", "getPortfolio", { portfolioId: this.portfolioId },
					Lang.hitch(this, function(response) {
						this.descriptionNode.innerHTML = response.description;
					})
				);
		},
		
		startup: function() {
			this.inherited(arguments);
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});