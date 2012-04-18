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
			this.setPreconditions(commsy_functions, this.setupMenus, {handle: this, objects: parameters.objects});
		},

		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};

			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},

		setupMenus: function(preconditions, parameters) {
			var handle = parameters.handle;
			var objects = parameters.objects;
			
			// register all trigger
			jQuery.each(objects, function() {
				// determe trigger offset
				//var offset = this.trigger.offset();
				
				// reposition menu
				//this.menu.offset({top: offset.top + this.trigger.outerHeight(), left: offset.left - this.trigger.css('padding-left').substr(0, 2)});
				
				this.trigger.bind('click', {menu: this.menu, trigger: this.trigger, active_class: this.active_class}, handle.onClick);
			});
		},
		
		onClick: function(event) {
			var menu = event.data.menu;
			var trigger = event.data.trigger;
			var active_class = event.data.active_class;
			
			
			if(menu.css('display') === 'none') {
				// show
				menu.show();
				
				trigger.addClass(active_class);
			} else {
				// hide
				menu.hide();
				
				trigger.removeClass(active_class);
			}
			
			return false;
		}
	};
});