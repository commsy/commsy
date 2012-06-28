define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom-attr"], function(declare, BaseClass, lang, Query, On, DomAttr) {
	return declare(BaseClass, {
		threshold:		3,
		ajaxRequests:	[],
		used:			false,
		matches:		[],
		
		constructor: function(options) {
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			// register handler
			On(node, "keyup", lang.hitch(this, function(event) {
				this.onKeyUp(event);
			}));
			
			On(node, "click", lang.hitch(this, function(event) {
				this.onClick(event);
			}));
		},
		
		onKeyUp: function(event) {
			// set suggestion to typed text
			DomAttr.set(Query("input#search_suggestion")[0], "value", event.target.value);
			
			// only update if threshold is met
			if(event.target.value.length === this.threshold) {console.log(event.target.value.length);
				// abort all running ajax requests
				dojo.forEach(this.ajaxRequests, function(request, index, arr) {
					request.abort();
				});
				
				// send ajax request
				this.AJAXRequest("search", "getAutocompleteSuggestions", { search_text: event.target.value },
					lang.hitch(this, function(words) {
						// update matches
						this.matches = words;
						
						// autosuggest
						this.autoSuggest(DomAttr.get(Query("input#search_input")[0], "value"));
					}),
					
					lang.hitch(this, function(err) {
						console.log(err);
					}),
					false,
					{
						//beforeSend:	function
					});
				
				/*

					beforeSend: function(jqXHR, settings) {
						handle.ajaxRequests.push(jqXHR);
					},
					complete: function(jqXHR, textStatus) {
						handle.ajaxRequests.pop();
					},

				});
				 */
				
			} else if(event.target.value > this.threshold) {
				// autosuggest
				this.autoSuggest(event.target.value);
			}
		},
		
		onClick: function(event) {
			if(this.used === false) {
				// initial use
				DomAttr.set(event.target, "value", "");
				DomAttr.set(Query("input#search_suggestion")[0], "value", "");
				this.used = true;
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
							console.log(match);
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