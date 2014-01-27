define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/_base/fx",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style"], function(declare, BaseClass, On, Lang, FX, Query, DomClass, DomAttr, DomConstruct, DomStyle) {
	return declare(BaseClass, {
		constructor: function(args) {
			args = args || {};
			declare.safeMixin(this, args);
		},
		
		setup: function(nodeList) {
			// get custom attribute data for all nodes
			dojo.forEach(nodeList, Lang.hitch(this, function(node, index, arr) {
				var customObject = this.getAttrAsObject(node, "data-custom");
				
				// register click
				On(node, "click", Lang.hitch(this, function(event) {
					// call function if exist
					if(this[customObject.action]) Lang.hitch(this, this[customObject.action](customObject));
				}));
			}));
		},
		
		addToClipboard: function(customObject) {
			var itemId = customObject.iid;
			
			// send ajax requets
			this.AJAXRequest("actions", "addToClipboard", { itemId: itemId }, Lang.hitch(this, function(response) {
				// item was added, update number of items in clipboard
				var ClipboardButtonNode = Query("a#tm_clipboard")[0];
				
				if(ClipboardButtonNode) {
					var spanNode = Query("span#tm_clipboard_copies")[0];
					
					if(!spanNode) {
						// create span
						spanNode = DomConstruct.create("span", {
							"id":		"tm_clipboard_copies",
							innerHTML:	0
						}, ClipboardButtonNode, "after");
					}
					
					// increase count
					DomAttr.set(spanNode, "innerHTML", parseInt(DomAttr.get(spanNode, "innerHTML")) + 1);
				}
			}));
		},
		
		versionMakeNew: function(customObject) {
			var itemId = customObject.iid;
			var versionID = customObject.vid;

			// send ajax requets
			this.AJAXRequest("actions", "versionMakeNew", { itemId: itemId, versionID: versionID }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		},
		
		exportToWordpress: function(customObject) {
			var itemId = customObject.iid;

			// send ajax requets
			this.AJAXRequest("actions", "exportToWordpress", { itemId: itemId }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		},
		
		exportToWiki: function(customObject) {
			var itemId = customObject.iid;

			// send ajax requets
			this.AJAXRequest("actions", "exportToWiki", { itemId: itemId }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		}
	});
});