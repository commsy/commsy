define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/MyWidgetsWidget.html",
 	"dojo/i18n!./nls/MyWidgets",
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
		baseClass:			"toggleWidget myWidgetsWidget",
		
		toggle:				true,							///< Determs if this is a switchable popup
		
		// attributes
		title:				"Widgets",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
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
			// load child widgets silently
			var widgetManager = this.getWidgetManager();
			widgetManager.GetInstances(
			[
			 	[ "commsy/widgets/MyWidgets/MyWidgetsNewEntries", {}, true ],
			 	[ "commsy/widgets/MyWidgets/MyWidgetsReleasedEntries", {}, true ],
			 	[ "commsy/widgets/MyWidgets/MyWidgetsReleasedForMeEntries", {}, true ],
			 	[ "commsy/widgets/MyWidgets/MyWidgetsRssTicker", {}, true ],
			 	[ "commsy/widgets/MyWidgets/MyWidgetsRoomwideSearch", {}, true ] 
			]).then(Lang.hitch(this, function(deferred)
			{
				var myWidgetsNewEntries = deferred[0].instance;
				var myWidgetsReleasedEntries = deferred[1].instance;
				var myWidgetsReleasedForMeEntries = deferred[2].instance;
				var myWidgetsRssTicker = deferred[3].instance;
				var myWidgetsRoomwideSearch = deferred[4].instance;
				
				// place widgets
				myWidgetsNewEntries.placeAt(this.mainNode);
				myWidgetsReleasedEntries.placeAt(this.mainNode);
				myWidgetsReleasedForMeEntries.placeAt(this.mainNode);
				myWidgetsRoomwideSearch.placeAt(this.mainNode);
				myWidgetsRssTicker.placeAt(this.sidebarNode);
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
				var buttonNode = Query("a#tm_widgets")[0];
				
				if ( buttonNode )
				{
					DomClass.add(buttonNode, "tm_widgets_hover");
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
			var buttonNode = Query("a#tm_widgets")[0];
			
			if ( buttonNode )
			{
				DomClass.remove(buttonNode, "tm_widgets_hover");
			}
		}
	});
});