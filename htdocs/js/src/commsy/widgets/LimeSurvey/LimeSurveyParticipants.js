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
	"commsy/request",
	"dojo/topic",
	"dojo/dom-class",
	"dojo/query",
	"dojo/parser",
	"dojox/form/Manager",
	"dojo/fx",
	"dijit/registry",
	"dijit/form/ValidationTextBox",
	"commsy/ValidationTextArea",
	"dojox/validate/web",
	"dojo/NodeList-traverse"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	lang,
	DomConstruct,
	DomAttr,
	On,
	request,
	Topic,
	DomClass,
	Query,
	Parser,
	Manager,
	FX,
	Registry
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"LimeSurveyParticipantsWidget",
		
		canOverlay:			true,							///< Determs if popup can overlay other popups
		
		surveyId:			null,							///< Given by LimeSurveyOverview.js
		additionalIndex:	1,
		
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
			
			On(this.formNode, "submit", lang.hitch(this, this.onSubmit));
			On(this.withTokensCheckboxNode, "change", lang.hitch(this, this.onChangeWithTokens));
			On(this.addAdditionalNode, "click", lang.hitch(this, this.onClickAddAdditional));
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
			
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'limesurvey',
					action:	'getGroups'
				}
			}).then(
				lang.hitch(this, function(response) {
					// destroy the loading animation
					DomConstruct.destroy(this.loadingGroupsNode);
					
					// if response is not empty, nable the submit button
					if ( response.data.groups.length > 0 )
					{
						DomAttr.remove(this.submitNode, "disabled");
					}
					
					// go through all groups and add them
					dojo.forEach(response.data.groups, lang.hitch(this, function(group)
					{
						DomConstruct.create("option",
						{
							value:			group.id,
							innerHTML:		group.title
						}, this.groupSelectNode, "last");
					}));
										
					// make the select field visible
					DomClass.remove(this.groupSelectNode, "hidden");
				})
			);
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		addAdditionalFormElements: function()
		{
			var lastRowNode = Query(this.addAdditionalNode).parent()[0];
			
			if ( lastRowNode )
			{
				var inputRowNode = DomConstruct.create("div",
				{
					className:			"input_row",
					style:				"display: none"
				}, lastRowNode, "before");
				
					var divNode = DomConstruct.create("div",
					{
						style:			"float: left; width: 180px;"
					}, inputRowNode, "last");
					
						var aNode = DomConstruct.create("a",
						{
							href:			"#",
							innerHTML:		"entfernen"
						}, divNode, "last");
				
					DomConstruct.create("input",
					{
						className:			"float-left",
						name:				"additionalFirstName_" + this.additionalIndex,
						required:			true,
						displayedValue:		PopupTranslations.additionalFirstName,
						"data-dojo-type":	"dijit/form/ValidationTextBox",
						"data-dojo-props":	"validator:dojox.validate.isText, invalidMessage:'" + PopupTranslations.errorMissing + "'"
						
					}, inputRowNode, "last");
					
					DomConstruct.create("input",
					{
						className:			"float-left",
						name:				"additionalLastName_" + this.additionalIndex,
						required:			true,
						displayedValue:		PopupTranslations.additionalLastName,
						"data-dojo-type":	"dijit/form/ValidationTextBox",
						"data-dojo-props":	"validator:dojox.validate.isText, invalidMessage:'" + PopupTranslations.errorMissing + "'"
						
					}, inputRowNode, "last");
					
					DomConstruct.create("input",
					{
						className:			"float-left",
						name:				"additionalMail_" + this.additionalIndex,
						required:			true,
						displayedValue:		PopupTranslations.additionalMail,
						"data-dojo-type":	"dijit/form/ValidationTextBox",
						"data-dojo-props":	"validator:dojox.validate.isEmailAddress, invalidMessage:'" + PopupTranslations.errorMail + "'",
						style:				"margin-left: 20px;"
						
					}, inputRowNode, "last");
					
					DomConstruct.create("div",
					{
						className:			"clear"
					}, inputRowNode, "last");
				
				this.additionalIndex++;
				
				var formManager = Registry.byId("limesurveyParticipantsForm");
				
				On(aNode, "click", lang.hitch(this, function()
				{
					FX.wipeOut(
					{
						node:		inputRowNode,
						onEnd:		lang.hitch(this, function()
						{
							var widgetsInRow = Registry.findWidgets(inputRowNode);
							dojo.forEach(widgetsInRow, function(widget)
							{
								formManager.unregisterWidget(widget);
								formManager.unregisterWidgetDescendants(widget);
								widget.set("disabled", true);
								widget.set("displayedValue", "");
							});
							
							DomConstruct.destroy(inputRowNode);
						})
					}).play();
				}));
				
				Parser.parse(inputRowNode).then(lang.hitch(this, function(instances)
				{
					dojo.forEach(instances, function(instance)
					{
						formManager.registerWidget(instance);
					});
					
					FX.wipeIn(
					{
						node:		inputRowNode
					}).play();
				}));
			}
		},
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		onChangeWithTokens: function(event)
		{
			// get the checkbox state
			var checked = DomAttr.get(this.withTokensCheckboxNode, "checked");
			
			// show / hide the mail forms
			if ( checked )
			{
				FX.wipeOut(
				{
					node:			this.noTokensNode,
					onEnd:			lang.hitch(this, function()
					{
						Registry.byId("lsParticipantMails").set("disabled", true);
						Registry.byId("lsParticipantMailSubject").set("disabled", true);
						Registry.byId("lsParticipantMailtext").set("disabled", true);
					})
				}).play();
				
				FX.wipeIn(
				{
					node:			this.withTokensNode,
					beforeBegin:	lang.hitch(this, function()
					{
						DomAttr.set(this.groupSelectNode, "disabled", false);
						
						dojo.forEach(Query("input[name^='additional']"), lang.hitch(this, function(node)
						{
							var widget = Registry.getEnclosingWidget(node);
							widget.set("disabled", false);
						}));
					})
				}).play();
			}
			else
			{
				FX.wipeIn(
				{
					node:			this.noTokensNode,
					beforeBegin:	lang.hitch(this, function()
					{
						Registry.byId("lsParticipantMails").set("disabled", false);
						Registry.byId("lsParticipantMailSubject").set("disabled", false);
						Registry.byId("lsParticipantMailtext").set("disabled", false);
						DomClass.remove(this.noTokensNode, "hidden");
					})
				}).play();
				
				FX.wipeOut(
				{
					node:			this.withTokensNode,
					onEnd:			lang.hitch(this, function()
					{
						DomAttr.set(this.groupSelectNode, "disabled", true);
						
						dojo.forEach(Query("input[name^='additional']"), lang.hitch(this, function(node)
						{
							var widget = Registry.getEnclosingWidget(node);
							widget.set("disabled", true);
						}));
					})
				}).play();
			}
		},
		
		onClickAddAdditional: function(event)
		{
			this.addAdditionalFormElements();
		},
		
		onSubmit: function(event)
		{
			event.preventDefault();
			
			var formManager = Registry.byId("limesurveyParticipantsForm");
			
			if ( formManager.isValid() )
			{
				this.setupLoading();
				var formValues = formManager.gatherFormValues();
				
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'limesurvey',
						action:	'inviteParticipants'
					},
					data: {
						groupId:				formValues.group,
						withTokens:				formValues.withTokensCheckbox,
						participantMails:		formValues.participantMails,
						participantMailSubject:	formValues.participantMailSubject,
						participantMailtext:	formValues.participantMailtext,
						formValues:				formValues,
						surveyId:				this.surveyId
					}
				}).then(
					lang.hitch(this, function(response) {
						// remove loading indicator and close this popup
						this.destroyLoading();
						this.Close();
					})
				);
			}
			else
			{
				formManager.validate();
			}
		}
	});
});