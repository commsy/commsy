define(
[
	"dojo/_base/declare",
	"dijit/_WidgetBase",
	"commsy/base",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/LimeSurveyUserWidget.html",
	"dojo/i18n!./nls/LimeSurveyUserWidget",
	"dojo/_base/lang",
	"commsy/request",
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
	lang,
	request,
	DomConstruct,
	On,
	DomClass,
	Query,
	Topic
) {
	return declare([BaseClass, WidgetBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"CommSyUserWidget",
		
		// attributes
		
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
			
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'limesurvey',
					action:	'getDisplayedSurveys'
				}
			}).then(
				lang.hitch(this, function(response) {
					// destroy the loading animation
					var loadingNode = Query("div#limesurveyLoading")[0];
					if ( loadingNode )
					{
						DomConstruct.destroy(loadingNode);
					}
					
					// add the response as html
					if ( response.data.surveys.length == 0 )
					{
						DomConstruct.create("span",
						{
							innerHTML:		PopupTranslations.noSurveys
						}, this.contentNode, "last");
					}
					else
					{
						var ulNode = DomConstruct.create("ul",
						{
						}, this.contentNode, "last");
						
						dojo.forEach(response.data.surveys, function(survey)
						{
							var liNode = DomConstruct.create("li",
							{
							}, ulNode, "last");
							
								DomConstruct.create("a",
								{
									href:		survey.url,
									target:		"_blank",
									innerHTML:	survey.title
								}, liNode, "last");
						});
					}
				})
			);
		}
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
	});
});