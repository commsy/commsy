define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/topic"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On, Topic) {
	return declare(ClickPopupHandler, {
		delType:	null,
		
		constructor: function() {
			this.delType = null;
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "delete";
			this.delType = customObject.delType;
			this.delVersion = customObject.delVersion || {};
			this.version_id = customObject.vid;
			this.contextId = customObject.contextId;
			
			if (customObject.portfolioId) {
				this.setInitData({
					portfolioId:		customObject.portfolioId
				});
			}
			
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
			
			//var delType = this.delType;
			if(customObject.del === "recurrence") this.delType = "date_recurrence";
			
			var send = {
				delType:			this.delType,
				delVersion:			this.delVersion,
				version_id:			this.version_id
			};
			
			if (this.initData.portfolioId) {
				declare.safeMixin(send, {
					portfolioId:		this.initData.portfolioId
				});
			}
			
			this.submit(search, send);
		},
		
		onPopupSubmitSuccess: function(response) {
			this.close();
			
			if (this.contextId) {
				if (this.initData.portfolioId) {
					Topic.publish("updatePortfolio", { portfolioId: this.initData.portfolioId });
					Topic.publish("openPortfolioList", { portfolioId: this.initData.portfolioId });
				} else {
					Topic.publish("newOwnRoomItem", {});		// this just updates the stack list at the moment
				}
			} else {
				if(response.redirectToIndex) {
					var cid = this.uri_object.cid;
					var module = this.uri_object.mod;
					location.href = "commsy.php?cid=" + cid + "&mod=" + module + "&fct=index";
				} else {
					this.reload(response.item_id);
				}
			}
		}
	});
});