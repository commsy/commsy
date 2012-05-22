/**
 * Datepicker Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		preconditions: null,
		
		init: function(commsy_functions, parameters) {
			parameters.handle = this;
			
			jQuery.datepicker.setDefaults({
				dateFormat:		'dd.mm.yy'
			});
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.setup, parameters);
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
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
			
			// setup datepicker
			register_on.each(function() {
				jQuery(this).datepicker();
			});
		}
	};
});