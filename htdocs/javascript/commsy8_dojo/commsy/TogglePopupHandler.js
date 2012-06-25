define([	"dojo/_base/declare",
        	"commsy/PopupHandler",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-construct",
        	"dojo/dom-attr"], function(declare, PopupHandler, on, topic, lang, query, dom_class, DomConstruct, dom_attr) {
	return declare(PopupHandler, {
		is_loaded:				false,
		is_open:				false,
		popup_button_node:		null,
		
		constructor: function(args) {
			this.fct = "popup";
		},
		
		registerPopupClick: function() {
			on(this.popup_button_node, "click", lang.hitch(this, function(event) {
				if(this.is_loaded === false) {
					this.setupLoading();
					
					// setup ajax request for getting html
					this.AJAXRequest("popup", "getHTML", { module: this.module} , lang.hitch(this, function(html) {		
						// append html to node
						DomConstruct.place(html, this.contentNode, "last");
						
						this.setupTabs();
						
						this.setupFeatures();
						
						this.setupSpecific();
						
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
				
				this.onTogglePopup();
				
				event.preventDefault();
			}));
		},
		
		close: function() {
			this.inherited(arguments);
			
			this.onTogglePopup();
			
			// remove popup html from dom
			//jQuery('div#popup_wrapper').remove();
			
			
			/*
			jQuery.each(handle.objects, function() {
				this.trigger.removeClass(this.active_class);
				this.menu.css('display', 'none');
			});*/

			//handle.isExpanded = false;
		}
	});
});