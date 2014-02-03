define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/_base/Deferred",
        	"dojo/promise/all"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang, Deferred, All) {
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
			this.AJAXRequest("widgets", action, {},
				Lang.hitch(this, function(response) {
					// we recieved a list of widgets to display
					this.widgetArray = this.widgetArray.concat(response.displayConfig);
					
					// load widgets
					this.loadWidgets();
				})
			);
		},
		
		loadWidgets: function() {
			dojo.forEach(this.widgetArray, Lang.hitch(this, function(widget, index, arr) {
				// determ name of widget
				var split = widget.split("_");
				var widgetPath = "widgets/" + this.ucFirst(this.module) + this.ucFirst(split[1]);
				
				if(split[3] && split[3] == "preferences") widgetPath += "Preferences";
				
				this.loadWidget(widgetPath);
				
				
				/*
				// check if widget exists
				if (Lang.exists("widgets." + widgetName)) {
					
					console.log(widgetName + " found");
				}
				*/
			}));
		},
		
		loadWidgetsManual: function(widgetArray) {
			var promiseList = [];
			
			dojo.forEach(widgetArray, Lang.hitch(this, function(widget, index, arr) {
				promiseList.push(this.loadWidget(widget));
			}));
			
			return All(promiseList);
		},
		
		loadWidget: function(widgetPath, mixin) {
			mixin = mixin || {};
			
			var deferred = new Deferred();
			
			require(["commsy/" + widgetPath], Lang.hitch(this, function(widgetObject) {
				// get template
				this.AJAXRequest("widgets", "getHTMLForWidget", { widgetPath: widgetPath },
					Lang.hitch(this, function(templateString) {
						var params = {
							templateString:		templateString,
							widgetHandler:		this
						};
						declare.safeMixin(params, mixin);
						
						// init widget
						var widgetHandler = new widgetObject(params);
						widgetHandler.startup();
						
						this.widgetHandles.push({ path: widgetPath, handler: widgetHandler });
						
						deferred.resolve({ handle: widgetHandler });
					})
				)
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