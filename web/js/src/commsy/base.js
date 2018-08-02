define([	"dojo/_base/declare",
        	"dojo/io-query",
        	"dojox/fx",
        	"dojox/fx/scroll",
        	"dojo/query",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"commsy/widgets/WidgetManager",
        	"dojo/window",
        	"dojo/dom-geometry",
        	"dojo/_base/lang",
        	"dojo/NodeList-traverse",
        	"dojo/_base/xhr"], function(declare, ioQuery, DojoxFX, Scroll, Query, DomAttr, domConstruct, widgetManager, Window, domGeometry, lang) {
	return declare(null, {
		// static
		baseStatics: {
			widgetManager:		null
		},
		
		constructor: function(args) {
			// set query object
			this.uri_object = ioQuery.queryToObject(dojo.doc.location.search.substr((dojo.doc.location.search[0] === "?" ? 1: 0)));

			// set from php object
			this.from_php = dojo.fromJson(from_php);
			
			if ( this.baseStatics.widgetManager === null )
			{
				this.baseStatics.widgetManager = new widgetManager( { base: this } );
				this.baseStatics.widgetManager.Init();
			}
		},
		
		getWidgetManager: function() {
			return this.baseStatics.widgetManager;
		},

		removeURIParam: function(regex) {
			var object = this.uri_object;
			for (var key in object) {
				if (key.search(regex) >= 0) {
					delete object[key];
				}
			}

			return object;
		},

		replaceOrSetURIParam: function(key, value) {
			var object = this.uri_object;
			object[key] = value;
			return object;
		},
		
		removeOrSetURIParam: function(key, value) {
			var object = this.uri_object;
			if(object[key]){
				delete object[key];
			} else {
				object[key] = value;
			}
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
				}, Query("div#popup_uploader")[0], "first");

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

		scrollToNodeAnimated: function(node)
		{
			/*
			 * try to find a parent node with class "scrollPopup"
			 * if not given use main window context as container
			 */
			var container = new dojo.NodeList(node).closest(".scrollPopup")[0];
			
			if ( container == null )
			{
				container = window;
			}

			if(DomAttr.get(node, 'id') == 'popup_wrapper') {
				var nodePosition = domGeometry.position(dojo.query('#popup_frame', node)[0]);
			} else {
				var nodePosition = domGeometry.position(node);
			}
			
			var vs = Window.getBox();

			if(nodePosition.y > vs.t + vs.h || nodePosition.y + nodePosition.h < vs.t) {
				DojoxFX.smoothScroll({
					node:		node,
					win:		container,
					duration:	400
				}).play();
			}
		},

		ucFirst: function(string) {
		    return string.charAt(0).toUpperCase() + string.slice(1);
		},

		getAttrAsObject: function(node, attrName) {
			if(node)
			{
				var attribute = DomAttr.get(node, attrName);
				
				if ( attribute )
				{
					return dojo.fromJson("{" + attribute + "}");
				}
			}

			return {};
		},

		backlink: function() {
			if (typeof this.uri_object.commsy_bar_backlink != "undefined") {
				var numChildPopups = Query("body>div#popup_wrapper:first").length;
				numChildPopups += Query("body>div[class^='portfolio']").length;

				if (numChildPopups == 0) {
					var backlink = decodeURIComponent(this.uri_object.commsy_bar_backlink);
					window.location = backlink;
				}
			}

			return false;
		},
		
		reload: function(item_id, module, cid, anchor) {
            module = module || null;
            cid = cid || null;
            anchor = anchor || null;

            // page reload
            if ( !cid )
            {
            	cid = this.uri_object.cid;
            }
            
            if ( !module )
            {
            	module = this.uri_object.mod;
            }

            if ( module === "home" )
            {
                module = this.module;
            }
            
            location.href = "commsy.php?cid=" + cid + "&mod=" + module + "&fct=detail&iid=" + item_id + ( anchor ? anchor : "");
            
            if ( anchor )
            {
            	location.reload();
            }
        }
	});
});