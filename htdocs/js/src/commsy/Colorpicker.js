define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojox/widget/ColorPicker",
        	"dojo/dom-attr"], function(declare, BaseClass, lang, Query, ColorPicker, DomAttr) {
	return declare(BaseClass, {
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			// setup colorpicker
			var colorpicker = new dojox.widget.ColorPicker({
				name:			DomAttr.get(node, "name")
			}, node);
			
			var color = DomAttr.get(node, "value");
			if(color) colorpicker.setColor(color);
			else colorpicker.setColor("#FFFFFF");
		}
	});
});