define([	"dojo/request/registry",
        	"dojo/request/script",
        	"dojo/_base/lang",
        	"dojo/_base/json"], function(request, script, lang, json) {
	
	/*
	 * Register anything that starts with "http://" or "https://" to be send
	 * to the script provider (JSONP), local requests will use xhr
	 */
	request.register(/^https?\/\//i, script);
	
	/*
	 * disables xhr error reporting temporarily to prevent the request
	 * from beeing called in a recursive loop
	 */
	var cooldown = false;
	
	/*
	 * ajax request options
	 */
	var options = {
		// A boolean that, if true, causes the request to block until the server
		// has responded or the request has timed out.
		sync:		false,
		
		// A string or key-value object containing query parameters to append to the URL.
		query:		{},
		
		// A string, key-value object, or FormData object containing data to transfer to the server.
		data:		{},
		
		// Time in milliseconds before considering the request a failure and triggering the error handler.
		timeout:	60000,
		
		// A string representing how to convert the text payload of the response
		// before passing the converted data to the success handler.
		// Possible formats are "text" (the default), "json", "javascript", and "xml".
		handleAs:	'json',
		
		// A key-value object containing extra headers to send with the request.
		headers:	{},
		
		jsonp:		'callback'
	};
	
	return {
		ajax: function(optionsMixin)
		{
			var url = 'commsy.php';
			
			if (typeof(commsyConfig) != "undefined") {
				if (commsyConfig.remoteAjaxURL) {
					url = commsyConfig.remoteAjaxURL + url;
				}
			}
			
			optionsMixin.data = json.toJson(optionsMixin.data);
			
			return request.post(url, lang.mixin(options, optionsMixin)).then(
				function(response) {
					return response;
				},
				lang.hitch(this, function(error) {
					/************************************************************************************
					 * A fatal error occured while performing the ajax request, maybe something went wrong
					 * on php side or while transporting data. Show error message in console and setup a
					 * user-friendly error widget
					************************************************************************************/
					// ignore the case of status code 0 - aborted xhr requests(search auto-completion, etc.)
					if (error.response.xhr.status !== 0) {
						if (dojo.fromJson(from_php).dev.xhr_error_reporting && dojo.fromJson(from_php).dev.xhr_error_reporting === true) {
							
							// disable xhr error reporting temporarily to prevent this request from beeing called in a recursive loop
							if (!cooldown) {
								cooldown = true;
								
								this.ajax({
									query: {
										cid:	error.response.options.query.cid,
										mod:	'ajax',
										fct:	'actions',
										action:	'sendXHRErrorReporting'
									},
									data: {
										ioargs: error.response.options,
										error: error.text
									}
								}).then(
									lang.hitch(this, function(response) {
										cooldown = false;
									}),
									lang.hitch(this, function(error) {
										cooldown = false;
									})
								);
							}
						}
					}
				}));
		}
	};
});