define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/LimeSurveyWidget.html",
 	"dojo/i18n!./nls/LimeSurveyWidget",
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
			 	[ "commsy/widgets/LimeSurvey/LimeSurveyOverview", {}, true ],
			 	[ "commsy/widgets/LimeSurvey/LimeSurveyMenu", { }, true ],
			 	[ "commsy/widgets/LimeSurvey/LimeSurveyExports", {}, true ]
			]).then(Lang.hitch(this, function(deferred)
			{
				var limeSurveyOverview = deferred[0].instance;
				var limeSurveyMenu = deferred[1].instance;
				var limeSurveyExports = deferred[2].instance;
				
				// place widgets
				limeSurveyOverview.placeAt(this.mainNode);
				limeSurveyExports.placeAt(this.mainNode);
				limeSurveyMenu.placeAt(this.sidebarNode);
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
				var buttonNode = Query("a#tm_limesurvey")[0];
				
				if ( buttonNode )
				{
					DomClass.add(buttonNode, "tm_limesurvey_hover");
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
			var buttonNode = Query("a#tm_limesurvey")[0];
			
			if ( buttonNode )
			{
				DomClass.remove(buttonNode, "tm_limesurvey_hover");
			}
		}
	});
});