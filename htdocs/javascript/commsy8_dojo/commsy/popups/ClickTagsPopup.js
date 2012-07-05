define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on",
        	"dojo/has",
        	"dojo/NodeList-traverse",
        	"dojo/_base/sniff"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, DomAttr, DomStyle, On, Has) {
	return declare(ClickPopupHandler, {
		constructor: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			//this.item_id = customObject.iid;
			this.module = "tags";
			this.tree= null;
			
			this.features = [ ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			require(["commsy/EditTree"], Lang.hitch(this, function(EditTree) {
				this.tree = new EditTree({
					followUrl:		false,
					checkboxes:		false,
					expanded:		(Has("ie") <= 8) ? false : true,
					item_id:		this.item_id
				});
				this.tree.setupTree(Query("div.tree", this.contentNode)[0]);
			}));
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			if(part === "add") {
				this.OnAddNewBuzzword();
			} else if(part == "merge") {
				this.OnMergeBuzzwords();
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
		},
	});
});