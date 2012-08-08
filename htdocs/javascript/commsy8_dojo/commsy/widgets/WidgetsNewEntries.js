define([	"dojo/_base/declare",
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
		
		currentPage:		1,
		maxPage:			1,
		entriesPerPage:		10,
		items:				[],
		
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
			this.updateList();
		},
		
		updateList: function() {
			// empty list
			DomConstruct.empty(this.itemListNode);

			this.AJAXRequest("widget_new_entries", "getListContent", {
					start:					(this.currentPage - 1) * this.entriesPerPage,
					numEntries:				this.entriesPerPage
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
					}));

					// update max page
					this.maxPage = Math.ceil(response.total / this.entriesPerPage);

					// set template values
					this.currentPageNode.innerHTML = Math.min(this.currentPage, this.maxPage);
					this.maxPageNode.innerHTML = this.maxPage;
				})
			);
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickListEntry: function(aNode) {
			var customObject = this.getAttrAsObject(aNode, "data-custom");
					
			this.reload(customObject.iid, customObject.module, customObject.cid);
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
		}
	});
});