define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/NodeList-traverse"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On, Topic) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.module = "rss";
			this.contextId = customObject.contextId;

			this.features = [ ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
			// register delete buttons
			var deleteButtonNodes = query("input.deleteButton", this.contentNode);
			On(deleteButtonNodes, "click", lang.hitch(this, function(event) {
				this.onDeleteRssFeed(event.target);
			}));
			
			// register create button
			var createButtonNode = query("input#rssCreateButton", this.contentNode)[0];
			if (createButtonNode) {
				On(createButtonNode, "click", lang.hitch(this, function(event) {
					var title = domAttr.get(query("input#rssNewTitle", this.contentNode)[0], "value");
					var address = domAttr.get(query("input#rssNewAddress", this.contentNode)[0], "value")
					
					var divWrapperNode = domConstruct.create("div", {
						className:		"rowWrapper"
					}, query("div#rssList", this.contentNode)[0], "last");
					
						domConstruct.create("input", {
							type:		"checkbox",
							name:		"form_data[feeds]",
							value:		"feed_" + (query("div#rssList div", this.contentNode).length - 1),
							checked:	"checked"
						}, divWrapperNode, "last");
						
						domConstruct.create("input", {
							type:		"text",
							name:		"form_data[feedsName]",
							size:		15,
							value:		title
						}, divWrapperNode, "last");
						
						domConstruct.create("input", {
							type:		"text",
							name:		"form_data[feedsAddress]",
							size:		30,
							value:		address
						}, divWrapperNode, "last");
						
						var deleteButtonNodeNew = domConstruct.create("input", {
							type:		"button",
							className:	"deleteButton",
							value:		domAttr.get(query("div.translationDelete", this.contentNode)[0], "innerHTML")
						}, divWrapperNode, "last");
					
					On(deleteButtonNodeNew, "click", lang.hitch(this, function(event) {
						this.onDeleteRssFeed(deleteButtonNodeNew);
					}));
				}));
			}
		},
		
		onDeleteRssFeed: function(buttonNode) {
			var wrapperNode = new dojo.NodeList(buttonNode).parent("div.rowWrapper")[0];
			if (wrapperNode) {
				domConstruct.destroy(wrapperNode);
			}
		},

		onPopupSubmit: function(customObject) {

			// setup data to send via ajax
			var search = {
				tabs: [
				],
				nodeLists: [
				    { query: query("input[name='form_data[feeds]']", this.contentNode), group: "feeds" },
				    { query: query("input[name='form_data[feedsName]']", this.contentNode), group: "feedsName" },
				    { query: query("input[name='form_data[feedsAddress]']", this.contentNode), group: "feedsAddress" }
				]
			};

			this.submit(search);
		},

		onPopupSubmitSuccess: function(item_id) {
			this.close();
			
			Topic.publish("refreshRssList", {});
		}
	});
});