define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/dom-attr",
        	"dojo/dom-class",
        	"dojo/dom-style",
        	"dojo/query",
        	"dojo/on",
        	"dojo/fx",
        	"dojo/_base/lang"], function(declare, BaseClass, DomAttr, DomClass, DomStyle, Query, On, FX, Lang) {
	return declare(BaseClass, {
		display:	false,
		anim:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(objects) {
			dojo.forEach(objects, Lang.hitch(this, function(object, index, arr) {
				var actor = object.actor;
				var div = object.div;
				var img = Query("> img", actor)[0];
				
				On(actor, "click", Lang.hitch(this, function(event) {
					this.onEvent(actor, div, img);
					
					event.preventDefault();
				}));
			}));
		},
		
		onEvent: function(actor, div, img) {
			if(img) {
				if(DomStyle.get(div, "display") === "none" || DomClass.contains(div, "hidden")) {
					
					if(DomClass.contains(div, "hidden")) {
						DomStyle.set(div, "display", "none");
						DomClass.remove(div, "hidden");
					}
					
					FX.wipeIn({
						node:		div
					}).play();
					
					DomAttr.set(img, "src", this.from_php.template.tpl_path + "img/btn_ci_close.gif");
				} else {
					FX.wipeOut({
						node:		div
					}).play();
					
					DomAttr.set(img, "src", this.from_php.template.tpl_path + "img/btn_ci_open.gif");
				}
			}
		}
	});
});