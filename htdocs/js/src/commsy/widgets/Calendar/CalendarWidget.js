define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/CalendarWidget.html",
 	"dojo/i18n!./nls/calendarWidget",
 	"dojo/_base/lang",
	"dojo/dom-class",
	"dojo/query"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	Lang,
	DomClass,
	Query
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"toggleWidget",
		
		toggle:				true,							///< Determs if this is a switchable popup
		calendar:			null,
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
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
			this.set("title", this.popupTranslations.title);
			
			// load child widgets silently
			var widgetManager = this.getWidgetManager();
			widgetManager.GetInstances(
			[
			 	[ "commsy/widgets/Calendar/Calendar", {}, true ],
			 	[ "commsy/widgets/Calendar/CalendarConfig", { parentWidget: this }, true ],
			 	[ "commsy/widgets/Calendar/CalendarAbo", {}, true ]
			]).then(Lang.hitch(this, function(deferred)
			{
				var calendar = deferred[0].instance;
				var calendarConfig = deferred[1].instance;
				var calendarAbo = deferred[2].instance;
				
				// store calendar instance
				this.calendar = calendar;
				
				// place widgets
				calendar.placeAt(this.mainNode);
				calendarConfig.placeAt(this.sidebarNode);
				calendarAbo.placeAt(this.sidebarNode);
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
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		
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
				var buttonNode = Query("a#tm_mycalendar")[0];
				
				if ( buttonNode )
				{
					DomClass.add(buttonNode, "tm_mycalendar_hover");
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
			var buttonNode = Query("a#tm_mycalendar")[0];
			
			if ( buttonNode )
			{
				DomClass.remove(buttonNode, "tm_mycalendar_hover");
			}
		}
	});
});