/**
 * Tag Tree Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
        	"order!libs/jQuery/jquery-ui-1.8.16.custom.min",
        	"order!libs/jQuery_plugins/dynatree-1.2.0/jquery.cookie",
        	"order!libs/jQuery_plugins/dynatree-1.2.0/jquery.dynatree.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		init: function(commsy_functions, object) {
			jQuery.ui.dynatree.nodedatadefaults['icon'] = false;		// Turn off icons by default
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.buildTree, {object: object});
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		buildTree: function(preconditions, parameters) {
			var div_object = parameters.object;
			
			div_object.dynatree({
				persist: true,			// store state in cookie
				
				onClick: function(node, event) {
					// follow link if event was not triggered by an expander
					if(event.target.className !== 'dynatree-expander') {
						window.location.href = node.data.url;
					}
				}
			});
		}
	};
});