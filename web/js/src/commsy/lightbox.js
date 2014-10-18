define([
	"dojo/_base/declare",
    "dojox/image/Lightbox",
    "dojo/domReady",
    "dojo/dom-attr",
    "dojo/on",
    "dojo/query",
    "dojo/_base/lang",
    "dojo/text!./templates/Lightbox.html"
], function(declare, Lightbox, domReady, domAttr, on, query, lang, template) {
	
	// Declare a custom Lightbox dialog class
	dojo.declare("CustomLightboxDialog", [dojox.image.LightboxDialog, dijit._TemplatedMixin ], {
		id:					"dojoxLightboxDialog",
		templateString: 	template
	});
	
	var LightboxManager = declare(null, {
		
		// our lightboxDialog instance
		lightboxDialog: null,
		
		constructor: function() {
			this.lightboxDialog = new CustomLightboxDialog();
			this.lightboxDialog.startup();
			
			on(query("a#lightboxDownloadLink", this.lightboxDialog.domNode)[0], "click", lang.hitch(this, function() {
				var src = domAttr.get(this.lightboxDialog.imgNode, "src");
			    window.open(src, '_blank');
			}));
		},
		
		addImageGroup: function(nodeList) {
			// group by item_id - class is lightbox_itemid
			dojo.forEach(nodeList, lang.hitch(this, function(node, index, arr) {
				var lightboxObject = {
					group:		domAttr.get(node, "class").substr(9),
					title:		domAttr.get(node, "title") ? domAttr.get(node, "title") : "",
					href:		domAttr.get(node, "href")
				};
				
				this.lightboxDialog.addImage({
					title:		lightboxObject.title,
					href:		lightboxObject.href
				}, lightboxObject.group);
				
				on(node, "click", lang.hitch(this, function(event) {
					this.lightboxDialog.show({ group: lightboxObject.group, href: lightboxObject.href });
					
					event.preventDefault();
				}));
			}));
		}
	});
	
	return new LightboxManager();
});