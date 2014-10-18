define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/query"], function(declare, BaseClass, Query) {
	return declare(BaseClass, {
		display:	false,
		anim:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		follow: function(anchor) {
			// separate type and item id
			/([a-z]*)([\-0-9]*)/.exec(anchor);
			var type = RegExp.$1;
			var item_id = parseInt(RegExp.$2);
			
			if(type === "annotation") {
				// follow annotation
				this.followAnnotation(item_id);
			}
		},
		
		followAnnotation: function(item_id) {
			// simulate click on annotations actions
			Query("div#top_item_actions a.annotations")[0].click();
			
			// scroll / go to node
			var anchorNode = Query("a[name='annotation" + item_id + "']")[0];
			this.scrollToNodeAnimated(anchorNode);
			window.location.href = window.location.href;
		}
	});
});