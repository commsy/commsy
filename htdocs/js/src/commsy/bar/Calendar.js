define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on",
        	"dojo/store/Observable",
        	"commsy/store/Json",
        	"dojox/calendar/Calendar"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, Query, On, Observable, Json, Calendar) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
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
			//var calendar = dijit.byId("calendar");
			
			var someData = [
			                {
			                  id: 0,
			                  summary: "Event 1",
			                  startTime: new Date(2012, 0, 1, 10, 0),
			                  endTime: new Date(2012, 0, 1, 12, 0)
			                }
			              ];
			
			var calendar = new Calendar({
				store: new Observable(new Json({
					fct:		"myCalendar"
				})),
				dateInterval: "day",
				style: "position: relative; height: 500px;"
			}, this.calendarNode);
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});