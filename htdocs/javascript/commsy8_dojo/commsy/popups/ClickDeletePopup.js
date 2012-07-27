define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		delType:	null,
		
		constructor: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "delete";
			this.delType = customObject.delType;
			this.delVersion = customObject.delVersion || {};
			this.version_id = customObject.vid;
			
			this.features = [];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
		},
		
		onPopupSubmit: function(customObject) {
			// setup data to send via ajax
			var search = {
				tabs: [],
				nodeLists: []
			};
			
			var delType = this.delType;
			if(customObject.del === "recurrence") delType = "date_recurrence";
			
			this.submit(search, { delType: this.delType, delVersion: this.delVersion, version_id: this.version_id });
		},
		
		onPopupSubmitSuccess: function(response) {
			this.close();
			
			if(response.redirectToIndex) {
				var cid = this.uri_object.cid;
				var module = this.uri_object.mod;
				location.href = "commsy.php?cid=" + cid + "&mod=" + module + "&fct=index";
			} else {
				if(typeof(this.version_id) != 'undefined'){
					this.reload(response.item_id+"&version_id="+this.version_id);
				} else {
					this.reload(response.item_id);
				}
			}
		},
	});
});