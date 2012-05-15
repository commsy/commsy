/**
 * Search Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
        	"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		threshold: 3,
		ajaxRequests: [],
		used: false,
		matches: [],
		
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
			jQuery(object).bind('click', {handle: handle}, handle.onClick);
		},
		
		onClick: function(event) {
			var handle = event.data.handle;
			
			if(handle.used === false) {
				// initial use
				jQuery(event.target).val('');
				jQuery('input[id="search_suggestion"]').val('');
				handle.used = true;
			}
		},
		
		onKeyUp: function(event) {
			var handle = event.data.handle;
			var value = event.target.value;
			var cid = event.data.commsy_functions.getURLParam('cid');
			
			// set suggestion to typed text
			jQuery('input[id="search_suggestion"]').val(value);
			
			// only update if threshold is met
			if(value.length == handle.threshold) {
				// abort all running ajax requests
				jQuery.each(handle.ajaxRequests, function() {
					this.abort();
				});
				
				// send ajax request
				var data = new Object;
				data.search_text = value;
				
				jQuery.ajax({
					type: 'POST',
					url: 'commsy.php?cid=' + cid + '&mod=ajax&fct=search&action=getAutocompleteSuggestions',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					beforeSend: function(jqXHR, settings) {
						handle.ajaxRequests.push(jqXHR);
					},
					complete: function(jqXHR, textStatus) {
						handle.ajaxRequests.pop();
					},
					error: function() {
						console.log("error while sending ajax request - search.js");
					},
					success: function(data, status) {
						// update matches
						handle.matches = data;
						
						// autosuggest
						handle.autoSuggest(jQuery('input#search_input').val());
					}
				});
			} else if(value.length > handle.threshold) {
				// autosuggest
				handle.autoSuggest(value);
			}
		},
		
		autoSuggest: function(user_input) {
			var length = 33;
			var match = '';
			
			// find new suggestion
			jQuery(this.matches).each(function(index, element) {
				// current input needs to match the beginning of element
				if(user_input.toLowerCase() === element.substr(0, user_input.length)) {
					// element needs to be longer than user input
					if(element.length > user_input.length) {
						// find shortest
						if(element.length < length) {
							console.log(element);
							length = element.length;
							match = element;
						}
					}
				}
			});
			
			// set suggestion - take first characters from user input
			jQuery('input[id="search_suggestion"]').val(user_input + match.substr(user_input.length));
		}
	};
});