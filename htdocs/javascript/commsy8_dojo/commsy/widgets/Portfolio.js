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
        	"dijit/layout/TabContainer",
        	"dijit/layout/ContentPane"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, xhr, Query, On, Topic) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyPortfolioWidget",
		widgetHandler:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.myPortfolioTabNode = null;
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.itemId = this.from_php.ownRoom.id;
			
			// subscribe
			Topic.subscribe("updatePortfolios", Lang.hitch(this, function(object) {
				// refresh portfolios
				this.loadPortfolios();
			}));
		},
		
		startup: function() {
		},
		
		afterParse: function() {
			// get handles
			this.myPortfolioTabNode = dijit.byId("myPortfolioTabNode");
			this.activatedPortfolioTabNode = dijit.byId("activatedPortfolioTabNode");
			
			// watch changes of child widgets in my portfolio tab
			this.myPortfolioTabNode.watch("selectedChildWidget", Lang.hitch(this, function(name, oldWidget, newWidget) {;
				this.onTabChanged(name, oldWidget, newWidget);
			}));
			
			this.loadPortfolios();
			
			// register create popup
			require(["commsy/popups/ClickPortfolioPopup"], Lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(this.createNewPortfolioNode, { iid: "NEW", module: "portfolioItem" });
			}));
		},
		
		loadPortfolios: function() {
			// reset tabs
			var children = this.myPortfolioTabNode.getChildren();
			dojo.forEach(children, Lang.hitch(this, function(child, index, arr) {
				if (child.baseClass === "CommSyPortfolioItemWidget") {
					this.myPortfolioTabNode.removeChild(child);
					child.destroyRecursive();
				}
			}));
			
			// load portfolios
			this.AJAXRequest("portfolio", "getPortfolios", {},
				Lang.hitch(this, function(response) {
					// add portfolios to tabs
					dojo.forEach(response.myPortfolios, Lang.hitch(this, function(portfolio, index, arr) {
						this.addPortfolio(portfolio, this.myPortfolioTabNode);
					}));
					dojo.forEach(response.activatedPortfolios, Lang.hitch(this, function(portfolio, index, arr) {
						
					}));
				})
			);
		},
		
		addPortfolio: function(portfolio, tab, select) {
			select = select || false;
			
			this.widgetHandler.loadWidget("widgets/PortfolioItem", { portfolioId: portfolio.id, title: portfolio.title }).then(
				Lang.hitch(this, function(widget) {
					var widget = widget.handle;
					
					tab.addChild(widget, 0);
					if (select) tab.selectChild(widget);
				})
			);
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onTabChanged: function(name, oldWidget, newWidget) {
			if (newWidget.baseClass === "CommSyPortfolioItemWidget") {
				newWidget.init();
			} else if(newWidget.id === "newPortfolioNode") {
				this.createNewPortfolioNode.click();
			}
		}
	});
});