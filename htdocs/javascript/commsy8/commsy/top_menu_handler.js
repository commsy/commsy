/**
 * Top Menu Handler Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.viewport.mini",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		cid: null,
		
		init: function(commsy_functions, parameters) {
			// set preconditions
			this.setPreconditions(commsy_functions, this.setupMenus, {handle: this, commsy_functions: commsy_functions, objects: parameters.objects});
		},

		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};

			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},

		setupMenus: function(preconditions, parameters) {
			var commsy_functions = parameters.commsy_functions;
			var handle = parameters.handle;
			var objects = parameters.objects;
			
			// register all trigger
			jQuery.each(objects, function() {
				// determe trigger offset
				var offset = this.trigger.offset();
				
				// reposition menu
				this.menu.offset({top: offset.top + this.trigger.outerHeight(), left: offset.left - this.trigger.css('padding-left').substr(0, 2)});
				
				this.trigger.bind('hover', {commsy_functions: commsy_functions, handle: handle, menu: this.menu}, handle.onHover);
			});
		},
		
		onHover: function(event) {
			var commsy_functions = event.data.commsy_functions;
			var handle = event.data.handle;
			var menu = event.data.menu;
			var trigger = jQuery(event.target);
			
			if(event.type === 'mouseenter') {
				// show
				menu.show();
			} else {
				
				// hide
				menu.hide();
			}
		}
	};
});