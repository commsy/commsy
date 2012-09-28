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
        	"dojo/topic",
        	"dojox/calendar/Calendar",
        	"dojo/date/stamp"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, Query, On, Observable, Json, Topic, Calendar, Stamp) {
	
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
			var calendar = this.createCalendar();
			
			Topic.subscribe("updatePrivateCalendar", Lang.hitch(this, function(data) {
				calendar.set("store", calendar.store);
			}));
		},
		
		afterParse: function() {
			
		},
		
		createCalendar: function() {
			var calendar = new Calendar({
				decodeDate:			function(s) {
					return Stamp.fromISOString(s);
				},
				encodeDate:			function(d) {
					return Stamp.toISOString(d)
				},
				store:				/*new Observable(*/new Json({
					fct:			"myCalendar"
				})/*)*/,
				selectionMode:		"none",
				moveEnabled:		false,
				dateInterval:		"day",
				style:				"position: relative; height: 500px;",
				columnViewProps:	{
					minHours:		0,
					maxHours:		24
				}
			}, this.calendarNode);
			
			calendar.on("itemClick", Lang.hitch(function(event) {
				
			}));
			
			return calendar;
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});