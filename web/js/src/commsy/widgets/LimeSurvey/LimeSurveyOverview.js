define(
[
	"dojo/_base/declare",
	"commsy/widgets/List/ListWidget",
	"dojo/i18n!./nls/LimeSurveyOverview",
	"dojo/_base/lang",
	"dojo/dom-construct",
	"dojo/on",
	"commsy/request",
	"dojo/dom-class",
	"dojo/query",
	"dojo/topic",
	"dijit/form/Button",
	"dijit/Dialog"
], function
(
	declare,
	ListWidget,
	PopupTranslations,
	lang,
	DomConstruct,
	On,
	request,
	DomClass,
	Query,
	Topic,
	Button,
	Dialog
) {
	return declare([ListWidget],
	{	
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
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
			
			// configure columns definition
			this.addColumn(0, lang.hitch(this, function(rowNode, rowData)
			{
				// first column
				var firstColumnNode = DomConstruct.create("div",
				{
					className:		"column_260"
				}, rowNode, "last");
					
					var title = rowData.title;
					if ( rowData.active === false )
					{
						title += " ";
					}
					
					var pNode = DomConstruct.create("p",
					{
						innerHTML:	title,
						style:		(rowData.active === true) ? "" : "background-color: rgba(250, 139, 139, 0.38)"
					}, firstColumnNode, "last");
					
						if ( rowData.active === false )
						{
							var aNode = DomConstruct.create("a",
							{
								href:		"#",
								innerHTML:	"(" + PopupTranslations.activate + ")"
							}, pNode, "last");
							
							On(aNode, "click", lang.hitch(this, function()
							{
								// create the dialog
								var activateDialog = new Dialog(
								{
									title:			PopupTranslations.activateDialogTitle,
									content:		DomConstruct.create("div",
									{
										innerHTML:		PopupTranslations.activateDialogContent
									})
								});
								
								// create the delete button
								var activateButton = new Button(
								{
									label:			PopupTranslations.activateDialogButton,
									onClick:		lang.hitch(this, function(event)
									{
										// activate survey
										this.setupLoading();
										
										request.ajax({
											query: {
												cid:	this.uri_object.cid,
												mod:	'ajax',
												fct:	'limesurvey',
												action:	'activateSurvey'
											},
											data: {
												surveyId:	rowData.sid
											}
										}).then(
											lang.hitch(this, function(response) {
												this.destroyLoading();
												Topic.publish("updateSurveys", {});
											})
										);

										// destroy the dialog
										activateDialog.destroyRecursive();
									})
								});
								
								// place button in dialog
								dojo.place(activateButton.domNode, activateDialog.containerNode, "last");
								
								// show dialog
								activateDialog.show();
							}));
						}
			}));
			
			this.addColumn(1, function(rowNode, rowData)
			{
				// second column
				var secondColumnNode = DomConstruct.create("div",
				{
					className:		"column_80"
				}, rowNode, "last");

					DomConstruct.create("p",
					{
						innerHTML:		rowData.sid
					}, secondColumnNode, "last");
			});
			
			this.addColumn(2, lang.hitch(this, function(rowNode, rowData)
			{
				// third column
				var thirdColumnNode = DomConstruct.create("div",
				{
					className:		"column_65"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, thirdColumnNode, "last");
						
						var aNode = DomConstruct.create("a",
						{
							href:		"#",
							className:	"limeSurveyDelete",
							innerHTML:	"&nbsp;",
							title:		PopupTranslations.deleteSurvey
						}, pNode, "last");
				
				On(aNode, "click", lang.hitch(this, function()
				{
					// create the dialog
					var deleteDialog = new Dialog(
					{
						title:			PopupTranslations.deleteSurvey
					});
					
					// create the delete button
					var deleteButton = new Button(
					{
						label:			PopupTranslations.deleteSurvey,
						onClick:		lang.hitch(this, function(event)
						{
							// delete survey
							this.setupLoading();
							
							request.ajax({
								query: {
									cid:	this.uri_object.cid,
									mod:	'ajax',
									fct:	'limesurvey',
									action:	'delete'
								},
								data: {
									surveyId:	rowData.sid
								}
							}).then(
								lang.hitch(this, function(response) {
									this.destroyLoading();
									Topic.publish("updateSurveys", {});
								})
							);
							
							// destroy the dialog
							deleteDialog.destroyRecursive();
						})
					});
					
					// place button in dialog
					dojo.place(deleteButton.domNode, deleteDialog.containerNode, "last");
					
					// show dialog
					deleteDialog.show();
				}));
			}));
			
			this.addColumn(3, function(rowNode, rowData)
			{
				// fourth column
				var fourthColumnNode = DomConstruct.create("div",
				{
					className:		"column_100"
				}, rowNode, "last");

					DomConstruct.create("p",
					{
						innerHTML:		rowData.expires
					}, fourthColumnNode, "last");
			});
			
			this.addColumn(4, lang.hitch(this, function(rowNode, rowData)
			{
				// fifth column
				var fifthColumnNode = DomConstruct.create("div",
				{
					className:		"column_90"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, fifthColumnNode, "last");

						var aNode = DomConstruct.create("a",
						{
							href:		"#",
							innerHTML:	PopupTranslations.participants
						}, pNode, "last");
				
				On(aNode, "click", lang.hitch(this, function()
				{
					var widgetManager = this.getWidgetManager();
					widgetManager.GetInstance("commsy/widgets/LimeSurvey/LimeSurveyParticipants", { surveyId: rowData.sid }).then(lang.hitch(this, function(deferred)
					{
						var widgetInstance = deferred.instance;
						
						widgetInstance.Open();
					}));
				}));
			}));
			
			this.addColumn(5, lang.hitch(this, function(rowNode, rowData)
			{
				if ( rowData.active == true )
				{
					// sixth column
					var sixthColumnNode = DomConstruct.create("div",
					{
						className:		"column_90"
					}, rowNode, "last");

						var pNode = DomConstruct.create("p", {}, sixthColumnNode, "last");

							var aNode = DomConstruct.create("a",
							{
								href:		"#",
								innerHTML:	PopupTranslations.exportSurvey
							}, pNode, "last");
					
					On(aNode, "click", lang.hitch(this, function()
					{
						this.setupLoading();
						
						request.ajax({
							query: {
								cid:	this.uri_object.cid,
								mod:	'ajax',
								fct:	'limesurvey',
								action:	'export'
							},
							data: {
								surveyId:	rowData.sid
							}
						}).then(
							lang.hitch(this, function(response) {
								this.destroyLoading();
								Topic.publish("updateExportedSurveys", {});
							})
						);
					}));
				}
			}));
			
			// set the store
			this.setStore("limesurvey");
			
			// subsribe to the update event
			this.subscribe("updateSurveys", lang.hitch(this, function(object)
			{
				this.setStore("limesurvey");
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