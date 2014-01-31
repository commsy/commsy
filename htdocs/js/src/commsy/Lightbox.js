define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style",
        	"dojo/query",
        	"dijit/_TemplatedMixin",
        	"dojo/on",
        	"dojo/fx",
        	"dojo/_base/lang",
        	"dojo/_base/array",
        	"dojox/image/Lightbox",
        	"dojo/text!./templates/Lightbox.html"], function(declare, BaseClass, DomAttr, DomConstruct, DomStyle, Query, _TemplatedMixin, On, FX, Lang, Array, Lightbox, Template) {
	return declare(BaseClass, {
		display:	false,
		anim:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(nodeList) {
			var dialog = dojo.declare("CustomLightboxDialog", [dojox.image.LightboxDialog, dijit._TemplatedMixin ], {
				id:					"dojoxLightboxDialog",
				templateString: 	Template
			});
			
			var dialog = new CustomLightboxDialog();
			
			// group by item_id - class is lightbox_itemid
			dojo.forEach(nodeList, function(node, index, arr) {
				var lightboxObject = {
					group:		DomAttr.get(node, "class").substr(9),
					title:		DomAttr.get(node, "title"),
					href:		DomAttr.get(node, "href")
				};
				
				// create lightbox instance for each
				var lightBox = new Lightbox(lightboxObject);
				
				lightBox.startup();
				
				On(node, "click", function(event) {
					lightBox.show();
					
					event.preventDefault();
				});
			});
			
			On(Query("a#lightboxDownloadLink", dialog.domNode)[0], "click", function() {
				url_to_open = DomAttr.get(dialog.imgNode, "src");
			    window.open(url_to_open, '_blank');
			});
		}
	});
});