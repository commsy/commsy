define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"commsy/request",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/_base/Deferred",
        	"dojo/promise/all"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, request, On, lang, Deferred, All) {
	return declare(TogglePopupHandler, {		
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			
			this.widgetArray = [];
			this.widgetHandles = [];
			
			this.defWidgets = null;
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			// get configuration for this popup
			var action = "get" + this.ucFirst(this.module) + "Configuration";
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'widgets',
					action:	action
				}
			}).then(lang.hitch(this, function(response) {
				// we recieved a list of widgets to display
				this.widgetArray = this.widgetArray.concat(response.data.displayConfig);
				
				// load widgets
				this.loadWidgets();
			}));
		},
		
		loadWidgets: function() {
			dojo.forEach(this.widgetArray, lang.hitch(this, function(widget, index, arr) {
				// determ name of widget
				var split = widget.split("_");
				var widgetPath = "widgets/" + this.ucFirst(this.module) + this.ucFirst(split[1]);
				
				if(split[3] && split[3] == "preferences") widgetPath += "Preferences";
				
				this.loadWidget(widgetPath);
			}));
		},
		
		loadWidgetsManual: function(widgetArray) {
			var promiseList = [];
			
			dojo.forEach(widgetArray, lang.hitch(this, function(widget, index, arr) {
				promiseList.push(this.loadWidget(widget));
			}));
			
			return All(promiseList);
		},
		
		loadWidget: function(widgetPath, mixin) {
			mixin = mixin || {};
			
			var deferred = new Deferred();
			
			require(["commsy/" + widgetPath], lang.hitch(this, function(widgetObject) {
				// get template
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'widgets',
						action:	'getHTMLForWidget'
					},
					data: {
						widgetPath: widgetPath
					}
				}).then(lang.hitch(this, function(response) {
					var params = {
						templateString:		response.data,
						widgetHandler:		this
					};
					declare.safeMixin(params, mixin);
					
					// init widget
					var widgetHandler = new widgetObject(params);
					widgetHandler.startup();
					
					this.widgetHandles.push({ path: widgetPath, handler: widgetHandler });
					
					deferred.resolve({ handle: widgetHandler });
				
				}));
			}));
			
			return deferred;
		},
		
		getWidget: function(widgetPath) {
			var filtered = dojo.filter(this.widgetHandles, function(item, index, arr) {
				return item.path == widgetPath;
			});
			
			return filtered[0].handler || null;
		},
		
		onPopupSubmit: function(customObject) {
			// this popup will not be submitted
		},
		
		onPopupSubmitSuccess: function(item_id) {
		}
	});
});