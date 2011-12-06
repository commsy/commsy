/**
 * List Selection Module
 */

define([	"libs/jQuery/jquery-1.7.1.min",
        	"libs/jQuery_plugins/dynatree-1.2.0/jquery.cookie",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		cookie_prefix: 'commsy_list_selection_',
		numSelections: 0,
		
		init: function() {
			// get num selections from cookie
			if(jQuery.cookie(this.cookie_prefix + 'count') !== null)
				this.numSelections = jQuery.cookie(this.cookie_prefix + 'count');
		},
		
		storeSelectionInCookie: function(event) {
			// get item_id
			var name = event.data.input_object.attr('name');
			var match = /attach\[([0-9]*)\]/.exec(name);
			var item_id = match[1];
			
			var class_ref = event.data.class_ref;
			
			if(event.data.input_object.attr('checked') === 'checked') {
				// set cookie
				jQuery.cookie(class_ref.cookie_prefix + item_id, 'checked');
				class_ref.numSelections++;
			} else {
				// delete cookie
				jQuery.cookie(class_ref.cookie_prefix + item_id, null);
				class_ref.numSelections--;
			}
			
			// store number of selections
			jQuery.cookie(class_ref.cookie_prefix + 'count', class_ref.numSelections);
		},
		
		getSelectionStateFromCookie: function(input_object) {
			// get item_id
			var name = input_object.attr('name');
			var match = /attach\[([0-9]*)\]/.exec(name);
			var item_id = match[1];
			
			if(jQuery.cookie(this.cookie_prefix + item_id) === null)
				return false;
			
			return true;
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