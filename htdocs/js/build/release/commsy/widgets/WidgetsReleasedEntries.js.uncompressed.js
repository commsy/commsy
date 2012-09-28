define("commsy/widgets/WidgetsReleasedEntries", [	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, Query, On) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,
		
		itemId:				null,

		currentPage: {
			released:		1,
			viewable:		1
		},
		
		maxPage: {
			released:		1,
			viewable:		1
		},
		
		entriesPerPage: {
			released:		20,
			viewable:		20
		},
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.itemId = this.from_php.ownRoom.id;

			// update list
			this.updateLists();
		},
		
		updateLists: function() {
			this.updateReleasedList();
			this.updateViewableList();
		},
		
		updateReleasedList: function() {
			// empty list
			DomConstruct.empty(this.releasedItemListNode);

			this.AJAXRequest("widget_released_entries", "getReleasedListContent", {
					start:					(this.currentPage.released - 1) * this.entriesPerPage.released,
					numEntries:				this.entriesPerPage.released
				},
				Lang.hitch(this, function(response) {
					// fill list
					dojo.forEach(response.items, Lang.hitch(this, function(item, index, arr) {

						// create list entries
						var rowNode = DomConstruct.create("div", {
							className:		(index % 2 == 0) ? "row_even even_sep_search" : "row_odd odd_sep_search"
						}, this.releasedItemListNode, "last");

							var firstColumnNode = DomConstruct.create("div", {
								className:		"column_280"
							}, rowNode, "last");

								var pNode = DomConstruct.create("p", {}, firstColumnNode, "last");

									var aNode = DomConstruct.create("a", {
										"id":		"listItem" + item.itemId,
										className:	"stack_link",
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
								className:		"column_260"
							}, rowNode, "last");

								DomConstruct.create("p", {
									innerHTML:		item.releasedFor
								}, fourthColumnNode, "last");
							
							DomConstruct.create("div", {
								className:		"clear"
							}, rowNode, "last");
						
						require(["commsy/popups/ClickDetailPopup"], Lang.hitch(this, function(ClickPopup) {
							var handler = new ClickPopup();
							handler.init(aNode, { iid: item.itemId, module: item.module, contextId: this.itemId, versionId: item.versionId });
						}));
					}));

					// update max page
					this.maxPage.released = Math.ceil(response.total / this.entriesPerPage.released);

					// set template values
					this.currentPageNodeReleased.innerHTML = Math.min(this.currentPage.released, this.maxPage.released);
					this.maxPageNodeReleased.innerHTML = this.maxPage.released;
				})
			);
		},
		
		updateViewableList: function() {
			// empty list
			DomConstruct.empty(this.viewableItemListNode);

			this.AJAXRequest("widget_released_entries", "getViewableListContent", {
					start:					(this.currentPage.viewable - 1) * this.entriesPerPage.viewable,
					numEntries:				this.entriesPerPage.viewable
				},
				Lang.hitch(this, function(response) {
					// fill list
					dojo.forEach(response.items, Lang.hitch(this, function(item, index, arr) {

						// create list entries
						var rowNode = DomConstruct.create("div", {
							className:		(index % 2 == 0) ? "row_even even_sep_search" : "row_odd odd_sep_search"
						}, this.viewableItemListNode, "last");

							var firstColumnNode = DomConstruct.create("div", {
								className:		"column_280"
							}, rowNode, "last");

								var pNode = DomConstruct.create("p", {}, firstColumnNode, "last");

									var aNode = DomConstruct.create("a", {
										"id":		"listItem" + item.itemId,
										className:	"stack_link",
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
								className:		"column_260"
							}, rowNode, "last");

								DomConstruct.create("p", {
									innerHTML:		item.releasedFrom
								}, fourthColumnNode, "last");
							
							DomConstruct.create("div", {
								className:		"clear"
							}, rowNode, "last");
						
						require(["commsy/popups/ClickDetailPopup"], Lang.hitch(this, function(ClickPopup) {
							var handler = new ClickPopup();
							handler.init(aNode, { iid: item.itemId, module: item.module, contextId: this.itemId, versionId: item.versionId });
						}));
					}));

					// update max page
					this.maxPage.viewable = Math.ceil(response.total / this.entriesPerPage.viewable);

					// set template values
					this.currentPageNodeViewable.innerHTML = Math.min(this.currentPage.viewable, this.maxPage.viewable);
					this.maxPageNodeViewable.innerHTML = this.maxPage.viewable;
				})
			);
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickPaging20Released: function(event) {
			this.entriesPerPage.released = 20;
			this.currentPage.released = 1;
			this.paging20Released.innerHTML = "<strong>20</strong>";
			this.paging50Released.innerHTML = "50";
			this.updateReleasedList();
		},

		onClickPaging50Released: function(event) {
			this.entriesPerPage.released = 50;
			this.currentPage.released = 1;
			this.paging20Released.innerHTML = "20";
			this.paging50Released.innerHTML = "<strong>50</strong>";
			this.updateReleasedList();
		},

		onClickPagingFirstReleased: function(event) {
			if (this.currentPage.released > 1) this.currentPage.released = 1;
			this.updateReleasedList();
		},

		onClickPagingPrevReleased: function(event) {
			if (this.currentPage.released > 1) this.currentPage.released--;
			this.updateReleasedList();
		},

		onClickPagingNextReleased: function(event) {
			if (this.currentPage.released < this.maxPage.released) this.currentPage.released++;
			this.updateReleasedList();
		},

		onClickPagingLastReleased: function(event) {
			if (this.currentPage.released < this.maxPage.released) this.currentPage.released = this.maxPage.released;
			this.updateReleasedList();
		},
		
		onClickPaging20Viewable: function(event) {
			this.entriesPerPage.viewable = 20;
			this.currentPage.viewable = 1;
			this.paging20Viewable.innerHTML = "<strong>20</strong>";
			this.paging50Viewable.innerHTML = "50";
			this.updateViewableList();
		},

		onClickPaging50Viewable: function(event) {
			this.entriesPerPage.viewable = 50;
			this.currentPage.viewable = 1;
			this.paging20Viewable.innerHTML = "20";
			this.paging50Viewable.innerHTML = "<strong>50</strong>";
			this.updateViewableList();
		},

		onClickPagingFirstViewable: function(event) {
			if (this.currentPage.viewable > 1) this.currentPage.viewable = 1;
			this.updateViewableList();
		},

		onClickPagingPrevViewable: function(event) {
			if (this.currentPage.viewable > 1) this.currentPage.viewable--;
			this.updateViewableList();
		},

		onClickPagingNextViewable: function(event) {
			if (this.currentPage.viewable < this.maxPage.viewable) this.currentPage.viewable++;
			this.updateViewableList();
		},

		onClickPagingLastViewable: function(event) {
			if (this.currentPage.viewable < this.maxPage.viewable) this.currentPage.viewable = this.maxPage.viewable;
			this.updateViewableList();
		},
		
		onClickListEntry: function(aNode) {
			var customObject = this.getAttrAsObject(aNode, "data-custom");
					
			this.reload(customObject.iid, customObject.module, customObject.cid);
		}
	});
});