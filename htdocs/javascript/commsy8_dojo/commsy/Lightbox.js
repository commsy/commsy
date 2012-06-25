define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/query",
        	"dojo/on",
        	"dojo/fx",
        	"dojo/_base/lang",
        	"dojo/_base/array",
        	"dojox/image/Lightbox"], function(declare, BaseClass, DomAttr, DomStyle, Query, On, FX, Lang, Array, Lightbox) {
	return declare(BaseClass, {
		display:	false,
		anim:		null,
		
		constructor: function(options) {
			declare.safeMixin(this, options);
		},
		
		setup: function(nodeList) {
			// group by item_id - class is lightbox_itemid
			nodeList.forEach(function(node, index, arr) {
				var lightboxObject = {
					group:		DomAttr.get(node, "class").substr(9),
					title:		DomAttr.get(node, "title"),
					href:		DomAttr.get(node, "href"),
					templateString: "",
					template: ""
				};
				
				// create lightbox instance for each
				var lightBox = new dojox.image.Lightbox(lightboxObject);
				lightBox.startup();
				
				On(node, "click", function(event) {
					lightBox.show();
					
					event.preventDefault();
				});
			});
		}
	});
});


/**

			
			// create lightbox instances for each group
			jQuery.each(lightbox_ids, function() {
				jQuery('a.lightbox_' + this).lightBox({
					fixedNavigation:	true,
					imageLoading:		tpl_path + 'img/lightbox/lightbox-ico-loading.gif',
					imageBtnClose:		tpl_path + 'img/lightbox/lightbox-btn-close.gif',
					imageBtnPrev:		tpl_path + 'img/lightbox/lightbox-btn-prev.gif',
					imageBtnNext:		tpl_path + 'img/lightbox/lightbox-btn-next.gif',
					imageBlank:			tpl_path + 'img/lightbox/lightbox-blank.gif'
				});
			});
		}
	};
});*/