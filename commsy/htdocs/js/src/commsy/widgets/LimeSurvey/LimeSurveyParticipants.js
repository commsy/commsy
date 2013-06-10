define(
[
	"dojo/_base/declare",
	"commsy/widgets/PopupBase",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/LimeSurveyParticipants.html",
	"dojo/i18n!./nls/LimeSurveyParticipants",
	"dojo/_base/lang",
	"dojo/dom-construct",
	"dojo/dom-attr",
	"dojo/on",
	"dojo/topic",
	"dojo/dom-class",
	"dojo/query",
	"dojo/parser",
	"dojox/form/Manager",
	"dijit/registry",
	"dijit/form/ValidationTextBox",
	"dojox/validate/web"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	Lang,
	DomConstruct,
	DomAttr,
	On,
	Topic,
	DomClass,
	Query,
	Parser,
	Manager,
	Registry
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"LimeSurveyParticipantsWidget",
		
		canOverlay:			true,							///< Determs if popup can overlay other popus
		
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
			this.set("title", PopupTranslations.title);
			
			On(this.formNode, "submit", Lang.hitch(this, this.onSubmit));
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
			
			Parser.parse(this.widgetNode);
			
			this.AJAXRequest(	"limesurvey",
								"getGroups",
								{},
								Lang.hitch(this, function(response)
			{
				// destroy the loading animation
				DomConstruct.destroy(this.loadingGroupsNode);
				
				// if response is not empty, nable the submit button
				if ( response.groups.length > 0 )
				{
					DomAttr.remove(this.submitNode, "disabled");
				}
				
				// go through all groups and add them
				dojo.forEach(response.groups, Lang.hitch(this, function(group)
				{
					DomConstruct.create("option",
					{
						value:			group.id,
						innerHTML:		group.title
					}, this.groupSelectNode, "last");
				}));
									
				// make the select field visible
				DomClass.remove(this.groupSelectNode, "hidden");
			}));
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
		onSubmit: function(event)
		{
			event.preventDefault();
			
			var formManager = Registry.byId("limesurveyParticipantsForm");
			
			if ( formManager.validate() )
			{/*
				this.setupLoading();
				var formValues = formManager.gatherFormValues();
				
				this.AJAXRequest(	"limesurvey",
									"createSurvey",
									{
										templateId:		formValues.template,
										surveyTitle:	formValues.title
									},
									Lang.hitch(this, function(response)
				{
					// update the survey list in the main widget
					Topic.publish("updateSurveys", { });
					
					// remove loading indicator and close this popup
					this.destroyLoading();
					this.Close();
				}));*/
			}
		}
	});
});