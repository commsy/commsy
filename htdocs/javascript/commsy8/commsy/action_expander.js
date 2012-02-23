/**
 * Action Expander Module
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
			// store handler
			var handler = parameters.handle.onEvent;
			
			jQuery(parameters.actors).each(function(index) {
				// bind
				jQuery(this).bind('click', {target: parameters.objects[index]}, handler);
			});
		},
		
		onEvent: function(event) {
			var target = jQuery(event.data.target);
			var actor = jQuery(event.currentTarget);
			
			// toggle
			target.toggle('fast', function() {
				// process 
				
				// get classname from underlying span-tag
				var span = actor.children('span:first');
				var span_classname = span.attr('class');
				
				if(target.css('display') === 'none') {
					// invisible
					
					// remove class item_actions_glow
					actor.removeClass('item_actions_glow');
					
					// check if last three characters are '_ok'
					if(span_classname.substr(-3, 3) === '_ok') {
						// remove them
						span.attr('class', span_classname.substr(0, span_classname.length - 3));
					}
				} else {
					// visible
					
					// add class item_actions_glow
					actor.addClass('item_actions_glow');
					
					// check if last three characters are not '_ok'
					if(span_classname.substr(-3, 3) !== '_ok') {
						// add them
						span.attr('class', span_classname + '_ok');
					}
				}
			});

			// stop page reload
			return false;
		}
	};
});