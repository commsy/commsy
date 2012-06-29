define([	"dojo/_base/declare",
        	"dojo/on",
        	"dojo/_base/lang",
        	"commsy/base",
        	"dojo/fx",
        	"dojo/dom-construct",
        	"dojo/dom-style",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dijit/ProgressBar",
        	"dojo/NodeList-traverse"], function(declare, On, Lang, BaseClass, FX, DomConstruct, DomStyle, DomAttr, Query, ProgressBar) {
	return declare(BaseClass, {
		hidden:		[],
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			var nodeList =  new dojo.NodeList(node);
			
			var percentSpan = nodeList.children("span[class^='percent']")[0];
			var value = parseInt(DomAttr.get(percentSpan, "innerHTML"));
			
			// remove span and img
			DomConstruct.destroy(percentSpan);
			DomConstruct.destroy(nodeList.children("img:first")[0]);
			
			var progressBar = new ProgressBar({
				value:		value + "%",
				"class":	"ui-progressbar"
			});
			
			progressBar.placeAt(node);
			
			// get count from span-tag
			var spanNode = nodeList.children("span[class^='value']")[0];
			if(spanNode) {
				var count = DomAttr.get(spanNode, "innerHTML");
				
				// remove span and set count
				DomConstruct.destroy(spanNode);
				progressBar.set("label", count);
			}
		}
	});
});