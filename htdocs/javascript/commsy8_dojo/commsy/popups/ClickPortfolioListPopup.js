define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.portfolioId;
			this.module = "portfolioList";
			
			this.setInitData({
				row:		customObject.row,
				column:		customObject.column,
				itemIds:	customObject.itemIds
			});
			
			this.features = [];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			var aDetailNodes = Query("a.openDetailPopup", this.contentNode);
			
			dojo.forEach(aDetailNodes, Lang.hitch(this, function(node, index, arr) {
				require(["commsy/popups/ClickDetailPopup"], Lang.hitch(this, function(ClickPopup) {
					var handler = new ClickPopup();
					var customObject = this.getAttrAsObject(node, "data-custom");
					
					handler.init(node, customObject);
					
					On(node, "click", Lang.hitch(this, function(event) {
						this.close();
					}));
				}));
			}));
			
			var aCreateAnnotationNode = Query("a#portfolioListCreateAnnotation")[0];
			if (aCreateAnnotationNode) {
				require(["commsy/popups/ClickAnnotationPopup"], Lang.hitch(this, function(ClickPopup) {
					var handler = new ClickPopup();
					var customObject = this.getAttrAsObject(aCreateAnnotationNode, "data-custom");
					
					customObject.portfolioRow = this.initData.row;
					customObject.portfolioColumn = this.initData.column;
					
					handler.init(aCreateAnnotationNode, customObject);
					
					On(aCreateAnnotationNode, "click", Lang.hitch(this, function(event) {
						this.close();
					}));
					
				}));
			}
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