define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"StackWidget",
		
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
					// set the item list
					dojo.forEach(response.items, Lang.hitch(this, function(item, index, arr) {
						
						DomConstruct.create("li", {
							"innerHTML":		item.title
						}, this.itemList, "last");
					}));
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
		}
	});
});