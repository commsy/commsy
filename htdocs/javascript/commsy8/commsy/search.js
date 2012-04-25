/**
 * Search Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
        	"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		threshold: 3,
		
		init: function(commsy_functions, parameters) {
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.setup, {commsy_functions: commsy_functions, object: parameters, handle: this});
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		setup: function(preconditions, parameters) {
			var handle = parameters.handle;
			var object = parameters.object;
			var commsy_functions = parameters.commsy_functions;
			
			// register handler
			jQuery(object).bind('keyup', {handle: handle, commsy_functions: commsy_functions}, handle.onKeyUp);
			
			// setup progressbars for list
			handle.setupProgressbars();
		},
		
		setupProgressbars: function() {
			// find progressbars
			var progressbars = jQuery('div[class="progressbar_search"]');
			
			// find max relevanz value
			var max = 0;
			jQuery.each(progressbars, function() {
				var span = jQuery(this).children('span:first');
				var value = parseInt(span.text());
				
				if(value > max) max = value;
			});
			
			jQuery.each(progressbars, function() {
				// get value from span-tag
				var span = jQuery(this).children('span:first');
				var value = parseInt(span.text());
				
				// remove span
				span.remove();
				
				// remove img
				jQuery(this).children('img:first').remove();
				
				// calculate percent
				var percent = 100 * value / max;
				
				// create progressbars
				jQuery(this).progressbar({
					disabled: false,
					value: percent
				});
			});
		},
		
		onKeyUp: function(event) {
			var handle = event.data.handle;
			var value = event.target.value;
			var cid = event.data.commsy_functions.getURLParam('cid');
			
			if(value.length >= handle.threshold) {
				// TODO: no new request, if search becomes more specific, etc...
				
				// send ajax request
				var data = new Object;
				data.search_text = value;
				
				jQuery.ajax({
					type: 'POST',
					url: 'commsy.php?cid=' + cid + '&mod=ajax&fct=search&action=getAutocompleteSuggestions',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					error: function() {
						console.log("error while sending ajax request - search.js");
					},
					success: function(data, status) {
						var length = 33;
						var match = '';
						
						jQuery(data).each(function(index, element) {
							if(element.length < length && element.length > value.length) {
								length = element.length;
								match = element;
							}
						});
						
						jQuery('input[id="search_suggestion"]').val(match);
					}
				});
			}
		}
	};
});