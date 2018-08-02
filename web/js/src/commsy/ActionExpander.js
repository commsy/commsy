define([	"dojo/_base/declare",
        	"dojo/on",
        	"dojo/_base/lang",
        	"commsy/base",
        	"dojo/fx",
        	"dojo/dom-class",
        	"dojo/dom-style",
        	"dojo/dom-attr",
        	"dojo/query"], function(declare, On, Lang, BaseClass, FX, DomClass, DomStyle, DomAttr, Query) {
	return declare(BaseClass, {
		hidden:		[],
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(actorNodes) {
			dojo.forEach(actorNodes, Lang.hitch(this, function(node, index, arr) {
				// find content div - connected by data-custom
				var customData = this.getAttrAsObject(node, "data-custom");
				
				var link = customData.expand;
				var contentNode = Query("div#" + link)[0];
				
				if(contentNode) {
					this.hidden[index] = DomClass.contains(contentNode, "hidden");
					
					On(node, "click", Lang.hitch(this, function(event) {
						this.onClick(node, index, contentNode);
						
						event.preventDefault();
					}));
				} else {
					console.error("content for action missing");
				}
			}));
		},
		
		onClick: function(actorNode, index, contentNode) {
			// get span class from actor
			var spanNode = Query("span:first", actorNode)[0];
			var spanClassName = DomAttr.get(spanNode, "class");
			
			if(this.hidden[index]) {
				// remove hidden class and set height to 0px
				DomClass.remove(contentNode, "hidden");
				DomStyle.set(contentNode, 'height', '0px');
				
				FX.wipeIn({
					node:		contentNode
				}).play();
				
				// add class "item_actions_glow" to actor
				DomClass.add(actorNode, "item_actions_glow");
				
				// check last three characters of spanClassName
				if(spanClassName.substr(-3, 3) !== '_ok') {
					// add "_ok" to span class
					DomAttr.set(spanNode, "class", spanClassName + "_ok");
				}
				
				this.scrollToNodeAnimated(contentNode);
			} else {
				FX.wipeOut({
					node:		contentNode
				}).play();
				
				// remove class "item_actions_glow" from actor
				DomClass.remove(actorNode, "item_actions_glow");
				
				// check last three characters of spanClassName
				if(spanClassName.substr(-3, 3) === '_ok') {
					// remove "_ok" from span class
					DomAttr.set(spanNode, "class", spanClassName.substr(0, spanClassName.length - 3));
				}
			}
			
			this.hidden[index] = !this.hidden[index];
		}
	});
});