/**
 * Attachments Overlay Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.tools.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		init: function(commsy_functions, objects) {
			// set preconditions
			this.setPreconditions(commsy_functions, this.registerEvent, {objects: objects});
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		registerEvent: function(preconditions, parameters) {
			jQuery(parameters.objects).each(function(index) {
				// tooltip
				jQuery(this).tooltip({
					effect: 'slide'
				});
			});
		}
	};
});