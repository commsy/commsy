/**
 * Action Overlay Module
 */

define([	"libs/jQuery/jquery-1.7.1.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		init: function(commsy_functions, parameters) {
			parameters.handle = this;
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.registerEvent, parameters);
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		registerEvent: function(preconditions, parameters) {
			var handle = parameters.handle;
			var objects = parameters.objects;
			
			// register mouser over
			var a_edit_objects = objects.find('a[class="edit"]');
			a_edit_objects.hover(handle.onHover);
			jQuery('div[class="edit_overlay"]').hover(function() { jQuery(this).show(); }, function() { jQuery(this).hide(); });
		},
		
		onHover: function(event) {
			var target = jQuery(event.target);
			var action_overlay = target.parent().next().find('div[class="edit_overlay"]');
			
			if(action_overlay.css('display') !== 'none') {
				action_overlay.hide();
			} else {
				action_overlay.show();
			}
		}
	};
});