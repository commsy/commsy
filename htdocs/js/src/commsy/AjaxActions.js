define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/_base/fx",
        	"dojo/query",
        	"commsy/request",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style"], function(declare, BaseClass, On, lang, FX, Query, request, DomClass, DomAttr, DomConstruct, DomStyle) {
	return declare(BaseClass, {
		constructor: function(args) {
			args = args || {};
			declare.safeMixin(this, args);
		},
		
		setup: function(nodeList) {
			// get custom attribute data for all nodes
			dojo.forEach(nodeList, lang.hitch(this, function(node, index, arr) {
				var customObject = this.getAttrAsObject(node, "data-custom");
				
				// register click
				On(node, "click", lang.hitch(this, function(event) {
					// call function if exist
					if(this[customObject.action]) lang.hitch(this, this[customObject.action](customObject));
				}));
			}));
		},
		
		addToClipboard: function(customObject) {
			var itemId = customObject.iid;
			
			// send ajax requets
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'actions',
					action:	'addToClipboard'
				},
				data: {
					itemId: itemId
				}
			}).then(
				lang.hitch(this, function(response) {
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
						var numberOfCopies = parseInt(DomAttr.get(spanNode, "innerHTML"));
						if (isNaN(numberOfCopies)) {
   						numberOfCopies = 0;
						}
						numberOfCopies++;
						DomAttr.set(spanNode, "innerHTML", numberOfCopies);
					}
				})
			);
		},
		
		versionMakeNew: function(customObject) {
			var itemId = customObject.iid;
			var versionID = customObject.vid;

			// send ajax requets
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'actions',
					action:	'versionMakeNew'
				},
				data: {
					itemId:		itemId,
					versionID:	versionID
				}
			}).then(
				lang.hitch(this, function(response) {
					this.reload(itemId);
				})
			);
		},
		
		exportToWordpress: function(customObject) {
			var itemId = customObject.iid;

			// send ajax requets
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'actions',
					action:	'exportToWordpress'
				},
				data: {
					itemId:		itemId
				}
			}).then(
				lang.hitch(this, function(response) {
					this.reload(itemId);
				})
			);
		},
		
		exportToWiki: function(customObject) {
			var itemId = customObject.iid;

			// send ajax requets
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'actions',
					action:	'exportToWiki'
				},
				data: {
					itemId:		itemId
				}
			}).then(
				lang.hitch(this, function(response) {
					this.reload(itemId);
				})
			);
		}
	});
});