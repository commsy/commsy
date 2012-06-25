define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojox/widget/ColorPicker",
        	"dojo/dom-attr"], function(declare, BaseClass, lang, Query, ColorPicker, DomAttr) {
	return declare(BaseClass, {
		constructor: function(options) {
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			// setup colorpicker
			var colorpicker = new dojox.widget.ColorPicker({
				value:			DomAttr.get(node, "value"),
				name:			DomAttr.get(node, "name"),
			}, node);

			
			/*
			
			dojo.require("dojox.widget.ColorPicker");
			dojo.ready(function(){
			    var c = new dojox.widget.ColorPicker({}, "picker1");
			});
			
			
			
			// setup colorpicker
			register_on.each(function() {
				jQuery(this).colorpicker({
					regional:	preconditions.environment.lang,
					zIndex:		1003
				});
			});
			*/
		}
	});
});