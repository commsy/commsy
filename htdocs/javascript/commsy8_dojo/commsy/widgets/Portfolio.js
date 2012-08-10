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
		},
		
		startup: function() {
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});