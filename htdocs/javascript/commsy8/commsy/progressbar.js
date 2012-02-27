/**
 * Progressbar Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
        	"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {		
		init: function(commsy_functions, object) {
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.setup, {commsy_functions: commsy_functions, objects: object.objects, handle: this});
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		setup: function(preconditions, parameters) {
			var progressbars = parameters.objects;
			
			jQuery.each(progressbars, function() {
				// get value from span-tag
				var percent_span = jQuery(this).children('span[class^="percent"]');
				var value = parseInt(percent_span.text());
				
				// remove span
				percent_span.remove();
				
				
				// remove img
				jQuery(this).children('img:first').remove();
				
				// create progressbars
				jQuery(this).progressbar({
					disabled: false,
					value: value,
					create: function(event, ui) {
						var object = jQuery(event.target);
						
						// get count from span-tag
						var count_span = object.children('span[class^="value"]');
						var count = count_span.text();
						
						// remove span
						count_span.remove();
						
						// add to div
						object.children('div').text(count);
					}
				});
			});
		}
	};
});