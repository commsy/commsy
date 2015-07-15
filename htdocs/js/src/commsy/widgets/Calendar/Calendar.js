define(
[
	"dojo/_base/declare",
	"dijit/_WidgetBase",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/Calendar.html",
	"dojo/i18n!./nls/calendar",
	"dojo/_base/lang",
	"dojo/on",
	"dojo/dom-class",
	"dojo/query",
	"commsy/store/Json",
	"dojo/topic",
	"dojox/calendar/Calendar",
	"dojo/date/stamp",
	"dojo/dom-construct"
], function
(
	declare,
	WidgetBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	Lang,
	On,
	DomClass,
	Query,
	Json,
	Topic,
	Calendar,
	Stamp,
	DomConstruct
) {
	return declare([WidgetBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"CommSyWidget",
		
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
			Topic.subscribe("updatePrivateCalendar", Lang.hitch(this, function(data) {
				declare.safeMixin(this.options, data.setConfig);
				declare.safeMixin(this.calendar.store.options, data.setConfig);
				this.calendar.set("store", this.calendar.store);
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
		createCalendar: function() {
			this.calendar = new Calendar({
				decodeDate:			function(s) {
					return Stamp.fromISOString(s);
				},
				encodeDate:			function(d) {
					return Stamp.toISOString(d);
				},
				selectionMode:		"none",
				moveEnabled:		false,
				dateInterval:		"day",
				style:				"position: relative; height: 500px;",
				columnViewProps:	{
					minHours:		0,
					maxHours:		24
				}
			});
			
			// set store
			var store = /*new Observable(*/new Json({
				options:		this.options,
				fct:			"myCalendar"
			})/*)*/;
			
			this.calendar.on("timeIntervalChange", Lang.hitch(this, function(event) {
				this.onTimeIntervalChange(event);
				
				this.calendar.set("store", store);
			}));
			
			this.calendar.on("itemClick", Lang.hitch(this, function(event) {
				this.itemClick(event);
			}));
			
			this.calendar.placeAt(this.calendarNode);
			
			return this.calendar;
		},
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		/**
		 * \brief	timeIntervalChange Event
		 * 
		 * Event dispatched when the displayed time interval has changed.
		 * 
		 * @param	event		oldStartTime, startTime, oldEndTime, endTime
		 */
		onTimeIntervalChange: function(event)
		{
			var startISOTime = Stamp.toISOString(event.startTime);
			var endISOTime = Stamp.toISOString(event.endTime);
			
			this.options.startISOTime = startISOTime;
			this.options.endISOTime = endISOTime;
		},
		
		/**
		 * \brief	itemClick Event
		 * 
		 * Event dispatched when item is clicked.
		 * 
		 * @param	event
		 */
		itemClick: function(event)
		{
			if (event.item.context == "public") {
			   window.open("/commsy.php?cid="+event.item.contextID+"&mod=date&fct=detail&iid="+event.item.id, "_self");
			} else {
   			var aNode = DomConstruct.create("a");
   			require(["commsy/popups/ClickDetailPopup"], Lang.hitch(this, function(ClickPopup) {
   				var handler = new ClickPopup();
   				handler.init(aNode, {iid:event.item.id, module:"date", contextId:event.item.contextID, data:event.item});
   				handler.open();
   			}));
			}
		}
	});
});