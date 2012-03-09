/**
 * Anchor Follower Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
        	"order!libs/jQuery_plugins/jquery.viewport.mini",        	
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		init: function(commsy_functions, parameters) {
			parameters.handle = this;
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.follow, parameters);
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		follow: function(preconditions, parameters) {
			// store handler
			var handle = parameters.handle;
			var anchor = parameters.anchor;
			
			// separate type and item id
			/([a-z]*)([0-9]*)/.exec(anchor);
			var type = RegExp.$1;
			var item_id = parseInt(RegExp.$2);
			
			if(type === 'annotation') {
				// follow annotation
				handle.followAnnotation(item_id);
			}
		},
		
		followAnnotation: function(item_id) {
			// simulate click on annotations actions
			jQuery('div[id="top_item_actions"] a[class="annotations"]').click();
			
			// check if annotation is outside screen
			var target = jQuery('a[name="annotation' + item_id + '"]');
			if(!jQuery.inviewport(target, {threshold: 0})) {
				// goto target
				window.location.href = window.location.href;
			}
		}
	};
});