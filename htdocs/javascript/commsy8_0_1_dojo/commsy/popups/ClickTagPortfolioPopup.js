define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/NodeList-traverse"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, DomAttr, DomStyle, On, Topic) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			//this.item_id = customObject.iid;
			this.module = customObject.module;
			this.tree = null;
			this.tagId = customObject.tagId;
			this.portfolioId = customObject.portfolioId;
			this.position = customObject.position;
			
			this.features = [ ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			require(["commsy/PortfolioTree"], Lang.hitch(this, function(PortfolioTree) {
				this.tree = new PortfolioTree({
					followUrl:		false,
					checkboxes:		false,
					room_id:		this.from_php.ownRoom.id,
					expanded:		false,
					item_id:		this.item_id,
					popup:			this
				});
				this.tree.setupTree(Query("div.tree", this.contentNode)[0], Lang.hitch(this, function(tree) {					
					/*On(tree.tree, "open", Lang.hitch(this, function(item, node) {
						this.tree.addCreateAndRenameToAllLabels();
					}));*/
				}));
			}));
		},
		
		onTagSelected: function(itemId) {
			this.AJAXRequest("portfolio", "updatePortfolioTag", {
				tagId:			itemId,
				portfolioId:	this.portfolioId,
				position:		this.position,
				oldTagId:		this.tagId
			}, Lang.hitch(this, function(response) {
				Topic.publish("updatePortfolio", { portfolioId: this.portfolioId });
				this.close();
			}));
		},
		
		onPopupSubmit: function(customObject) {
			if (customObject.action === "delete") {
				this.AJAXRequest("portfolio", "deletePortfolioTag", {
					tagId:			this.tagId,
					portfolioId:	this.portfolioId,
				}, Lang.hitch(this, function(response) {
					Topic.publish("updatePortfolio", { portfolioId: this.portfolioId });
					this.close();
				}));
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
		}
	});
});