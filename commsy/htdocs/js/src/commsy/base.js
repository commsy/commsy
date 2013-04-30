define([	"dojo/_base/declare",
        	"dojo/_base/xhr",
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
        	"dojo/NodeList-traverse"], function(declare, xhr, ioQuery, DojoxFX, Scroll, Query, DomAttr, domConstruct, widgetManager, Window, domGeometry, Lang) {
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
		
		request: function(fct, action, data) {
			// execute a HTTP POST request
			var args = {
				url:		"commsy.php?cid=" + this.uri_object.cid + "&mod=ajax&fct=" + fct + "&action=" + action,
				headers:	{
							"Content-Type":		"application/json; charset=utf-8",
							"Accept":			"application/json"
				},
				postData:	dojo.toJson(data),
				handleAs:	"json",
				error:		Lang.hitch(this, function(errorMessage, ioargs) {
					/************************************************************************************
					 * A fatal error occured while performing the ajax request, maybe something went wrong
					 * on php side or while transporting data. Show error message in console and setup a
					 * user-friendly error widget
					************************************************************************************/
					
					// ignore the case of status code 0 - aborted xhr requests(search auto-completion, etc.)
					if (ioargs.xhr.status !== 0) {
						if (this.from_php.dev.xhr_error_reporting && this.from_php.dev.xhr_error_reporting === true) {
							/*
							 * we overwrite all success and error handler, so failing to send
							 * this request will not lead into a recursive loop
							 */
							this.AJAXRequest("actions", "sendXHRErrorReporting", { ioargs: ioargs, error: errorMessage },
								function() {},
								function() {},
								false,
								{ error: function() {} }
							);
						}
					}
				})
			};
			
			return xhr.post(args);
		},

		AJAXRequest: function(fct, action, data, callback, error_callback, sync, mixin) {
			callback = callback || function(response) {};
			error_callback = error_callback || function(response) {};
			sync = sync || false;
			mixin = mixin || {};

			// execute a HTTP POST request
			var args = {
				url:		"commsy.php?cid=" + this.uri_object.cid + "&mod=ajax&fct=" + fct + "&action=" + action,
				headers:	{
							"Content-Type":		"application/json; charset=utf-8",
							"Accept":			"application/json"
				},
				postData:	dojo.toJson(data),
				handleAs:	"json",
				sync:		sync,
				error:		Lang.hitch(this, function(errorMessage, ioargs) {
					/************************************************************************************
					 * A fatal error occured while performing the ajax request, maybe something went wrong
					 * on php side or while transporting data. Show error message in console and setup a
					 * user-friendly error widget
					************************************************************************************/
					
					// ignore the case of status code 0 - aborted xhr requests(search auto-completion, etc.)
					if (ioargs.xhr.status !== 0) {
						if (this.from_php.dev.xhr_error_reporting && this.from_php.dev.xhr_error_reporting === true) {
							/*
							 * we overwrite all success and error handler, so failing to send
							 * this request will not lead into a recursive loop
							 */
							this.AJAXRequest("actions", "sendXHRErrorReporting", { ioargs: ioargs, error: errorMessage },
								function() {},
								function() {},
								false,
								{ error: function() {} }
							);
						}
						
						/*
						// destroy any existing loading screen
						this.destroyLoading();
						
						
						// setup error dialog
						require(["commsy/widgets/ErrorDialog"], function(ErrorDialog) {
							var dialog = new ErrorDialog({});
							dialog.show();
						});
						*/
					}
				})
			};
			
			declare.safeMixin(args, mixin);
			var request = xhr.post(args);

			// setup deferred
			request.then(function(response) {
				if(response.status === "success") {
					callback(response.data);
				} else {
					error_callback(response);
				}
			});
			
			return request;
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
			
			var vs = Window.getBox();
			var nodePosition = domGeometry.position(node);
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