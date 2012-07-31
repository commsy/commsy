define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "detail";
			this.version_id = customObject.vid || null;
			this.contextId = customObject.contextId;
			
			this.ajaxHTMLSource = "detail_popup";
			
			this.features = [];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			// reinvoke ActionExpander
			var actors = query(	"div.item_actions a.edit," +
								"div.item_actions a.detail," +
								"div.item_actions a.workflow," +
								"div.item_actions a.linked," + 
								"div.item_actions a.annotations," +
								"div.item_actions a.versions");
				
			require(["commsy/ActionExpander"], function(ActionExpander) {
				var handler = new ActionExpander();
				handler.setup(actors);
			});
		},
		
		onPopupSubmit: function(customObject) {
			// setup data to send via ajax
			var search = {
				tabs: [],
				nodeLists: []
			};
			
			
			this.submit(search, { version_id: this.version_id });
		},
		
		onPopupSubmitSuccess: function(response) {
		},
	});
});