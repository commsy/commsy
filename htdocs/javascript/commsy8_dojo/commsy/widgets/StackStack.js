define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/query"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, On, Query) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,
		
		itemId:				null,
		
		currentPage:		1,
		maxPage:			1,
		entriesPerPage:		20,
		search:				"",
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.restrictions = {
				buzzwords: [],
				tags: []
			}
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.itemId = this.from_php.ownRoom.id;
			
			// update list
			this.updateList();
		},
		
		updateList: function() {
			// empty list
			DomConstruct.empty(this.itemListNode);
			
			this.AJAXRequest("widget_stack", "getListContent", {
					search:					this.search.toLowerCase(),
					start:					(this.currentPage - 1) * this.entriesPerPage,
					numEntries:				this.entriesPerPage,
					buzzwordRestrictions:	dojo.map(this.restrictions.buzzwords, function(item) { return item.id }),
					tagRestrictions:		dojo.map(this.restrictions.tags, function(item) { return item.id })
				},
				Lang.hitch(this, function(response) {
					// fill list
					dojo.forEach(response.items, Lang.hitch(this, function(item, index, arr) {
						
						// create list entries
						var rowNode = DomConstruct.create("div", {
							className:		(index % 2 == 0) ? "row_even even_sep_search" : "row_odd odd_sep_search"
						}, this.itemListNode, "last");
						
							var firstColumnNode = DomConstruct.create("div", {
								className:		"column_280"
							}, rowNode, "last");
							
								var pNode = DomConstruct.create("p", {}, firstColumnNode, "last");
								
									var aNode = DomConstruct.create("a", {
										href:		"#",
										innerHTML:	item.title
									}, pNode, "last");
							
							var secondColumnNode = DomConstruct.create("div", {
								className:		"column_45"
							}, rowNode, "last");
							
								var pNode = DomConstruct.create("p", {}, secondColumnNode, "last");
								
									if (item.fileCount > 0) {
										DomConstruct.create("a", {
											className:		"attachment",
											href:			"#",
											innerHTML:		item.fileCount
										}, pNode, "last");
									}
							
							var thirdColumnNode = DomConstruct.create("div", {
								className:		"column_65"
							}, rowNode, "last");
							
								var pNode = DomConstruct.create("p", {}, thirdColumnNode, "last");
								
									DomConstruct.create("img", {
										src:		this.from_php.template.tpl_path + "img/netnavigation/" + item.image.img,
										title:		item.image.text
									}, pNode, "last");
							
							var fourthColumnNode = DomConstruct.create("div", {
								className:		"column_90"
							}, rowNode, "last");
							
								DomConstruct.create("p", {
									innerHTML:		item.modificationDate
								}, fourthColumnNode, "last");
							
							var fifthColumnNode = DomConstruct.create("div", {
								className:		"column_155"
							}, rowNode, "last");
							
								DomConstruct.create("p", {
									innerHTML:		item.creator
								}, fifthColumnNode, "last");
							
							DomConstruct.create("div", {
								className:		"clear"
							}, rowNode, "last");
						
						require(["commsy/popups/ClickDetailPopup"], Lang.hitch(this, function(ClickPopup) {
							var handler = new ClickPopup();
							handler.init(aNode, { iid: item.itemId, module: item.module, contextId: this.itemId, versionId: item.versionId });
						}));
					}));
					
					// update max page
					this.maxPage = Math.ceil(response.total / this.entriesPerPage);
					
					// set template values
					this.currentPageNode.innerHTML = Math.min(this.currentPage, this.maxPage);
					this.maxPageNode.innerHTML = this.maxPage;
				})
			);
		},
		
		addBuzzwordRestriction: function(buzzwordId, buzzwordName) {
			// check if this buzzword is already in list
			var filtered = dojo.filter(this.restrictions.buzzwords, function(buzzword, index, arr) {
				return buzzword.id == buzzwordId;
			});
			
			if (filtered.length == 0) {
				this.restrictions.buzzwords.push({ id: buzzwordId, name: buzzwordName });
				
				// update restriction list
				this.updateBuzzwordRestrictions();
				
				this.updateList();
			}
		},
		
		addTagRestriction: function(tagId, tagName) {
			// check if this tag is already in list
			var filtered = dojo.filter(this.restrictions.tags, function(tag, index, arr) {
				return tag.id == tagId;
			});
			
			if (filtered.length == 0) {
				this.restrictions.tags.push({ id: tagId, name: tagName });
				
				// update restriction list
				this.updateTagRestrictions();
				
				this.updateList();
			}
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickPaging20: function(event) {
			this.entriesPerPage = 20;
			this.currentPage = 1;
			this.paging20.innerHTML = "<strong>20</strong>";
			this.paging50.innerHTML = "50";
			this.updateList();
		},
		
		onClickPaging50: function(event) {
			this.entriesPerPage = 50;
			this.currentPage = 1;
			this.paging20.innerHTML = "20";
			this.paging50.innerHTML = "<strong>50</strong>";
			this.updateList();
		},
		
		onClickPagingFirst: function(event) {
			if (this.currentPage > 1) this.currentPage = 1;
			this.updateList();
		},
		
		onClickPagingPrev: function(event) {
			if (this.currentPage > 1) this.currentPage--;
			this.updateList();
		},
		
		onClickPagingNext: function(event) {
			if (this.currentPage < this.maxPage) this.currentPage++;
			this.updateList();
		},
		
		onClickPagingLast: function(event) {
			if (this.currentPage < this.maxPage) this.currentPage = this.maxPage;
			this.updateList();
		},
		
		onClickSearch: function(event) {
			var searchWord = this.searchNode.value;
			this.search = searchWord;
			this.updateList();
		},
		
		onBuzzwordRestrictionRemove: function(buzzwordId) {
			var liNode = Query("li#" + buzzwordId, this.buzzwordRestrictionsNode)[0];
			
			if (liNode) {
				DomConstruct.destroy(liNode);
			}
			
			this.restrictions.buzzwords = dojo.filter(this.restrictions.buzzwords, function(buzzword, index, arr) {
				return buzzword.id != buzzwordId;
			});
			
			this.updateList();
		},
		
		onTagRestrictionRemove: function(tagId) {
			var liNode = Query("li#" + tagId, this.tagRestrictionsNode)[0];
			
			if (liNode) {
				DomConstruct.destroy(liNode);
			}
			
			this.restrictions.tags = dojo.filter(this.restrictions.tags, function(tag, index, arr) {
				return tag.id != tagId;
			});
			
			this.updateList();
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		updateBuzzwordRestrictions: function() {
			DomConstruct.empty(this.buzzwordRestrictionsNode);
			
			dojo.forEach(this.restrictions.buzzwords, Lang.hitch(this, function(buzzword, index, arr) {
				var liNode = DomConstruct.create("li", {
					"id":		buzzword.id,
					className:	"float-left"
				}, this.buzzwordRestrictionsNode, "last");
				
					DomConstruct.create("span", {
						innerHTML:	buzzword.name
					}, liNode, "last");
					
					var aNode = DomConstruct.create("a", {
						href:		"#"
					}, liNode, "last");
					
						DomConstruct.create("img", {
							src:	this.from_php.template.tpl_path + "img/btn_del_tag.gif"
						}, aNode, "last");
				
				On(aNode, "click", Lang.hitch(this, function(event) {
					this.onBuzzwordRestrictionRemove(buzzword.id);
				}));
			}));
		},
		
		updateTagRestrictions: function() {
			DomConstruct.empty(this.tagRestrictionsNode);
			
			dojo.forEach(this.restrictions.tags, Lang.hitch(this, function(tag, index, arr) {
				var liNode = DomConstruct.create("li", {
					"id":		tag.id,
					className:	"float-left"
				}, this.tagRestrictionsNode, "last");
				
					DomConstruct.create("span", {
						innerHTML:	tag.name
					}, liNode, "last");
					
					var aNode = DomConstruct.create("a", {
						href:		"#"
					}, liNode, "last");
					
						DomConstruct.create("img", {
							src:	this.from_php.template.tpl_path + "img/btn_del_tag.gif"
						}, aNode, "last");
				
				On(aNode, "click", Lang.hitch(this, function(event) {
					this.onTagRestrictionRemove(tag.id);
				}));
			}));
		}
	});
});