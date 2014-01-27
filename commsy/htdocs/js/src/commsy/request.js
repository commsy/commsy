define([	"dojo/request/registry",
        	"dojo/request/script",
        	"dojo/_base/lang"], function(request, script, lang) {
	
	/*
	 * Register anything that starts with "http://" or "https://" to be send
	 * to the script provider (JSONP), local requests will use xhr
	 */
	request.register(/^https?\/\//i, script);
	
	return {
		ajax: function(url, optionsMixin)
		{
			var options = {
				// A boolean that, if true, causes the request to block until the server
				// has responded or the request has timed out.
				sync:		false,
				
				// A string or key-value object containing query parameters to append to the URL.
				query:		{},
				
				// A string, key-value object, or FormData object containing data to transfer to the server.
				data:		{},
				
				// Time in milliseconds before considering the request a failure and triggering the error handler.
				timeout:	10000,
				
				// A string representing how to convert the text payload of the response
				// before passing the converted data to the success handler.
				// Possible formats are "text" (the default), "json", "javascript", and "xml".
				handleAs:	'json',
				
				// A key-value object containing extra headers to send with the request.
				headers:	{},
				
				jsonp:		'callback'
			};
			
			return request.get(url, lang.mixin(options, optionsMixin));
		}
	};
});