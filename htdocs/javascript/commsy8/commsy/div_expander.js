/**
 * Div Expander Module
 */

define([	"libs/jQuery/jquery-1.7.1.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		/*
		 * optional parameter: collapsed(initial state)
		 */
		registerEvent: function(target, actors, event) {
			// process optional argument
			var initial_collapsed = false;
			if(typeof(arguments[3]) !== 'undefined') {
				initial_collapsed = arguments[3];
			}
			
			// store handler
			var handler = this.onEvent;
			
			// go through all actors
			jQuery.each(actors, function() {
				// bind
				jQuery(this).bind(event, {target: target}, handler);
			});
		},
		
		onEvent: function(event) {
			var target = event.data.target;
			
			// toggle
			target.toggle('fast');
			
			// stop page reload
			return false;
		}
	};
});