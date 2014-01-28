define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/PortfolioEditWidget.html",
 	"dojo/i18n!./nls/PortfolioEditWidget",
 	"dojo/query",
 	"commsy/request",
 	"dojo/_base/lang",
 	"dojo/topic",
 	"dojo/dom-construct",
 	"dojo/dom-attr",
 	"dojo/on"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	Query,
	request,
	lang,
	Topic,
	DomConstruct,
	DomAttr,
	On
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"portfolioEditWidget",
		
		canOverlay:			true,							///< Determs if popup can overlay other popus
		
		portfolioId:		null,							///< Mixed in by calling class
		
		fromTemplateNode:	null,
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		submit:				"",
		_setSubmitAttr:		{ node: "submitButtonNode", type: "attribute", attribute: "value" },
		
		portfolioTitle:		"",
		_setPortfolioTitleAttr:	{ node: "portfolioTitleNode", type: "attribute", attribute: "value" },
		
		portfolioDescription:	"",
		_setPortfolioDescriptionAttr:	{ node: "portfolioDescriptionNode", type: "innerHTML" },
		
		portfolioExternalViewer:		"",
		_setPortfolioExternalViewerAttr:	{ node: "portfolioExternalViewerNode", type: "attribute", attribute: "value" },
		
		portfolioTemplate:		"",
		_setPortfolioTemplateAttr:	{ node: "portfolioTemplateNode", type: "attribute", attribute: "checked" },
		
		portfolioExternalTemplate:		"",
		_setPortfolioExternalTemplateAttr:	{ node: "portfolioExternalTemplateNode", type: "attribute", attribute: "value" },
		
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
			if ( this.portfolioId === null )
			{
				this.set("title", this.popupTranslations.titleCreate);
				this.set("submit", this.popupTranslations.buttonCreate);
			}
			else
			{
				this.set("title", this.popupTranslations.titleEdit);
				this.set("submit", this.popupTranslations.buttonEdit);
			}
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
			
			// if in edit mode
			if ( this.portfolioId !== null )
			{
				// add delete button
				var deleteButtonNode = DomConstruct.create("input",
				{
					"id":			"popup_button_delete",
					className:		"popup_button float-right submit",
					"data-custom":	"part: 'delete'",
					type:			"button",
					name:			"",
					value:			this.popupTranslations.buttonDelete
				}, this.buttonDivNode, "last");
				
				// register event
				On(deleteButtonNode, "click", lang.hitch(this, this.onDeletePortfolio));
			} else {
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'portfolio',
						action:	'getTemplates'
					}
				}).then(
					lang.hitch(this, function(response) {
						var titleInputRowNode = this.portfolioTitleNode.parentNode;
						
						var rowNode = DomConstruct.create('div', {
							className:		"input_row"
						}, titleInputRowNode, "after");	
						
							DomConstruct.create('label', {
								"for":		"portfolioTemplate",
								innerHTML:	PopupTranslations.template+":"
							}, rowNode, "last");
							
							var selectNode = DomConstruct.create('select', {
								id:			"portfolioTemplate",
								name:		"template"
							}, rowNode, "last");
							
								DomConstruct.create('option', {
									value:		"none",
									innerHTML:	PopupTranslations.templateSelect
								}, selectNode, "last");
						
						dojo.forEach(response.data.templates, lang.hitch(this, function(portfolio){
							DomConstruct.create('option', {
								value:		portfolio.id,
								innerHTML:	portfolio.title
							}, selectNode, "last");
						}));
						
						this.fromTemplateNode = selectNode;
					})
				);
			}
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		_getPortfolioTitleAttr: function()
		{
			return DomAttr.get(this.portfolioTitleNode, "value");
		},
		
		_getPortfolioDescriptionAttr: function()
		{
			return DomAttr.get(this.portfolioDescriptionNode, "value");
		},
		
		_getPortfolioExternalViewerAttr: function()
		{
			return DomAttr.get(this.portfolioExternalViewerNode, "value");
		},
		_getPortfolioTemplateAttr: function()
		{
			return DomAttr.get(this.portfolioTemplateNode, "checked");
		},
		_getPortfolioExternalTemplateAttr: function()
		{
			return DomAttr.get(this.portfolioExternalTemplateNode, "value");
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		onSavePortfolio: function(event)
		{
			// prepare data to send
			var data =
			{
				id:				this.portfolioId || "NEW",
				title:			this.get("portfolioTitle"),
				description:	this.get("portfolioDescription"),
				externalViewer:	this.get("portfolioExternalViewer"),
				template:		this.get("portfolioTemplate"),
				externalTemplate: this.get("portfolioExternalTemplate")
			};
			if (this.fromTemplateNode && this.fromTemplateNode.value != 'none') {
				data.fromTemplate = this.fromTemplateNode.value;
			} else {
				data.fromTemplate = false;
			}
			
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'portfolio',
					action:	'savePortfolio'
				},
				data: data
			}).then(
				lang.hitch(this, function(response) {
					Topic.publish("updatePortfolios", { itemId: response.data.portfolioId });
					this.Close();
				})
			);
		},
		
		onTemplateChange: function (event)
		{
			if(event.target.value == "none") 
			{
				this.portfolioTemplateNode.disabled = false;
				this.portfolioExternalTemplateNode.disabled = false;
			} 
			else 
			{
				this.portfolioTemplateNode.disabled = true;
				this.portfolioExternalTemplateNode.disabled = true;
			}
			
			
		},
		
		onDeletePortfolio: function(event)
		{
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'portfolio',
					action:	'deletePortfolio'
				},
				data: {
					id: this.portfolioId
				}
			}).then(
				lang.hitch(this, function(response) {
					Topic.publish("updatePortfolios", { itemId: this.portfolioId });
					this.Close();
				})
			);
		}
	});
});