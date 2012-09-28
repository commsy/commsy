define("commsy/bar/CalendarAbo", [	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on",
        	"dojo/i18n!./nls/calendar"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, Query, On, CalendarTranslations) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidgetBorderless",
		widgetHandler:		null,
		
		itemId:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			this.itemId = this.from_php.ownRoom.id;
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
		},
		
		afterParse: function() {
			this.AJAXRequest("myCalendar", "getIcalAdress", {},
				Lang.hitch(function(response) {
					DomAttr.set(Query("a#dateAbo")[0], "href", "webcal://" + response.date);
					DomAttr.set(Query("a#dateExport")[0], "href", "http://" + response.date);
					
					/*
					DomAttr.set(Query("a#todoAbo")[0], "href", "webcal://" + response.todo);
					DomAttr.set(Query("a#todoExport")[0], "href", "http://" + response.todo);
					*/
				})
			);
		}
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});