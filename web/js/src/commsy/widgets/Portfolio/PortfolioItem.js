define(
[
	"dojo/_base/declare",
	"dijit/_WidgetBase",
	"commsy/base",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/PortfolioItem.html",
 	"dojo/i18n!./nls/PortfolioItem",
	"dojo/_base/lang",
	"commsy/request",
	"dojo/_base/array",
	"dojo/dom-construct",
	"dojo/dom-attr",
	"dojo/dom-style",
	"dojo/_base/xhr",
	"dojo/query",
	"dojo/topic",
	"dojo/on",
	"dijit/layout/ContentPane",
	"dojo/NodeList-traverse"
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
	Array,
	DomConstruct,
	DomAttr,
	DomStyle,
	xhr,
	Query,
	topic,
	On,
	ContentPane
) {
	return declare([BaseClass, WidgetBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"CommSyPortfolioItemWidget",
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		creator:			"",
		_setCreatorAttr:	{ node: "creatorNode", type: "innerHTML" },
		
		description:			"",
		_setDescriptionAttr:	{ node: "descriptionNode", type: "innerHTML" },
		
		externalViewer:		"",
		
		externalTemplate:	"",
		
		template:			"",
		
		titleFull:			"",
		_setTitleFullAttr:	{ node: "titleNode", type: "attribute", attribute: "title" },
		
		descriptionFull:	"",
		_setDescriptionFullAttr:	{ node: "descriptionNode", type: "attribute", attribute: "title" },
		
		lastListClickData:	null,
		
		portfolioId:		null,							///< portfolio id mixed in by calling class
		isInitialized:		false,							///< is this portfolio already initialized?
		
		contextId:			null,
		
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
		
		init: function(withEditing) {
			if (this.isInitialized === false) {
				this.withEditing = withEditing;
				
				// grep information via ajax and display portfolio content
				this.update();
				
				// subscribe
				this.subscribe("updatePortfolio", lang.hitch(this, function(object) {
					if (object.portfolioId == this.portfolioId) {
						this.update();
					}
				}));
				
				this.subscribe("updateAndOpenPortfolioList", lang.hitch(this, function(object) {
					if ( object.portfolioId == this.portfolioId ) {
						
						this.update().then(lang.hitch(this, function() {
							this.onClickPortfolioItemListPopup(
								this.lastListClickData.row,
								this.lastListClickData.column,
								this.lastListClickData.ItemIds
							);
						}));
					}
				}));
				
				this.isInitialized = true;
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
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		insertHTMLForTableCell: function(node, columnIndex, columnTag, rowIndex, rowTag)
		{
			// get the tag ids an item has to matchx
			var tagIdsToMatch = [];
			tagIdsToMatch.push(columnTag.t_id);
			tagIdsToMatch.push(rowTag.t_id);

			// create content div
			var divContentNode = DomConstruct.create("div", { className: "ep_cell_content" }, node, "last");
			
			var numItems = 0;
			var numComments = 0;
			
			if (this.response.numAnnotations[rowIndex] && this.response.numAnnotations[rowIndex][columnIndex]) numComments = this.response.numAnnotations[rowIndex][columnIndex];
			
			// check if there is content for this cell - because this is a matrix, tagIDsToMatch needs to have two entries
			if (tagIdsToMatch.length == 2) {
				// insert content
				var aContentNode = DomConstruct.create("a", { }, divContentNode, "last");
				
				var tagIdOne = tagIdsToMatch[0];
				var tagIdTwo = tagIdsToMatch[1];
				
				var itemIdArray = [];
				
				// check for items matching
				if(this.response.links[tagIdOne] && this.response.links[tagIdTwo]) {
					// go through all item ids in first tag
					dojo.forEach(this.response.links[tagIdOne], lang.hitch(this, function(item, index, arr) {
						var itemId = item.itemId;
						
						// check if the second tag also contains this id
						var match = dojo.some(this.response.links[tagIdTwo], lang.hitch(this, function(item2, index2, arr2) {
							return item2.itemId == itemId;
						}));
						
						if (match) {
							// only three
							if (numItems < 3) {
								DomConstruct.create("span", {
									innerHTML:		item.title.substring(0, 20)
								}, aContentNode, "last");
							};
							
							itemIdArray.push(item.itemId);
							numItems++;
						}
					}));
				}
				
				// register onclick
				On(aContentNode, "click", lang.hitch(this, function(event)
				{
					this.onClickPortfolioItemListPopup(rowIndex, columnIndex, itemIdArray);
				}));
			}
			
			// create action content
			var divActionNode = DomConstruct.create("div", { className: "ep_cell_actions" }, node, "last");
			
				if (numItems > 0) {
					DomConstruct.create("p", { className: "ep_item_count", innerHTML: numItems }, divActionNode, "last");
					DomConstruct.create("p", { className: "ep_item_comment", innerHTML: numComments }, divActionNode, "last");
				}
				
				
				DomConstruct.create("div", { className: "clear" }, divActionNode, "last");
		},
		
		update: function() {
			return request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'portfolio',
					action:	'getPortfolio'
				},
				data: {
					portfolioId: this.portfolioId
				}
			}).then(
				lang.hitch(this, function(response) {
					// clean up previous widget instances
					var widgetManager = this.getWidgetManager();
					widgetManager.removeInstances("commsy/widgets/Portfolio/PortfolioEditWidget");
					widgetManager.removeInstances("commsy/widgets/Portfolio/PortfolioTagEditWidget");
					widgetManager.removeInstances("commsy/widgets/Portfolio/PortfolioItemListWidget");
					
					// set data for this portfolio - title is already set
					this.set("creator", response.data.creator);
					this.set("descriptionFull", response.data.description);
					this.set("externalViewer", response.data.externalViewer);
					this.set("externalTemplate", response.data.externalTemplate);
					this.set("template", response.data.template);
					
					this.set("contextId", response.data.contextId);
					
					var description = response.data.description;
					if ( description.length > 80 ) {
						description = description.substr(0, 80) + "...";
					}
					this.set("description", description);
					
					this.response = response.data;
					
					// separate row and column nodes
					var rowTags = dojo.filter(response.data.tags, function(item, index) {
						return item.row > 0;
					});
					
					var columnTags = dojo.filter(response.data.tags, function(item, index) {
						return item.column > 0;
					});
					
					if (this.withEditing === false) {
						DomStyle.set(this.lastVerticalTag, "display", "none");
						DomStyle.set(this.portfolioEditDivNode, "display", "none");
						DomStyle.set(this.portfolioUnsubscribeDivNode, "display", "block");
						DomStyle.set(this.portfolioEditColumnNode, "display", "none");
					}
					
					// create html for row tags
					var createdRowNodes = Query(this.lastVerticalTag).prevAll("div.ep_vert_col_cell");
					dojo.forEach(createdRowNodes, lang.hitch(this, function(rowNode, index, arr) {
						DomConstruct.destroy(rowNode);
					}));
					
					dojo.forEach(rowTags, lang.hitch(this, function(rowTag, index, arr) {
						var divNode = DomConstruct.create("div", { className: "ep_vert_col_cell" }, this.lastVerticalTag, "before");
							
							var divTitleNode = DomConstruct.create("div", { className: "ep_vert_col_title" }, divNode, "last");
								if (this.withEditing === true) {
									var aEditNode = DomConstruct.create("a", {
										href:			"#",
										"data-custom":	"tagId: '" + rowTag.t_id + "', position: 'row', module: 'tagPortfolio'"
									}, divTitleNode, "last");
									
										DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_icon_editdarkgrey.gif" }, aEditNode, "last");
								}
								
								DomConstruct.create("strong", { innerHTML: rowTag.title }, divTitleNode, "last");
								
								if ( rowTag.description )
								{
									var description = rowTag.description;
									if ( description.length > 60 )
									{
										description = description.substr(0, 60) + "...";
									}
									DomConstruct.create("div", { innerHTML: description, title: rowTag.description }, divTitleNode, "last");
								}
							
							DomConstruct.create("div", { className: "clear" }, divNode, "last");
						
						// register edit
						if ( this.withEditing === true ) {
							On(aEditNode, "click", lang.hitch(this, function(event)
							{
								this.onClickEditTag(event, "row", rowTag);
							}));
						}
					}));
					
					DomConstruct.empty(this.tableNode);
					
					// create html for column tags	
					var trNode = DomConstruct.create("tr", {}, this.tableNode, "last");
					
						dojo.forEach(columnTags, lang.hitch(this, function(columnTag, index, arr) {
							var thNode = DomConstruct.create("th", {}, trNode, "last");
							
								var divTitleNode = DomConstruct.create("div", { className: "ep_hor_col_title" }, thNode, "last");
									if (this.withEditing === true) {
										var aEditNode = DomConstruct.create("a", {
											href:			"#",
											"data-custom":	"tagId: '" + columnTag.t_id + "', position: 'row', module: 'tagPortfolio'"
										}, divTitleNode, "last");
										
											DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_icon_editdarkgrey.gif" }, aEditNode, "last");
									}
									
									DomConstruct.create("strong", { innerHTML: columnTag.title }, divTitleNode, "last");
									
									if ( columnTag.description )
									{
										var description = columnTag.description;
										if ( description.length > 60 )
										{
											description = description.substr(0, 60) + "...";
										}
										DomConstruct.create("div", { innerHTML: description, title: columnTag.description }, divTitleNode, "last");
									}
								
								DomConstruct.create("div", { className: "clear" }, thNode, "last");
							
							// register edit
							if (this.withEditing === true) {
								On(aEditNode, "click", lang.hitch(this, function(event)
								{
									this.onClickEditTag(event, "column", columnTag);
								}));
							}
						}));
						
					// create html for table cells
					dojo.forEach(rowTags, lang.hitch(this, function(rowTag, rowIndex)
					{
						var trNode = DomConstruct.create("tr", {}, this.tableNode, "last");
						
						dojo.forEach(columnTags, lang.hitch(this, function(columnTag, columnIndex)
						{
							var tdNode = DomConstruct.create("td", {}, trNode, "last");
							
							this.insertHTMLForTableCell(tdNode, columnIndex, columnTag, rowIndex, rowTag);
						}));
					}));
				})
			);
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickEditPortfolio: function(event)
		{
			var widgetManager = this.getWidgetManager();
			
			widgetManager.GetInstance("commsy/widgets/Portfolio/PortfolioEditWidget", { portfolioId: this.portfolioId }).then(lang.hitch(this, function(deferred)
			{
				var widgetInstance = deferred.instance;
				
				// set portfolio data
				widgetInstance.set("portfolioTitle", this.get("titleFull"));
				widgetInstance.set("portfolioDescription", this.get("descriptionFull"));
				widgetInstance.set("portfolioExternalViewer", this.get("externalViewer"));
				widgetInstance.set("portfolioExternalTemplate", this.get("externalTemplate"));
				widgetInstance.set("portfolioTemplate", this.get("template"));
				
				widgetInstance.Open();
			}));
		},

		onClickUnsubscribePortfolio: function(event)
		{
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'portfolio',
					action:	'unsubscribePortfolio'
				},
				data: {
					portfolioId:	this.portfolioId
				}
			}).then(
				lang.hitch(this, function(response) {
					topic.publish("removeTab", { itemId: this.portfolioId });
				})
			);
		},
		
		onInsertNewTag: function(event)
		{
			var customObject = this.getAttrAsObject(event.target, "data-custom");
			
			var widgetManager = this.getWidgetManager();
			
			widgetManager.GetInstance(	"commsy/widgets/Portfolio/PortfolioTagEditWidget",
										{ position: customObject.position, portfolioId: this.portfolioId }).then(lang.hitch(this, function(deferred)
			{
				var widgetInstance = deferred.instance;
				widgetInstance.Open();
			}));
		},
		
		onClickEditTag: function(event, position, tag)
		{
			// prepare init data
			var initData =
			{
				position:		position,
				portfolioId:	this.portfolioId,
				tagId:			tag.t_id,
				description:	tag.description
			};
			
			var widgetManager = this.getWidgetManager();
			
			widgetManager.GetInstance(	"commsy/widgets/Portfolio/PortfolioTagEditWidget",
										initData).then(lang.hitch(this, function(deferred)
			{
				var widgetInstance = deferred.instance;
				widgetInstance.Open();
			}));
		},
		
		onClickPortfolioItemListPopup: function(row, column, itemIdArray)
		{
			var initData =
			{
				portfolioId:	this.portfolioId,
				contextId:		this.contextId,
				row:			row,
				column:			column,
				ItemIds:		itemIdArray
			};
			this.lastListClickData = initData;
			
			var widgetManager = this.getWidgetManager();
			
			widgetManager.GetInstance(	"commsy/widgets/Portfolio/PortfolioItemListWidget",
										initData).then(lang.hitch(this, function(deferred)
			{
				var widgetInstance = deferred.instance;
				widgetInstance.Open();
			}));
		}
	});
});