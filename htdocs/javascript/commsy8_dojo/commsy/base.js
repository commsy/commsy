define([	"dojo/_base/declare",
        	"dojo/_base/xhr",
        	"dojo/io-query",
        	"dojox/fx",
        	"dojox/fx/scroll",
        	"dojo/query",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/window",
        	"dojo/dom-geometry"], function(declare, xhr, ioQuery, DojoxFX, Scroll, Query, DomAttr, domConstruct, Window, domGeometry) {	
	return declare(null, {
		uri_object:		null,
		from_php:		null,
		
		constructor: function(args) {
			// set query object
			this.uri_object = ioQuery.queryToObject(dojo.doc.location.search.substr((dojo.doc.location.search[0] === "?" ? 1: 0)));
			
			// set from php object
			this.from_php = dojo.fromJson(from_php);
			
		},
		
		replaceOrSetURIParam: function(key, value) {
			var object = this.uri_object;
			object[key] = value;
			return object;
		},
		
		replaceOrSetAnchor: function(anchor) {
			var splitLocation = location.href.split("#");
			
			return splitLocation[0] + anchor;
		},
		
		setupLoading: function() {
			var loadingScreenDiv = Query("#loadingScreen")[0];

			if (!loadingScreenDiv) {
				// TODO: add invisible screen layer, to prevent closing, before fully loaded
				var loadingScreenDiv = domConstruct.create("div", {
					"id":		"loadingScreen"
				}, Query("body")[0], "first");

					var loadingScreenInner = domConstruct.create("div", {
						"id":		"loadingScreenInner"
					}, loadingScreenDiv, "last");

						domConstruct.create("h2", {
							innerHTML:		"Loading..."
						}, loadingScreenInner, "last");

						domConstruct.create("img", {
							src:		this.from_php.template.tpl_path + "img/ajax_loader_big.gif"
						}, loadingScreenInner, "last");
			}
		},

		destroyLoading: function() {
			var loadingScreenDiv = Query("#loadingScreen")[0];

			if(loadingScreenDiv) {
				dojo.fadeOut({
					node:		loadingScreenDiv,
					duration:	1000,
					onEnd:		function() {
						domConstruct.destroy(loadingScreenDiv);
					}
				}).play();
			}
		},
		
		AJAXRequest: function(fct, action, data, callback, error_callback, sync, mixin) {
			callback = callback || function(response) {};
			error_callback = error_callback || function(response) {};
			sync = sync || false;
			mixin = mixin || {};
			
			// execute a HTTP POST request
			var request = xhr.post({
				url:		"commsy.php?cid=" + this.uri_object.cid + "&mod=ajax&fct=" + fct + "&action=" + action,
				headers:	{
							"Content-Type":		"application/json; charset=utf-8",
							"Accept":			"application/json"
				},
				postData:	dojo.toJson(data),
				handleAs:	"json",
				sync:		sync
			});
			
			declare.safeMixin(request, mixin);
			
			// setup deferred
			request.then(function(response) {
				if(response.status === "success") {
					callback(response.data);
				} else {
					error_callback(response);
				}
				
			}, function(errorMessage) {
				console.error(errorMessage);
			});
		},
		
		scrollToNodeAnimated: function(node) {
			var vs = Window.getBox();
			var nodePosition = domGeometry.position(node);
			if(nodePosition.y > vs.t + vs.h || nodePosition.y + nodePosition.h < vs.t) {
				DojoxFX.smoothScroll({
					node:		node,
					win:		window,
					duration:	400
				}).play();
			}
		},
		
		ucFirst: function(string) {
		    return string.charAt(0).toUpperCase() + string.slice(1);
		},
		
		getAttrAsObject: function(node, attrName) {
			if(node) {
				var attribute = DomAttr.get(node, attrName);
				
				if(attribute) return dojo.fromJson("{" + attribute + "}");
			}
			
			return {};
		}
	});
});