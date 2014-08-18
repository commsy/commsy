define([	"dojo/_base/declare",
        	"commsy/PopupHandler",
        	"dojo/on",
        	"dojo/topic",
        	"commsy/request",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-construct",
        	"dojo/dom-attr"], function(declare, PopupHandler, on, topic, request, lang, query, dom_class, DomConstruct, dom_attr) {
	return declare(PopupHandler, {
		is_loaded:				false,
		is_open:				false,
		popup_button_node:		null,
		
		/* "static" */ statics: { togglePopups: [] },
		
		constructor: function(args) {
			this.fct = "popup";
		},
		
		registerPopupClick: function() {
			on(this.popup_button_node, "click", lang.hitch(this, function(event) {
				this.open();
				event.preventDefault();
			}));
		},
		
		open: function() {
			if(this.is_loaded === false) {
				this.setupLoading();
				
				this.statics.togglePopups.push(this);
				togglePopups = this.statics.togglePopups;
				
				// setup ajax request for getting html
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'popup',
						action:	'getHTML'
					},
					data: {
						module: this.module
					}
				}).then(lang.hitch(this, function(response) {
					// append html to node
					DomConstruct.place(response.data, this.contentNode, "last");
					
					this.setupTabs();
					this.setupFeatures();
					this.setupSpecific();
					this.onCreate();
					
					// register close
					on(query("a", this.contentNode)[0], "click", lang.hitch(this, function(event) {
						this.close();
						
						event.preventDefault();
					}));
					
					// register submit click
					on(query("input.submit", this.contentNode), "click", lang.hitch(this, function(event) {
						// get custom data object
						var customObject = this.getAttrAsObject(event.target, "data-custom");
						this.onPopupSubmit(customObject);
						
						event.preventDefault();
					}));
					
					this.destroyLoading();
				}));
				
				this.is_loaded = true;
			}
			
			this.is_open = !this.is_open;
			
			if (this.is_open) {
				// close all popups before open this
				noBacklink = true;
				/* temporary, until all widgets are migrated to current version */
				var widgetManager = this.getWidgetManager();
				widgetManager.CloseAllWidgets(this);
				/* ~temporary */
				noBacklink = false;
			} else {
				this.backlink();
			}

			this.onTogglePopup();
		},
		
		close: function() {
			this.inherited(arguments);

			this.onTogglePopup();
		}
	});
});