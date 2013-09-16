define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/PortfolioWidget.html",
 	"dojo/i18n!./nls/Portfolio",
 	"dojo/dom-construct",
 	"dojo/_base/lang",
 	"dijit/registry",
 	"dojo/query",
 	"dojo/dom-class",
 	"dojo/parser",
 	"dojo/on"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	DomConstruct,
	Lang,
	Registry,
	Query,
	DomClass,
	Parser,
	On
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"toggleWidget portfolioWidget",
		
		toggle:				true,							///< Determs if this is a switchable popup
		
		//mailSuccess:		true,
		//mail:				null,							///< mail data mixed in by calling class
		
		// attributes
		title:				"Portfolios",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		myPortfolioTabNode:	null,
		activatedPortfolioTabNode:	null,
		
		ignoreTabChanges: false,
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
			
			this.popupTranslations = PopupTranslations;
		},
		
		/**
		 * \brief	Processing after the DOM fragment is created
		 * 
		 * Called after the DOM fragment has been created, but not necessarily
		 * added to the document.  Do not include any operations which rely on
		 * node dimensions or placement.
		 */
		postCreate: function()
		{
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.itemId = this.from_php.ownRoom.id;
			
			// subscribe
			this.subscribe("updatePortfolios", Lang.hitch(this, function(object)
			{
				this.ignoreTabChanges = true;
				
				// TODO: object contains id of portfolio that has been updates, a total refresh is overpowered
				
				// refresh portfolios
				this.loadPortfolios().then(Lang.hitch(this, function()
				{
					// select edited portfolio, if not deleted
					this.selectMyPortfolio(object.itemId);
					
					this.ignoreTabChanges = false;
				}));
			}));
		},
		
		/**
		 * \brief 	Processing after the DOM fragment is added to the document
		 * 
		 * Called after a widget and its children have been created and added to the page,
		 * and all related widgets have finished their create() cycle, up through postCreate().
		 * This is useful for composite widgets that need to control or layout sub-widgets.
		 * Many layout widgets can use this as a wiring phase.
		 */
		startup: function()
		{
			this.inherited(arguments);
			
			// parse declarative markup
			Parser.parse(this.widgetNode).then(Lang.hitch(this, function(instances)
			{
				// get handles
				this.myPortfolioTabNode = Registry.byId("myPortfolioTabNode");
				this.activatedPortfolioTabNode = Registry.byId("activatedPortfolioTabNode");
				
				// watch changes of child widgets in my portfolio tab
				this.myPortfolioTabNode.watch("selectedChildWidget", Lang.hitch(this, function(name, oldWidget, newWidget) {
					this.onTabChanged(name, oldWidget, newWidget, true);
				}));
				
				// watch changes of child widgets in activated tab
				this.activatedPortfolioTabNode.watch("selectedChildWidget", Lang.hitch(this, function(name, oldWidget, newWidget) {
					this.onTabChanged(name, oldWidget, newWidget, false);
				}));
				
				// load existing portfolios
				this.loadPortfolios();
			}));
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		selectMyPortfolio: function(portfolioId)
		{
			// find the portfolio with the given id
			var children = this.myPortfolioTabNode.getChildren();
			
			var child = dojo.filter(children, function(item, index)
			{
				return item.portfolioId == portfolioId;
			});
			
			var selectWidget = null;
			
			if ( child[0] )
			{
				// select portfolio with given id
				selectWidget = child[0];
			}
			else
			{
				// select first one, if existing
				
				if ( children[0] )
				{
					selectWidget = children[0];
				}
			}
			
			if ( selectWidget !== null )
			{
				this.myPortfolioTabNode.selectChild(selectWidget);
				
				if ( selectWidget.isInitialized === false )
				{
					selectWidget.init(true);
				}
			}
		},
		
		loadPortfolios: function()
		{
			// reset tabs
			var children = this.myPortfolioTabNode.getChildren();
			dojo.forEach(children, Lang.hitch(this, function(child, index, arr) {
				this.myPortfolioTabNode.removeChild(child);
				child.destroyRecursive();
			}));
			
			children = this.activatedPortfolioTabNode.getChildren();
			dojo.forEach(children, Lang.hitch(this, function(child, index, arr) {
				this.activatedPortfolioTabNode.removeChild(child);
				child.destroyRecursive();
			}));
			
			// load portfolios
			return this.AJAXRequest("portfolio", "getPortfolios", {},
				Lang.hitch(this, function(response) {
					// before adding portfolios, make sure all previous instances are cleaned up
					var widgetManager = this.getWidgetManager();
					widgetManager.removeInstances("commsy/widgets/Portfolio/PortfolioItem");
					
					// add portfolios to tabs
					dojo.forEach(response.myPortfolios, Lang.hitch(this, function(portfolio, index, arr) {
						this.addPortfolio(portfolio, this.myPortfolioTabNode);
					}));
					dojo.forEach(response.activatedPortfolios, Lang.hitch(this, function(portfolio, index, arr) {
						this.addPortfolio(portfolio, this.activatedPortfolioTabNode);
					}));
				})
			);
		},
		
		addPortfolio: function(portfolio, tab, select)
		{
			select = select || false;
			
			var title = portfolio.title;
			if ( title.length > 18 )
			{
				title = title.substr(0, 18) + "...";
			}
			
			// load child widgets silently
			var widgetManager = this.getWidgetManager();
			widgetManager.GetInstance(	"commsy/widgets/Portfolio/PortfolioItem",
										{
											portfolioId: portfolio.id,
											title: title,
											titleFull: portfolio.title,
											iconClass: portfolio.external.length > 0 ? "dijitIconUsers" : ""
										},
										true).then(Lang.hitch(this, function(deferred)
			{
				var widget = deferred.instance;
				
				tab.addChild(widget, 0);
				if (select) tab.selectChild(widget);
				
				// tooltip
				if (portfolio.external.length > 0) {
					widget.set("tooltip", PopupTranslations.external + ": " + portfolio.external.join(", "));
				} else {
					widget.set("tooltip", title);
				}
			}));
		},
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		onTabChanged: function(name, oldWidget, newWidget, withEditing)
		{
			if ( this.ignoreTabChanges === true ) return;
			
			var showLoading = !newWidget.isInitialized;
			
			if ( showLoading === true )
			{
				this.setupLoading();
			}
			
			newWidget.init(withEditing);
			
			if ( showLoading === true )
			{
				this.destroyLoading();
			}
		},
		
		onClickNewPortfolio: function(event)
		{
			var widgetManager = this.getWidgetManager();
			widgetManager.GetInstance("commsy/widgets/Portfolio/PortfolioEditWidget", { portfolioId: null }).then(Lang.hitch(this, function(deferred)
			{
				var widgetInstance = deferred.instance;
				
				widgetInstance.Open();
			}));
		},
		
		onRefreshPortfolios: function(event)
		{
			this.loadPortfolios();
		},
		
		/**
		 * \brief	toggle event
		 * 
		 * Triggered on popup opening. Overwritten to specify some custom behavior.
		 * 
		 * @return	Deferred - resolves when opening is done
		 */
		OnOpenPopup: function()
		{
			// call parent
			return this.inherited(arguments).then(Lang.hitch(this, function(response)
			{
				// set class for widget button
				var buttonNode = Query("a#tm_portfolio")[0];
				
				if ( buttonNode )
				{
					DomClass.add(buttonNode, "tm_portfolio_hover");
				}
			}));
		},
		
		/**
		 * \brief	close event
		 * 
		 * Triggered on popup closing. Overwritten to specify some custom behavior.
		 */
		OnClosePopup: function()
		{
			this.inherited(arguments);
			
			// set class for widget button
			var buttonNode = Query("a#tm_portfolio")[0];
			
			if ( buttonNode )
			{
				DomClass.remove(buttonNode, "tm_portfolio_hover");
			}
		}
	});
});