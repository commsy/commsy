define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(TogglePopupHandler, {
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "clipboard";
			
			this.features = [ ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_clipboard_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_clipboard_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			// setup clipboard functions
			require(["commsy/Clipboard"], Lang.hitch(this, function(Clipboard) {
				var clipboard = new Clipboard();
				clipboard.init(this.cid, this.from_php.template.tpl_path);
			}));
		},
		
		onPopupSubmit: function(customObject) {
			// this popup will not be submitted
		},
		
		onPopupSubmitSuccess: function(item_id) {
		}
	});
});