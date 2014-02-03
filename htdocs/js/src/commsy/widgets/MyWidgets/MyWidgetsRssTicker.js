define(
[
	"dojo/_base/declare",
	"dijit/_WidgetBase",
	"commsy/base",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/MyWidgetsRssWidget.html",
	"dojo/i18n!./nls/MyWidgetsRssWidget",
	"dojo/_base/lang",
	"dojo/dom-construct",
	"dojo/on",
	"dojo/dom-class",
	"dojo/query",
	"dojo/topic"
], function
(
	declare,
	WidgetBase,
	BaseClass,
	TemplatedMixin,
	Template,
	PopupTranslations,
	Lang,
	DomConstruct,
	On,
	DomClass,
	Query,
	Topic
) {
	return declare([BaseClass, WidgetBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"CommSyWidgetBorderless",
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
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
			this.set("title", PopupTranslations.title);
			
			Topic.subscribe("refreshRssList", Lang.hitch(this, function(object)
			{
				this.updateList();
			}));
			
			this.updateList();
			
			require(["commsy/popups/ClickRssPopup"], Lang.hitch(this, function(ClickPopup)
			{
				var handler = new ClickPopup();
				handler.init(this.rssEditNode, { module: "rss", contextId: this.itemId });
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
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		updateList: function()
		{
			this.AJAXRequest("widget_rss_ticker", "getRssFeeds", { },
				Lang.hitch(this, function(response)
				{	
					DomConstruct.empty(this.rssContentNode);
					
					dojo.forEach(response.feeds, Lang.hitch(this, function(feed, index, arr)
					{
						if (feed.display == "1") {
							
							this.AJAXRequest("widget_rss_ticker", "getFeed", { address: feed.adress },
								Lang.hitch(this, function(feeds) {
									var content = "";
									
									dojo.forEach(feeds, Lang.hitch(this, function(feed, index, arr)
									{
										if (feed.title && feed.link) {
											content += "<a href='" + feed.link + "'>" + feed.title + "</a><br/>";
										}
									}));
									
									var divNode = DomConstruct.create("div", {
									}, this.rssContentNode, "last");
									
										DomConstruct.create("h3", {
											innerHTML:		feed.title
										}, divNode, "last");
										
										DomConstruct.create("div", {
											innerHTML:		content
										}, divNode, "last");
								})
							);
						}						
					}));
				}));
		}
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
	});
});