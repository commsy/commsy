define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/_base/array",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/_base/xhr",
        	"dojo/query",
        	"dojo/on",
        	"dojo/topic",
        	"dijit/layout/ContentPane",
        	"dojo/NodeList-traverse"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, Array, DomConstruct, DomAttr, DomStyle, xhr, Query, On, Topic, ContentPane) {
	
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
		
		init: function(withEditing) {
			if (this.isInitialized === false) {
				this.withEditing = withEditing;
				
				this.update();
				
				require(["commsy/popups/ClickPortfolioPopup"], Lang.hitch(this, function(ClickPopup) {
					var handler = new ClickPopup();
					handler.init(this.editPortfolioNode, { iid: this.portfolioId, module: "portfolioItem" });
				}));
				
				var tagEditNodes = Query("a.tagEdit", this.portfolioNode);
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
					if (object.portfolioId == this.portfolioId) {
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
						
						if (this.withEditing === false) {
							this.creatorNode.innerHTML = response.creator;
						}
						
						// separate row and column nodes
						var rowTags = dojo.filter(response.tags, function(item, index) {
							return item.row > 0;
						});
						
						var columnTags = dojo.filter(response.tags, function(item, index) {
							return item.column > 0;
						});
						
						if (this.withEditing === false) {
							DomStyle.set(this.lastVerticalTag, "display", "none");
							DomStyle.set(this.portfolioEditDivNode, "display", "none");
							DomStyle.set(this.portfolioEditColumnNode, "display", "none");
						}
						
						// create html for row tags
						var createdRowNodes = Query(this.lastVerticalTag).prevAll("div.ep_vert_col_cell");
						dojo.forEach(createdRowNodes, Lang.hitch(this, function(rowNode, index, arr) {
							DomConstruct.destroy(rowNode);
						}));
						
						dojo.forEach(rowTags, Lang.hitch(this, function(rowTag, index, arr) {
							var divNode = DomConstruct.create("div", { className: "ep_vert_col_cell" }, this.lastVerticalTag, "before");
							
								/*var aNode = DomConstruct.create("a", { className: "ep_vert_edit" }, divNode, "last");
									DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_vert_edit.jpg" }, aNode, "last");*/
								
								var divTitleNode = DomConstruct.create("div", { className: "ep_vert_col_title" }, divNode, "last");
									if (this.withEditing === true) {
										var aEditNode = DomConstruct.create("a", {
											href:			"#",
											"data-custom":	"tagId: '" + rowTag.t_id + "', position: 'row', module: 'tagPortfolio'"
										}, divTitleNode, "last");
										
											DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_icon_editdarkgrey.gif" }, aEditNode, "last");
									}
									
									DomConstruct.create("strong", { innerHTML: rowTag.title }, divTitleNode, "last");
								
								DomConstruct.create("div", { className: "clear" }, divNode, "last");
							
							// register edit
							if (this.withEditing === true) {
								require(["commsy/popups/ClickTagPortfolioPopup"], Lang.hitch(this, function(ClickPopup) {
									var handler = new ClickPopup();
									var customObject = this.getAttrAsObject(aEditNode, "data-custom");
									customObject.portfolioId = this.portfolioId;
									
									if (customObject) {
										handler.init(aEditNode, customObject);
									}
								}));
							}
						}));
						
						DomConstruct.empty(this.tableNode);
						
						// create html for column tags	
						var trNode = DomConstruct.create("tr", {}, this.tableNode, "last");
						
							dojo.forEach(columnTags, Lang.hitch(this, function(columnTag, index, arr) {
								var thNode = DomConstruct.create("th", {}, trNode, "last");
								
									/*var aNode = DomConstruct.create("a", { }, thNode, "last");
										DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_hor_edit.jpg" }, aNode, "last");*/
									
									if (this.withEditing === true) {
										var aEditNode = DomConstruct.create("a", {
												className: "ep_edit_head",
												href:			"#",
												"data-custom":	"tagId: '" + columnTag.t_id + "', position: 'row', module: 'tagPortfolio'"
											}, thNode, "last");
											
											DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_icon_editdarkgrey.gif" }, aEditNode, "last");
									}
									
									DomConstruct.create("strong", { innerHTML: columnTag.title.substring(0, 14) }, thNode, "last");
								
								// register edit
								if (this.withEditing === true) {
									require(["commsy/popups/ClickTagPortfolioPopup"], Lang.hitch(this, function(ClickPopup) {
										var handler = new ClickPopup();
										var customObject = this.getAttrAsObject(aEditNode, "data-custom");
										customObject.portfolioId = this.portfolioId;
										
										if (customObject) {
											handler.init(aEditNode, customObject);
										}
									}));
								}
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
			var filteredArray = dojo.filter(this.response.tags, Lang.hitch(this, function(tag, index, arr) {
				return (tag.column == column && tag.row == 0) || (tag.row == row && tag.column == 0)
			}));
			
			// extract the tag ids an item has to match
			var tagIdsToMatch = [];
			dojo.forEach(filteredArray, Lang.hitch(this, function(tag, index, arr) {
				tagIdsToMatch.push(tag.t_id);
			}));

			// create content div
			var divContentNode = DomConstruct.create("div", { className: "ep_cell_content" }, node, "last");
			
			var numItems = 0;
			var numComments = 0;
			
			if (this.response.numAnnotations[row] && this.response.numAnnotations[row][column]) numComments = this.response.numAnnotations[row][column];
			
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
					dojo.forEach(this.response.links[tagIdOne], Lang.hitch(this, function(item, index, arr) {
						var itemId = item.itemId;
						
						// check if the second tag also contains this id
						var match = dojo.some(this.response.links[tagIdTwo], Lang.hitch(this, function(item2, index2, arr2) {
							return item2.itemId == itemId;
						}));
						
						if (match) {
							// only three
							if (numItems < 3) {
								var spanNode = DomConstruct.create("span", {
									innerHTML:		item.title.substring(0, 20)
								}, aContentNode, "last");
							};
							
							itemIdArray.push(item.itemId);
							numItems++;
						}
					}));
				}
				
				// register onclick
				require(["commsy/popups/ClickPortfolioListPopup"], Lang.hitch(this, function(ClickPopup) {
					var handler = new ClickPopup();
					var customObject = {};
					customObject.portfolioId = this.portfolioId;
					customObject.row = row;
					customObject.column = column;
					customObject.itemIds = itemIdArray;
					
					if (customObject) {
						handler.init(aContentNode, customObject);
					}
				}));
			}
			
			// create action content
			var divActionNode = DomConstruct.create("div", { className: "ep_cell_actions" }, node, "last");
			
				if (numItems > 0) {
					DomConstruct.create("p", { className: "ep_item_count", innerHTML: numItems }, divActionNode, "last");
					DomConstruct.create("p", { className: "ep_item_comment", innerHTML: numComments }, divActionNode, "last");
				}
				
				/*
				var aEditNode = DomConstruct.create("a", {}, divActionNode, "last");
					DomConstruct.create("img", { src: this.from_php.template.tpl_path + "img/ep_icon_editdarkgrey.gif" }, aEditNode, "last");*/
				
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