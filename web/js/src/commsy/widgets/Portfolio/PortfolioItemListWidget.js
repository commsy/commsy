define(
[
	"dojo/_base/declare",
	"commsy/widgets/PopupBase",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/PortfolioItemListWidget.html",
 	"dojo/i18n!./nls/PortfolioItemListWidget",
	"dojo/_base/lang",
	"commsy/request",
	"dojo/dom-construct",
	"dojo/dom-attr",
	"dojo/_base/array",
	"dojo/query",
	"dojo/on"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	lang,
	request,
	DomConstruct,
	DomAttr,
	Array,
	Query,
	On
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"portfolioItemListWidget",
		
		canOverlay:			true,							///< Determs if popup can overlay other popus
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		portfolioId:		null,							///< portfolio id mixed in by calling class
		contextId:			null,
		row:				null,
		column:				null,
		itemIds:			[],
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.popupTranslations = PopupTranslations;
			
			this.templatePath = this.from_php.template.tpl_path;
		},
		
		/**
		 * \brief	Processing after the DOM fragment is created
		 * 
		 * Called after the DOM fragment has been created, but not necessarily
		 * added to the document.  Do not include any operations which rely on
		 * node dimensions or placement.
		 */
		postCreate: function() {
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
		startup: function() {
			this.inherited(arguments);
	
			// request list
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'portfolio',
					action:	'getPortfolioList'
				},
				data: {
					portfolioId:	this.portfolioId,
					itemIdArray:	this.ItemIds,
					row:			this.row,
					column:			this.column
				}
			}).then(
				lang.hitch(this, function(response) {
					this.createEntriesList(response.data.items);
					this.createAnnotationList(response.data.annotationItems);
					
					// set number of entries
					DomAttr.set(this.numEntriesNode, "innerHTML", response.data.items.length);
					DomAttr.set(this.numAnnotationsNode, "innerHTML", response.data.annotationItems.length);
					
					// register event handling
					var aDetailNodes = Query("a.openDetailPopup", this.contentNode);
					dojo.forEach(aDetailNodes, lang.hitch(this, function(node, index, arr) {
						require(["commsy/popups/ClickDetailPopup"], lang.hitch(this, function(ClickPopup) {
							var handler = new ClickPopup();
							var customObject = this.getAttrAsObject(node, "data-custom");
							
							customObject.fromPortfolio = true;
							customObject.portfolioId = this.portfolioId;
							customObject.contextId = this.contextId;
							
							handler.init(node, customObject);
							
							this.own(On(node, "click", lang.hitch(this, function(event) {
								this.Close();
							})));
						}));
					}));
					
					var aDetailNodes = Query("a.openDetailPopupAnnotation", this.contentNode);
					dojo.forEach(aDetailNodes, lang.hitch(this, function(node, index, arr) {
						require(["commsy/popups/ClickDetailPopup"], lang.hitch(this, function(ClickPopup) {
							var handler = new ClickPopup();
							var customObject = this.getAttrAsObject(node, "data-custom");
							
							customObject.portfolioRow = this.row;
							customObject.portfolioColumn = this.column;
							customObject.fromPortfolio = true;
							customObject.portfolioId = this.portfolioId;
							customObject.contextId = this.contextId;
							
							handler.init(node, customObject);
							
							this.own(On(node, "click", lang.hitch(this, function(event) {
								this.Close();
							})));
							
							//this.annotationDetailHandler.push({ annotationId: customObject.iid, handler: handler });
						}));
					}));
				})
			);
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		createEntriesList: function(items)
		{
			dojo.forEach(items, lang.hitch(this, function(item, index)
			{
				var divItemNode = DomConstruct.create("div",
				{
					className:			(index % 2 === 0) ? "pop_row_even" : "pop_row_odd"
				}, this.entriesNode, "last");
				
					var firstColumnNode = DomConstruct.create("div",
					{
						className:		"pop_col_330"
					}, divItemNode, "last");
					
						DomConstruct.create("a",
						{
							href:			"#",
							className:		"openDetailPopup",
							"data-custom":	"iid: " + item.itemId + ", module: '" + item.module + "'",
							innerHTML:		item.title
						}, firstColumnNode, "last");
					
					DomConstruct.create("div",
					{
						className:		"pop_col_90",
						innerHTML:		item.modificationDate
					}, divItemNode, "last");
					
					DomConstruct.create("div",
					{
						className:		"pop_col_150",
						innerHTML:		item.modificator
					}, divItemNode, "last");
					
					DomConstruct.create("div",
					{
						className:		"clear"
					}, divItemNode, "last");
			}));
		},
		
		createAnnotationList: function(items)
		{
			dojo.forEach(items, lang.hitch(this, function(item, index)
			{
				var divItemNode = DomConstruct.create("div",
				{
					className:			(index % 2 === 0) ? "pop_row_even" : "pop_row_odd"
				}, this.annotationsNode, "last");
				
					var firstColumnNode = DomConstruct.create("div",
					{
						className:		"pop_col_330"
					}, divItemNode, "last");
					
						DomConstruct.create("a",
						{
							href:		"#",
							className:	"openDetailPopupAnnotation",
							"data-custom":	"iid: " + item.itemId + ", module: 'annotation'",
							innerHTML:	item.title
						}, firstColumnNode, "last");
					
					DomConstruct.create("div",
					{
						className:		"pop_col_90",
						innerHTML:		item.modificationDate
					}, divItemNode, "last");
					
					DomConstruct.create("div",
					{
						className:		"pop_col_150",
						innerHTML:		item.modificator
					}, divItemNode, "last");
					
					DomConstruct.create("div",
					{
						className:		"clear"
					}, divItemNode, "last");
			}));
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickCreateAnnotation: function(event)
		{
			var createAnnotationNode = this.createAnnotationNode;

			require(["commsy/popups/ClickAnnotationPopup"], lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				
				var data =
				{
					module:				"annotation",
					iid:				"NEW",
					portfolioId:		this.portfolioId,
					contextId:			this.contextId,
					portfolioRow:		this.row,
					portfolioColumn:	this.column
				};
				
				this.Close();
				
				handler.init(createAnnotationNode, data);
				handler.open();
			}));
		}
	});
});