define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, On) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"StackWidget",
		
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
			// get entries in my stack
			this.AJAXRequest("widget_stack", "getListContent", {},
				Lang.hitch(this, function(response) {
					
					// save items
					this.items = response.items;
					
					this.maxPage = this.items.length;
					
					// update list
					this.updateList();
				})
			);
			
			
			/*
			 * // Get a DOM node reference for the root of our widget
    var domNode = this.domNode;
 
    // Run any parent postCreate processes - can be done at any point
    this.inherited(arguments);
 
    // Set our DOM node's background color to white -
    // smoothes out the mouseenter/leave event animations
    domStyle.set(domNode, "backgroundColor", this.baseBackgroundColor);
    // Set up our mouseenter/leave events - using dijit/_WidgetBase's connect
    // means that our callback will execute with `this` set to our widget
    this.connect(domNode, "onmouseenter", function(e) {
        this._changeBackground(this.mouseBackgroundColor);
    });
    this.connect(domNode, "onmouseleave", function(e) {
        this._changeBackground(this.baseBackgroundColor);
    });
			 */
		},
		
		updateList: function() {
			// empty list
			DomConstruct.empty(this.itemList);
			
			// fill list
			var numFiltered = 0;
			dojo.forEach(this.items, Lang.hitch(this, function(item, index, arr) {
				
				var skip = false;
				// filter by search word
				if (this.search) {
					if (item.title.indexOf(this.search) == -1) {
						skip = true;
					}
				}
				
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
					
					DomAttr.set(aNode, "data-custom", "iid: " + item.itemId + ", module: '" + item.module + "'");
					On(aNode, "click", Lang.hitch(this, function(event) {
						this.onClickListEntry(event.target);
					}));
					
					numFiltered++;
				}
			}));
			
			// update max page
			this.maxPage = parseInt(numFiltered / this.entriesPerPage) + 1;
			
			// set template values
			this.currentPageNode.innerHTML = this.currentPage;
			this.maxPageNode.innerHTML = this.maxPage;
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickListEntry: function(aNode) {
			// reinvoke popup handling
			var customObject = this.getAttrAsObject(aNode, "data-custom");
			
			var module = customObject.module;
			
			require(["commsy/popups/Click" + this.ucFirst(module) + "Popup"], function(ClickPopup) {
				var handler = new ClickPopup(aNode, customObject);
			});
		},
		
		onClickPaging20: function(event) {
			this.entriesPerPage = 20;
			this.paging20.innerHTML = "<strong>20</strong>";
			this.paging50.innerHTML = "50";
		},
		
		onClickPaging50: function(event) {
			this.entriesPerPage = 50;
			this.paging20.innerHTML = "20";
			this.paging50.innerHTML = "<strong>50</strong>";
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
		
		onChangeSearch: function(event) {
			var searchWord = event.target.value;
			this.search = searchWord;
			this.updateList();
		}
	});
});