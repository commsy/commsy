define([	"dojo/_base/declare",
        	"commsy/base",
        	"dijit/TooltipDialog",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on",
        	"dojo/mouse",
        	"dijit/popup",
        	"dojo/dom-style",
        	"dojo/_base/lang",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, TooltipDialog, DomAttr, Query, On, Mouse, Popup, DomStyle, Lang) {
	return declare(BaseClass, {
		display:			false,
		fadeInAnimation:	null,
		fadeOutAnimation:	null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			var contentNode = Query("div.tooltip:first", node.parentNode.parentNode)[0];
			
			if(contentNode) {
				var position = "bottom";
				
				var customObject = this.getAttrAsObject(contentNode, "data-custom");
				if (customObject) {
					if (customObject.position) position = customObject.position;
				}
				
				DomStyle.set(contentNode, "opacity", 0);
				DomStyle.set(contentNode, "display", "block");
				DomStyle.set(contentNode, "zIndex", -1);
				
				if (position === "right") {
					DomStyle.set(contentNode, "marginLeft", "100px");
				}
				
				// setup tooltip dialog
				var tooltipDialog = new TooltipDialog({
					content:		DomAttr.get(contentNode, "innerHTML"),
					className:		DomAttr.get(contentNode, "class")
				});
				
				// set animations
				this.fadeInAnimation = dojo.animateProperty({
					node:		contentNode,
					properties: {
						opacity: 1
					},
					beforeBegin:	Lang.hitch(this, function(event) {
						DomStyle.set(contentNode, "zIndex", 0);
						Popup.open({
							popup:	tooltipDialog,
							around:	node
						});
						this.display = true;
					})
				});
				this.fadeOutAnimation = dojo.animateProperty({
					node:		contentNode,
					properties: {
						opacity:	0
					},
					onEnd:			Lang.hitch(this, function(event) {
						Popup.close();
						DomStyle.set(contentNode, "zIndex", -1);
						this.display = false;
					})
				});
				
				// mouse enters trigger
				On(node, Mouse.enter, Lang.hitch(this, function(event) {
					if(this.display === false) {
						this.fadeInAnimation.play();
					}
				}));
				
				// mouse leaves trigger
				On(node, Mouse.leave, Lang.hitch(this, function(event) {
					if(this.display === true) {
						this.fadeOutAnimation.play();
					}
				}));
				
				// mouse enters content
				On(contentNode, Mouse.enter, Lang.hitch(this, function() {
					if(this.display === true) this.fadeOutAnimation.gotoPercent(0);
				}));
				
				// mouse leaves content
				On(contentNode, Mouse.leave, Lang.hitch(this, function() {
					if(this.display === true) this.fadeOutAnimation.play();
				}));
			}
		}
	});
});