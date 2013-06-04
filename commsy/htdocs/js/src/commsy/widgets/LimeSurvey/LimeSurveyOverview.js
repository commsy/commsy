define(
[
	"dojo/_base/declare",
	"commsy/widgets/List/ListWidget",
	"dojo/i18n!./nls/LimeSurveyOverview",
	"dojo/_base/lang",
	"dojo/dom-construct",
	"dojo/on",
	"dojo/dom-class",
	"dojo/query",
	"dojo/topic"
], function
(
	declare,
	ListWidget,
	PopupTranslations,
	Lang,
	DomConstruct,
	On,
	DomClass,
	Query,
	Topic
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
			this.addColumn(0, Lang.hitch(this, function(rowNode, rowData)
			{
				// first column
				var firstColumnNode = DomConstruct.create("div",
				{
					className:		"column_260"
				}, rowNode, "last");
				
					var pNode = DomConstruct.create("p", {}, firstColumnNode, "last");

						DomConstruct.create("a",
						{
							"id":		"listItem" + rowData.sid,
							className:	"stack_link",
							href:		"#",
							innerHTML:	/*rowData.sid*/ "titel"
						}, pNode, "last");
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
			
			this.addColumn(2, Lang.hitch(this, function(rowNode, rowData)
			{
				// third column
				var thirdColumnNode = DomConstruct.create("div",
				{
					className:		"column_45"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, thirdColumnNode, "last");

						DomConstruct.create("img",
						{
							src:		this.from_php.template.tpl_path + "img/" + (rowData.active ? "add.png" : "cross.png"),
							height:		"16px"
						}, pNode, "last");
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
						innerHTML:		rowData.datecreated
					}, fourthColumnNode, "last");
			});
			
			this.addColumn(4, function(rowNode, rowData)
			{
				// fifth column
				var fourthColumnNode = DomConstruct.create("div",
				{
					className:		"column_100"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, fourthColumnNode, "last");

					DomConstruct.create("a",
					{
						"id":		"listItem" + rowData.sid,
						className:	"stack_link",
						href:		"#",
						innerHTML:	/*rowData.sid*/ "Teilnehmer"
					}, pNode, "last");
			});
			
			// set the store
			this.setStore("limesurvey");
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