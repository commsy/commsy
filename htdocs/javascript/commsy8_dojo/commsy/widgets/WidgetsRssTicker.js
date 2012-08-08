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
        	"dojo/topic"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, xhr, Query, On, Topic) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,
		
		items:				[],
		
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
			this.itemId = this.from_php.ownRoom.id;
			
			Topic.subscribe("refreshRssList", Lang.hitch(this, function(object) {
				this.updateList();
			}));
			
			this.updateList();
			
			require(["commsy/popups/ClickRssPopup"], Lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(this.rssEditNode, { module: "rss", contextId: this.itemId });
			}));
		},
		
		updateList: function() {
			this.AJAXRequest("widget_rss_ticker", "getRssFeeds", { },
				Lang.hitch(this, function(response) {
					
					DomConstruct.empty(this.rssContentNode);
					
					dojo.forEach(response.feeds, Lang.hitch(this, function(feed, index, arr) {
						if (feed.display == "1") {
							var content = this.getFeedContent(feed.adress);
							
							var divNode = DomConstruct.create("div", {
							}, this.rssContentNode, "last");
							
								DomConstruct.create("h3", {
									innerHTML:		feed.title
								}, divNode, "last");
								
								DomConstruct.create("div", {
									innerHTML:		content
								}, divNode, "last");
						}						
					}));
				}));
		},
		
		getFeedContent: function(feedAddress) {
			this.AJAXRequest("widget_rss_ticker", "getFeed", { address: feedAddress },
				Lang.hitch(this, function(feed) {
					//return feed;
				})
			);
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});