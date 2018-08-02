define(
[
	"dojo/_base/declare",
	"dijit/_WidgetBase",
	"commsy/base",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/ListWidget.html",
 	"dojo/i18n!./nls/ListWidget",
	"dojo/_base/lang",
	"dojo/dom-construct",
	"dojo/dom-attr",
	"dojo/_base/array",
	"dojo/query",
	"commsy/store/Json",
	"dojo/on"
], function
(
	declare,
	WidgetBase,
	BaseClass,
	TemplatedMixin,
	Template,
	PopupTranslations,
	Lang,
	DomConstruct,
	DomAttr,
	Array,
	Query,
	Json,
	On
) {
	return declare([BaseClass, WidgetBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"CommSyWidget",
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		currentPage:			0,
		_setCurrentPageAttr:	{ node: "currentPageNode", type: "innerHTML" },
		
		maxPage:			0,
		_setMaxPageAttr:	{ node: "maxPageNode", type: "innerHTML" },
		
		entriesPerPage:		20,
		query:				"*",									///< query for the json request
		queryOptions:		{},										///< query options for the json request
		
		hasSearchMask:		false,									///< does this list provide a search mask?
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.popupTranslations = PopupTranslations;
			this.templatePath = this.from_php.template.tpl_path;
			
			this.store = null;										///< the store, holding the list data
			this.columns = [];										///< the columns definition
			this.totalListEntries = 0;								///< number of total list entries
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
			this.set("currentPage", 0);
			this.set("maxPage", 0);
			
			if ( this.hasSearchMask === true )
			{
				this.createSearchMask();
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
		startup: function() {
			this.inherited(arguments);
		},
		
		addColumn: function(index, callback)
		{
			this.columns[index] = callback;
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		setStore: function(fct)
		{
			this.store = new Json({
				options:	{ start: 0, numEntries: this.entriesPerPage },
				fct:		fct
			});
			
			// fetch data
			if ( this.hasSearchMask === false )
			{
				this.doQuery(this.query, this.queryOptions, Lang.hitch(this, this.updateList));
			}
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		updateList: function(response)
		{
			this.totalListEntries = response.data.total;
			
			var itemList = response.data.items;
			
			// clear the list content
			DomConstruct.empty(this.itemListNode);
			
			// update template values
			var currentPage = ( response.data.total > 0 ) ? Math.max(this.currentPage, 1) : 0;
			this.set("maxPage", Math.ceil(response.data.total / this.entriesPerPage));
			this.set("currentPage", currentPage);
			
			// go through all items
			dojo.forEach(itemList, Lang.hitch(this, function(item, index, arr)
			{
				// create new row
				var rowNode = DomConstruct.create("div",
				{
					className:		(index % 2 == 0) ? "row_even even_sep_search" : "row_odd odd_sep_search"
				}, this.itemListNode, "last");
				
				// iterate all column callbacks and call them
				dojo.forEach(this.columns, Lang.hitch(this, function(callback, index)
				{
					callback(rowNode, item);
				}));
				
				// end row
				DomConstruct.create("div",
				{
					className:		"clear"
				}, rowNode, "last");
			}));
		},
		
		createSearchMask: function()
		{
			DomConstruct.create("span",
			{
				innerHTML:		PopupTranslations.search
			}, this.searchContainerNode, "last");
			
			var searchInputNode = DomConstruct.create("input",
			{
				type:			"text",
				size:			"20"
			}, this.searchContainerNode, "last");
			
			var searchButtonNode = DomConstruct.create("input",
			{
				type:			"button",
				value:			PopupTranslations.searchButton
			}, this.searchContainerNode, "last");
			
			On(searchButtonNode, "click", Lang.hitch(this, Lang.partial(this.onClickSearchButton, searchInputNode)));
		},
		
		createLoadingAnimation: function()
		{
			var loadingDivNode = DomConstruct.create("div",
			{
				style:			"margin-left: 10px: width: 200px;"
			}, this.itemListNode, "first");
			
				DomConstruct.create("span",
				{
					innerHTML:		this.popupTranslations.loading
				}, loadingDivNode, "last");
				
				DomConstruct.create("img",
				{
					src:			this.templatePath + "img/ajax_loader.gif",
					style:			"margin-left: 5px, top: 2px, position: relative;"
				}, loadingDivNode, "last");
		},
		
		doQuery: function(query, options, callback)
		{
			// loading animation
			this.createLoadingAnimation();
			
			this.store.query(query, options, callback);
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickSearchButton: function(inputNode, event)
		{
			this.query = DomAttr.get(inputNode, "value");
			
			// send a new query
			this.doQuery(this.query, this.queryOptions, Lang.hitch(this, this.updateList));
		},
		
		onClickPagingFirst: function(event)
		{
			if ( this.currentPage > 1 )
			{
				this.set("currentPage", 1);
				this.queryOptions.start = (this.currentPage - 1) * this.entriesPerPage;
				this.queryOptions.numEntries = this.entriesPerPage;
				this.doQuery(this.query, this.queryOptions, Lang.hitch(this, this.updateList));
			}
		},
		
		onClickPagingPrev: function(event)
		{
			if ( this.currentPage > 1 )
			{
				this.set("currentPage", --this.currentPage);
				this.queryOptions.start = (this.currentPage - 1) * this.entriesPerPage;
				this.queryOptions.numEntries = this.entriesPerPage;
				this.doQuery(this.query, this.queryOptions, Lang.hitch(this, this.updateList));
			}
		},
		
		onClickPagingNext: function(event)
		{
			if ( this.currentPage < this.maxPage )
			{
				this.set("currentPage", ++this.currentPage);
				this.queryOptions.start = (this.currentPage - 1) * this.entriesPerPage;
				this.queryOptions.numEntries = this.entriesPerPage;
				this.doQuery(this.query, this.queryOptions, Lang.hitch(this, this.updateList));
			}
		},
		
		onClickPagingLast: function(event)
		{
			if ( this.currentPage < this.maxPage )
			{
				this.set("currentPage", this.maxPage);
				this.queryOptions.start = (this.currentPage - 1) * this.entriesPerPage;
				this.queryOptions.numEntries = this.entriesPerPage;
				this.doQuery(this.query, this.queryOptions, Lang.hitch(this, this.updateList));
			}
		},
		
		onClickPaging20: function(event)
		{
			this.entriesPerPage = 20;
			
			// update template values
			this.set("currentPage", 1);
			this.set("maxPage", Math.ceil(this.totalListEntries / this.entriesPerPage));
			
			this.queryOptions.start = (this.currentPage - 1) * this.entriesPerPage;
			this.queryOptions.numEntries = this.entriesPerPage;
			this.doQuery(this.query, this.queryOptions, Lang.hitch(this, this.updateList));
			DomAttr.set(this.paging50Node, "innerHTML", "50");
			DomAttr.set(this.paging20Node, "innerHTML", "<b>20</b>");
		},
		
		onClickPaging50: function(event)
		{
			this.entriesPerPage = 50;
			
			// update template values
			this.set("currentPage", 1);
			this.set("maxPage", Math.ceil(this.totalListEntries / this.entriesPerPage));
			
			this.queryOptions.start = (this.currentPage - 1) * this.entriesPerPage;
			this.queryOptions.numEntries = this.entriesPerPage;
			this.doQuery(this.query, this.queryOptions, Lang.hitch(this, this.updateList));
			DomAttr.set(this.paging50Node, "innerHTML", "<b>50</b>");
			DomAttr.set(this.paging20Node, "innerHTML", "20");
		}
	});
});