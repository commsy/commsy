define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/query",
        	"dojo/on",
        	"dojo/fx",
        	"dojo/_base/lang"], function(declare, BaseClass, DomAttr, DomStyle, Query, On, FX, Lang) {
	return declare(BaseClass, {
		display:	false,
		anim:		null,
		
		constructor: function(options) {
			declare.safeMixin(this, options);
		},
		
		setup: function(objects) {
			objects.forEach(Lang.hitch(this, function(object, index, arr) {
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
				if(DomStyle.get(div, "display") === "none") {
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