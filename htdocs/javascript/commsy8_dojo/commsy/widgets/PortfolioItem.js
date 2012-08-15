define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/_base/array",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/_base/xhr",
        	"dojo/query",
        	"dojo/on",
        	"dojo/topic",
        	"dijit/layout/ContentPane",
        	"dojo/NodeList-traverse"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, Array, DomConstruct, DomAttr, xhr, Query, On, Topic, ContentPane) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyPortfolioItemWidget",
		widgetHandler:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.isInitialized = false;
			this.description = "";
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
		},
		
		init: function() {
			if (this.isInitialized === false) {
				this.update();
				
				require(["commsy/popups/ClickPortfolioPopup"], Lang.hitch(this, function(ClickPopup) {
					var handler = new ClickPopup();
					handler.init(this.editPortfolioNode, { iid: this.portfolioId, module: "portfolioItem" });
				}));
				
				var tagEditNodes = Query("a.tagEdit");
				dojo.forEach(tagEditNodes, Lang.hitch(this, function(tagNode, index, arr) {
					require(["commsy/popups/ClickTagPortfolioPopup"], Lang.hitch(this, function(ClickPopup) {
						var handler = new ClickPopup();
						var customObject = this.getAttrAsObject(tagNode, "data-custom");
						customObject.portfolioId = this.portfolioId;
						
						if (customObject) {
							handler.init(tagNode, customObject);
						}
					}));
				}));
				
				// subscribe
				Topic.subscribe("updatePortfolio", Lang.hitch(this, function(object) {
					if (object.portfolioId === this.portfolioId) {
						this.update();
					}
				}));
				
				this.isInitialized = true;
			}
		},
		
		update: function() {
			this.AJAXRequest("portfolio", "getPortfolio", { portfolioId: this.portfolioId },
					Lang.hitch(this, function(response) {
						this.response = response;
						
						this.descriptionNode.innerHTML = response.description;
						
						// separate row and column nodes
						var rowTags = dojo.filter(response.tags, function(item, index) {
							return item.row > 0;
						});
						
						var columnTags = dojo.filter(response.tags, function(item, index) {
							return item.column > 0;
						});
						
						// create html for row tags
						var createdRowNodes = Query(this.lastVerticalTag).prevAll("div.ep_vert_col_cell");
						dojo.forEach(createdRowNodes, Lang.hitch(this, function(rowNode, index, arr) {
							DomConstruct.destroy(rowNode);
						}));
						
						dojo.forEach(rowTags, Lang.hitch(this, function(rowTag, index, arr) {
							var divNode = DomConstruct.create("div", { className: "ep_vert_col_cell" }, this.lastVerticalTag, "before");
							
								var aNode = DomConstruct.create("a", { className: "ep_vert_edit" }, divNode, "last");
									DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_vert_edit.jpg" }, aNode, "last");
								
								var divTitleNode = DomConstruct.create("div", { className: "ep_vert_col_title" }, divNode, "last");
									var aEditNode = DomConstruct.create("a", { }, divTitleNode, "last");
										DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_icon_editdarkgrey.gif" }, aEditNode, "last");
									
									DomConstruct.create("strong", { innerHTML: rowTag.title }, divTitleNode, "last");
								
								DomConstruct.create("div", { className: "clear" }, divNode, "last");
						}));
						
						DomConstruct.empty(this.tableNode);
						
						// create html for column tags	
						var trNode = DomConstruct.create("tr", {}, this.tableNode, "last");
						
							dojo.forEach(columnTags, Lang.hitch(this, function(columnTag, index, arr) {
								var thNode = DomConstruct.create("th", {}, trNode, "last");
								
									var aNode = DomConstruct.create("a", { }, thNode, "last");
										DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_hor_edit.jpg" }, aNode, "last");
									
									var aEditNode = DomConstruct.create("a", { className: "ep_edit_head" }, thNode, "last");
										DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_icon_editdarkgrey.gif" }, aEditNode, "last");
									
									DomConstruct.create("strong", { innerHTML: columnTag.title }, thNode, "last");
							}));
						
						// create html for table cells
						var numCells = rowTags.length * columnTags.length;
						var trNode = null;
						for (var i=0; i < numCells; i++) {
							if (i % columnTags.length === 0) {
								trNode = DomConstruct.create("tr", {}, this.tableNode, "last");
							}
							
								var tdNode = DomConstruct.create("td", { }, trNode, "last");
								
									this.insertHTMLForTableCell(tdNode, (i % columnTags.length) + 1, parseInt(i / columnTags.length) + 1);
						}
					})
				);
		},
		
		insertHTMLForTableCell: function(node, column, row) {
			// first, get the tag id for this cell
			var lookupColumn = column - 1;
			var lookupRow = row - 1;
			
			var filteredArray = dojo.filter(this.response.tags, Lang.hitch(this, function(tag, index, arr) {
				return lookupColumn == tag.column && lookupRow == tag.row;
			}));
			var filteredEntry = filteredArray[0];
			
			// create content div
			var divContentNode = DomConstruct.create("div", { className: "ep_cell_content" }, node, "last");
			
			var numItems = 0;
			var numComments = 0;
			
			// check if there is content for this cell
			if (filteredEntry) {
				// insert content
				var aContentNode = DomConstruct.create("a", { }, divContentNode, "last");
				
				// insert items
				var tagId = filteredEntry.t_id;
				
				if(this.response.links[tagId]) {
					numItems = this.response.links[tagId].length;
					
					dojo.forEach(this.response.links[tagId], Lang.hitch(this, function(item, index, arr) {
						
						// only three
						if (index < 3) {
							var spanNode = DomConstruct.create("span", {
								innerHTML:		item.title.substring(0, 14)
							}, aContentNode, "last");
						}
					}));
				}
			}
			
			// create action content
			var divActionNode = DomConstruct.create("div", { className: "ep_cell_actions" }, node, "last");
			
				if (numItems > 0) DomConstruct.create("p", { className: "ep_item_count", innerHTML: numItems }, divActionNode, "last");
				
				if (numComments > 0) DomConstruct.create("p", { className: "ep_item_comment", innerHTML: 123 }, divActionNode, "last");
				
				var aEditNode = DomConstruct.create("a", {}, divActionNode, "last");
					DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_icon_editdarkgrey.gif" }, aEditNode, "last");
				
				DomConstruct.create("div", { className: "clear" }, divActionNode, "last");
		},
		
		startup: function() {
			this.inherited(arguments);
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});