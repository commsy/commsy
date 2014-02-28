define(
[
	"dojo/_base/declare",
	"commsy/widgets/List/ListWidget",
	"dojo/i18n!./nls/LimeSurveyExports",
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
				
					DomConstruct.create("p",
					{
						innerHTML:	rowData.title
					}, firstColumnNode, "last");
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
						innerHTML:		rowData.surveyId
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
							title:		PopupTranslations.deleteExport
						}, pNode, "last");
						
				On(aNode, "click", lang.hitch(this, function()
				{
					// create the dialog
					var deleteDialog = new Dialog(
					{
						title:			PopupTranslations.deleteExport
					});
					
					// create the delete button
					var deleteButton = new Button(
					{
						label:			PopupTranslations.deleteExport,
						onClick:		lang.hitch(this, function(event)
						{
							// delete survey export
							this.setupLoading();
							
							request.ajax({
								query: {
									cid:	this.uri_object.cid,
									mod:	'ajax',
									fct:	'limesurveyExports',
									action:	'delete'
								},
								data: {
									surveyId:	rowData.surveyId,
									timestamp:	rowData.timestamp
								}
							}).then(
								lang.hitch(this, function(response) {
									this.destroyLoading();
									Topic.publish("updateExportedSurveys", {});
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
						innerHTML:		rowData.exportDate
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
					
						if ( rowData.files.survey )
						{
							DomConstruct.create("a",
							{
								href:		"commsy.php?cid=" + this.uri_object.cid + "&mod=limesurvey&fct=getfile&surveyId=" + rowData.surveyId + "&timestamp=" + rowData.timestamp + "&file=survey",
								target:		"blank",
								id:			"limeSurveyFileSurvey",
								innerHTML:	"&nbsp;",
								title:		PopupTranslations.downloadSurvey
							}, pNode, "last");
						}
						
						if ( rowData.files.statistics )
						{
							DomConstruct.create("a",
							{
								href:		"commsy.php?cid=" + this.uri_object.cid + "&mod=limesurvey&fct=getfile&surveyId=" + rowData.surveyId + "&timestamp=" + rowData.timestamp + "&file=statistics",
								id:			"limeSurveyFileStatistics",
								innerHTML:	"&nbsp;",
								title:		PopupTranslations.downloadStatistics
							}, pNode, "last");
						}
						
						if ( rowData.files.responses )
						{
							DomConstruct.create("a",
							{
								href:		"commsy.php?cid=" + this.uri_object.cid + "&mod=limesurvey&fct=getfile&surveyId=" + rowData.surveyId + "&timestamp=" + rowData.timestamp + "&file=responses",
								id:			"limeSurveyFileResponses",
								innerHTML:	"&nbsp;",
								title:		PopupTranslations.downloadResponses
							}, pNode, "last");
						}
						
						if ( rowData.files.create || rowData.files.statistics || rowData.files.responses )
						{
							DomConstruct.create("a",
							{
								href:		"commsy.php?cid=" + this.uri_object.cid + "&mod=limesurvey&fct=getfile&surveyId=" + rowData.surveyId + "&timestamp=" + rowData.timestamp,
								id:			"limeSurveyFileZip",
								innerHTML:	"&nbsp;",
								title:		PopupTranslations.downloadZip
							}, pNode, "last");
						}
			}));
			
			// set the store
			this.setStore("limesurveyExports");
			
			// subsribe to the update event
			this.subscribe("updateExportedSurveys", lang.hitch(this, function(object)
			{
				this.setStore("limesurveyExports");
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