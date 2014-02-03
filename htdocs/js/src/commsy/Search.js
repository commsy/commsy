define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom-attr"], function(declare, BaseClass, lang, Query, On, DomAttr) {
	return declare(BaseClass, {		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.threshold = 3;
			this.matches = [];
			this.ajaxRequests = [];
		},
		
		setup: function(node) {
			// register handler
			On(node, "keyup", lang.hitch(this, function(event) {
				this.onKeyUp(event);
			}));
		},
		
		onKeyUp: function(event) {
			//var char = String.fromCharCode(event.keyCode).toLowerCase();
			
			// set suggestion to typed text
			DomAttr.set(Query("input#search_suggestion")[0], "value", event.target.value);
			
			// only update if threshold is met
			if(event.target.value.length >= this.threshold && this.matches.length == 0) {
				// abort all running ajax requests
				dojo.forEach(this.ajaxRequests, function(request, index, arr) {
					request.cancel();
				});
				
				// send ajax request
				var request = this.AJAXRequest("search", "getAutocompleteSuggestions", { search_text: event.target.value.toLowerCase() },
					lang.hitch(this, function(words) {
						// update matches
						this.matches = words;
						
						// autosuggest
						this.autoSuggest(DomAttr.get(Query("input#search_input")[0], "value"));
					}),
					
					lang.hitch(this, function(err) {
						console.log(err);
					}),
					false);
				
				// store this request in array
				this.ajaxRequests.push(request);
				
			} else if(event.target.value.length > this.threshold) {
				// autosuggest
				this.autoSuggest(event.target.value);
			}
		},
		
		autoSuggest: function(userInput) {
			var length = 33;
			var suggestion = "";
			
			// find new suggestion
			dojo.forEach(this.matches, function(match, index, arr) {
				// current input needs to match the beginning of match
				if(userInput.toLowerCase() === match.substr(0, userInput.length)) {
					// match needs to be longer than userInput
					if(match.length > userInput.length) {
						// find shortest
						if(match.length < length) {
							length = match.length;
							suggestion = match;
						}
					}
				}
			});
			
			// set suggestion - take first characters from user input
			DomAttr.set(Query("input#search_suggestion")[0], "value", userInput + suggestion.substr(userInput.length));
		}
	});
});