define(
[
	"dojo/_base/declare",
	"commsy/widgets/PopupBase",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/StackPortfolioMini.html",
	"dojo/i18n!./nls/StackPortfolioMini",
	"dojo/_base/lang",
	"dojo/dom-construct",
	"dojo/on",
	"commsy/request",
	"dojo/dom-class",
	"dojo/dom-attr",
	"dojo/query",
	"dojo/topic",
	"dijit/registry",
	"dojo/dnd/Source",
	"dojo/_base/array"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	lang,
	DomConstruct,
	On,
	request,
	DomClass,
	DomAttr,
	Query,
	Topic,
	Registry,
	Source,
	Array
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"CommSyWidget",
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
			
			this.dndSources = [];
			this.tags = {
				row:	[],
				column:	[]
			};
			
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
					fct:	'portfolio',
					action:	'getPortfolios'
				}
			}).then(
				lang.hitch(this, function(response) {
					// remove loading node
					DomConstruct.destroy(this.loadingPortfoliosNode);
					
					// if the response is not empty, remove the default option
					// and load the preview for the first portfolio
					if (response.data.myPortfolios.length > 0) {
						DomConstruct.empty(this.portfolioSelectNode);
						this.loadPreview(response.data.myPortfolios[0].id);
					}
					
					// insert select options
					dojo.forEach(response.data.myPortfolios, lang.hitch(this, function(portfolio)
					{
						DomConstruct.create("option", {
							value:		portfolio.id,
							innerHTML:	portfolio.title
						}, this.portfolioSelectNode, "last");
					}));
					
					// show select form
					DomClass.remove(this.portfolioSelectNode, "hidden");
				})
			);
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		loadPreview: function(portfolioId)
		{
			// destroy old dnd sources
			dojo.forEach(this.dndSources, function(dndSource) {
				if (dndSource) {
					dndSource.destroy();
				}
			});
			
			// empty the preview div
			DomConstruct.empty(this.previewPortfolioNode);
			
			// load the portfolio data via ajax
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'portfolio',
					action:	'getPortfolio'
				},
				data: {
					portfolioId: portfolioId
				}
			}).then(
				lang.hitch(this, function(response) {
					// process the tags and create two arrays with rows and columns
					var rowTags = [];
					var columnTags = [];
					dojo.forEach(response.data.tags, function(tag)
					{
						// if column is "0", this is a row,
						// otherwise its a column
						if (tag.column == "0") {
							rowTags.push({
								id:		tag.t_id,
								row:	tag.row,
								title:	tag.title
							});
						} else {
							columnTags.push({
								id:		tag.t_id,
								column:	tag.column,
								title:	tag.title
							});
						}
					});
					
					// store the tags
					this.tags.row = rowTags;
					this.tags.column = columnTags;
					
					// create html
					this.createPreviewHTML(rowTags, columnTags, response.data.links);
				})
			);
		},
		
		createPreviewHTML: function(rowTags, columnTags, items)
		{
			var tableNode = DomConstruct.create("table", {}, this.previewPortfolioNode, "last");
			var trNode = null;
			
			// head
			trNode = DomConstruct.create("tr", {}, tableNode, "last");
			DomConstruct.create("th", {
				innerHTML:		"&nbsp"
			}, trNode, "last");
			dojo.forEach(columnTags, function(columnTag) {
				DomConstruct.create("th", {
					innerHTML:	columnTag.title
				}, trNode, "last");
			});
			
			// create table body
			dojo.forEach(rowTags, lang.hitch(this, function(rowTag, index)
			{
				// first column is the title of the row tag
				trNode = DomConstruct.create("tr", {id:"portfolioMiniTableRow_"+index}, tableNode, "last");
				DomConstruct.create("td", {
					className:	"portfolioTagName",
					innerHTML:	rowTag.title
				}, trNode, "last");
				
				dojo.forEach(columnTags, lang.hitch(this, function(columnTag)
				{
					var tdNode = DomConstruct.create("td", {}, trNode, "last");
					
					var ulNode = DomConstruct.create("ul", {
						
					}, tdNode, "last");
					
					// create a new dnd source
					var source = new Source(ulNode, {
						onDropExternal:		this.onDropExternal
					});
					this.dndSources.push(source);
					
					// insert all items matching row and column tag ids
					var numItems = 0;
					if (items[rowTag.id] && items[columnTag.id]) {
						// scan both tags for the same items
						dojo.forEach(items[rowTag.id], function(rowItem)
						{
							if ( Array.some(items[columnTag.id], function(columnItem)
							{
								return columnItem.itemId == rowItem.itemId;
							})) {
								var itemNode = DomConstruct.create('li', {
									id:	rowItem.itemId,
									className:	'dojoDndItem'
								});
								source.insertNodes(false, [{node: itemNode, data: rowItem.title}]);
								numItems++;
							};
						});
					}
					
					DomConstruct.create('li', {
						innerHTML: numItems
					}, source.node, "first");
					
					// watch the source for changes
					On(source, 'Drop', lang.hitch(this, lang.partial(this.onItemHasDropped, source)));
				}));
			}));
		},
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		onChangePortfolio: function(event)
		{
			var portfolioId = event.target.value;
			this.loadPreview(portfolioId);
		},
		
		onItemHasDropped: function(targetSource, source, nodes, copy)
		{
			if (nodes[0]) {
				var node = nodes[0];
				
				// get the correct row and column indizes for the tags
				var tdNode = targetSource.node.parentNode;
				var trNode = tdNode.parentNode;
				
				var column = tdNode.cellIndex - 1;
				var row = trNode.id.substr(22);

				// get the tag ids
				var columnTagId = this.tags.column[column].id;
				var rowTagId = this.tags.row[row].id;
				
				// get the item id
				var item = source.getItem(node.id);
				var itemId = item.data.data.itemId;
				
				this.setupLoading();
				
				// save via ajax
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'tags',
						action:	'addTagsToItem'
					},
					data: {
						tagIdArray:	[columnTagId, rowTagId],
						itemId:		itemId,
						roomId:		this.from_php.ownRoom.id
					}
				}).then(
					lang.hitch(this, function(response) {
						// is the columnTagId or the rowTagId not already assigned?
						if (response.data.already_assigned.length < 2) {
							var infoNodeList = Query('li:first-child', targetSource.node);
							if (infoNodeList[0]) {
								var infoNode = infoNodeList[0];
								
								DomAttr.set(infoNode, 'innerHTML', parseInt(DomAttr.get(infoNode, 'innerHTML')) + 1);
							}
						}
						
						this.destroyLoading();
					})
				);
			}
		},
		
		onDropExternal: function(source, nodes, copy)
		{
			if (nodes[0]) {
				var node = nodes[0];
				
				// Extract the title from the node and add it
				var aNode = Query('div:first-child a', node)[0];
				if (aNode) {
					var title = DomAttr.get(aNode, 'innerHTML');
					
					this.selectNone();
					this.insertNodes(false, [{data: title}], this.before, this.current);
				}
			}
		}
	});
});