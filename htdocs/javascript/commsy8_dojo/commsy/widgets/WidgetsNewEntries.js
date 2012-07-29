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
		
		currentPage:		1,
		maxPage:			1,
		entriesPerPage:		20,
		search:				"",
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
			this.AJAXRequest("widget_new_entries", "getListContent", { },
				Lang.hitch(this, function(response) {
					// save items
					this.items = response.items;
					
					this.maxPage = this.items.length;
					
					// update list
					this.updateList();
				})
			);
		},
		
		updateList: function() {
			// empty list
			DomConstruct.empty(this.itemList);
			
			// fill list
			var numFiltered = 0;
			var start = (this.currentPage - 1) * this.entriesPerPage;
			dojo.forEach(this.items, Lang.hitch(this, function(item, index, arr) {
				
				var skip = false;
				// filter by search word
				if (this.search) {
					if (item.title.toLowerCase().indexOf(this.search.toLowerCase()) == -1) {
						skip = true;
					}
				}
				
				// limit entries per page
				if (index < start || index > start + this.entriesPerPage) skip = true;
				
				if (!skip) {
					// create list entries
					var liNode = DomConstruct.create("li", {
					}, this.itemList, "last");
					
						DomConstruct.create("img", {
							src:		this.from_php.template.tpl_path + "img/netnavigation/" + item.image.img,
							title:		item.image.text
						}, liNode, "last");
						
						var aNode = DomConstruct.create("a", {
							innerHTML:		item.title,
							href:			"#",
							className:		"open_popup"
						}, liNode, "last");
					
					DomAttr.set(aNode, "data-custom", "cid: " + item.contextId + ", iid: " + item.itemId + ", module: '" + item.module + "'");
					On(aNode, "click", Lang.hitch(this, function(event) {
						this.onClickListEntry(event.target);
					}));
					
					numFiltered++;
				}
			}));
			
			// update max page
			this.maxPage = Math.ceil(numFiltered / this.entriesPerPage);
			
			// set template values
			this.currentPageNode.innerHTML = this.currentPage;
			this.maxPageNode.innerHTML = this.maxPage;
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickListEntry: function(aNode) {
			var customObject = this.getAttrAsObject(aNode, "data-custom");
					
			this.reload(customObject.iid, customObject.module, customObject.cid);
		},
		
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
		}
	});
});