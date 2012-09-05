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
			this.user_id = customObject.user_id;
			this.content_id = customObject.context_id;
			this.action = customObject.action;
			this.module = "userParticipation";
			
			this.features = [];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			var user_id = customObject.user_id;
			var context_id = customObject.context_id;
			var action = customObject.action;
			
			// setup data to send via ajax
			var search = {
				tabs: [],
				nodeLists: []
			};
			
			this.submit(search,  { part: part, user_id: user_id, context_id: context_id, action: action });
		},
		
		onPopupSubmitSuccess: function(item_id) {
			location.href = "commsy.php?cid=" + item_id;
		}
	});
});