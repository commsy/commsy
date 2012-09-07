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
		constructor: function() {

		},

		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "portfolioItem";

			this.features = [ ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
		},

		onPopupSubmit: function(customObject) {
			// setup data to send via ajax
			var search = {
				tabs: [
				],
				nodeLists: [
				    { query: query("input[name^='form_data']", this.contentNode) },
				    { query: query("textarea[name^='form_data']", this.contentNode) }
				]
			};

			this.submit(search, { part: customObject.part });
		},

		onPopupSubmitSuccess: function(item_id) {
			Topic.publish("updatePortfolios", { itemId: item_id });
			this.close();
		}
	});
});