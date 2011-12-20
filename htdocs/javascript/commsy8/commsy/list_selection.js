/**
 * List Selection Module
 */

define([	"libs/jQuery/jquery-1.7.1.min",
        	"libs/jQuery_plugins/dynatree-1.2.0/jquery.cookie",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		cookie_name: 'commsy_list_selection',
		numSelections: 0,
		
		init: function(commsy_functions, parameters) {
			parameters.handle = this;
			
			// get num selections from cookie
			if(jQuery.cookie(this.cookie_name) !== null) {
				var cookie = jQuery.parseJSON(jQuery.cookie(this.cookie_name));
				this.numSelections = cookie.items.length;
			}
			
			// compare current module with last used
			var current_module = commsy_functions.getURLParam('mod');
			if(current_module !== jQuery.cookie(this.cookie_name + '_last_module')) {
				// clear all selections
				this.clearSelectionsFromCookie();
				
				// update counter
				this.updateCounter(parameters.counter_object);
			}
			
			// store the current module as last used
			jQuery.cookie(this.cookie_name + '_last_module', current_module);
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.process, parameters);
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		process: function(preconditions, parameters) {
			var input_tags = parameters.input_tags;
			var counter_object = parameters.counter_object;
			var handle = parameters.handle;
			
			// get selection state from cookies
			input_tags.each(function() {
				if(handle.getSelectionStateFromCookie(jQuery(this))) {
					jQuery(this).attr('checked', 'checked');
				}
			});
			
			// store selection in cookie and update selection count
			input_tags.each(function() {
				var input_object = jQuery(this);
				
				// register onChange
				input_object.change({input_object: input_object, class_ref: handle}, handle.storeSelectionInCookie);
				input_object.change({update_object: counter_object, class_ref: handle}, handle.updateCounterHandler);
			});
			
			// update counter
			handle.updateCounter(counter_object);
		},
		
		clearSelectionsFromCookie: function() {
			jQuery.cookie(this.cookie_name, null);
			this.numSelections = 0;
		},
		
		storeSelectionInCookie: function(event) {
			// get item_id
			var name = event.data.input_object.attr('name');
			var match = /attach\[([0-9]*)\]/.exec(name);
			var item_id = match[1];
			
			var class_ref = event.data.class_ref;
			if(event.data.input_object.attr('checked') === 'checked') {
				// append to cookie
				if(jQuery.cookie(class_ref.cookie_name) !== null) {
					var cookie = jQuery.parseJSON(jQuery.cookie(class_ref.cookie_name));
				} else {
					var cookie = new Object;
					cookie.items = new Array();
				}
				cookie.items.push(item_id);
				
				// set cookie
				jQuery.cookie(class_ref.cookie_name, JSON.stringify(cookie));
				class_ref.numSelections++;
			} else {
				// remove from cookie
				var cookie = jQuery.parseJSON(jQuery.cookie(class_ref.cookie_name));
				var temp = new Array();
				jQuery(cookie.items).each(function(index) {
					if(parseInt(this) !== parseInt(item_id)) {
						temp.push(parseInt(this));
					}
				});
				
				cookie.items = temp;
				
				// set cookie
				jQuery.cookie(class_ref.cookie_name, JSON.stringify(cookie));
				class_ref.numSelections--;
			}
		},
		
		getSelectionStateFromCookie: function(input_object) {
			// get item_id
			var name = input_object.attr('name');
			var match = /attach\[([0-9]*)\]/.exec(name);
			var item_id = match[1];
			
			var cookie = jQuery.parseJSON(jQuery.cookie(this.cookie_name));
			var match = false;
			if(cookie !== null) {
				jQuery(cookie.items).each(function() {
					if(parseInt(this) === parseInt(item_id)) {
						match = true;
						return false;
					}
				});
			}
			
			if(match === true) return true;
			
			return false;
		},
		
		updateCounterHandler: function(event) {
			event.data.class_ref.updateCounter(event.data.update_object);
		},
		
		updateCounter: function(update_object) {
			// update counter
			update_object.text(this.numSelections);
		}
	};
});