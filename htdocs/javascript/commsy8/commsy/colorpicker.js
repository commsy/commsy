/**
 * Colorpicker Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
			"order!libs/jQuery_plugins/colorpicker/jquery.colorpicker",
			"order!libs/jQuery_plugins/colorpicker/i18n/jquery.ui.colorpicker-en",
			"order!libs/jQuery_plugins/colorpicker/i18n/jquery.ui.colorpicker-de",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		preconditions: null,
		
		init: function(commsy_functions, parameters) {
			parameters.handle = this;
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.setup, parameters);
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
				environment: ['lang']
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		setup: function(preconditions, parameters) {
			var handle = parameters.handle;
			var register_on = parameters.register_on;
			
			// store preconditions
			if(handle.preconditions === null) handle.preconditions = preconditions;
			
			// restore
			else if(preconditions === null) preconditions = handle.preconditions;
			
			// setup colorpicker
			register_on.each(function() {
				jQuery(this).colorpicker({
					regional:	preconditions.environment.lang,
					zIndex:		1003
				});
			});
		}
	};
});