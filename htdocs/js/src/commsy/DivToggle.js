define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/query",
        	"dojo/dom-attr",
        	"dojo/dom-class",
        	"dojo/dom-style",
        	"dojo/_base/lang",
        	"dojo/fx",
        	"dojo/on"], function(declare, BaseClass, Query, DomAttr, DomClass, DomStyle, Lang, FX, On) {
	return declare(BaseClass, {
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function() {
			// get all a-tags with class "divToggle"
			var aNodes = Query("a.divToggle");
			
			dojo.forEach(aNodes, Lang.hitch(this, function(node, index, arr) {
				if (node) {
					// get the div node to toggle
					var customObject = this.getAttrAsObject(node, "data-custom");
					var toggleNode = Query("div#" + customObject.toggleId)[0];
					
					// register clicks
					if (toggleNode) {
						On(node, "click", Lang.hitch(this, function(event) {
							this.onClick(node, toggleNode);
						}));
					}
				}
			}));
		},
		
		onClick: function(triggerNode, toggleNode) {
			
			
			// get state of trigger
			if (DomClass.contains(toggleNode, "hidden")) {
				/* hidden */
				
				// set title of trigger node
				DomAttr.set(triggerNode, "title", this.from_php.translations.common_hide);
				// set new image and alt of img node
				var imgNode = Query("img", triggerNode)[0];
				if (imgNode) {
					DomAttr.set(imgNode, "src", this.from_php.template.tpl_path + "img/btn_close_rc.gif");
					DomAttr.set(imgNode, "alt", this.from_php.translations.common_hide);
				}
				
				DomClass.remove(toggleNode, "hidden");
				DomStyle.set(toggleNode, "height", "0px");
				
				
				// show div
				FX.wipeIn({
					node:		toggleNode
				}).play();
			} else {
				/* not hidden */
				
				// set title of trigger node
				DomAttr.set(triggerNode, "title", this.from_php.translations.common_show);
				
				// set new image and alt of img node
				var imgNode = Query("img", triggerNode)[0];
				if (imgNode) {
					DomAttr.set(imgNode, "src", this.from_php.template.tpl_path + "img/btn_open_rc.gif");
					DomAttr.set(imgNode, "alt", this.from_php.translations.common_show);
				}
				
				// show div
				FX.wipeOut({
					node:		toggleNode,
					onEnd:		function() {
						DomClass.add(toggleNode, "hidden");
					}
				}).play();
			}
		}
	});
});