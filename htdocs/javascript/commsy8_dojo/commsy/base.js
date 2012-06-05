define([	"dojo/_base/declare",
        	"dojo/_base/xhr",
        	"dojo/io-query"], function(declare, xhr, ioQuery) {	
	return declare(null, {
		uri_object: null,
		
		constructor: function(args) {
			// set query object
			this.uri_object = ioQuery.queryToObject(dojo.doc.location.search.substr((dojo.doc.location.search[0] === "?" ? 1: 0)));
		},
		
		init: function() {
			console.log('test');
		},
		
		getHTMLFromAJAX: function(fct, action, data, callback) {
			data.cid = this.uri_object.cid;
			data.mod = 'ajax';
			data.fct = fct;
			data.action = action;
			
			// execute a HTTP POST request
			var request = xhr.post({
				url:		"commsy.php?cid=" + this.uri_object.cid + "&mod=ajax&fct=" + fct + "&action=" + action,
				headers:	{
							"Content-Type":		"application/json; charset=utf-8",
							"Accept":			"application/json"
				},
				postData:	dojo.toJson(data),
				handleAs:	"json"
			});
			
			// setup deferred
			request.then(function(response) {
				if(response.status === "success") {
					callback(response.html);
				}
				
			}, function(errorMessage) {
				console.log('an error occurred');
			});
		}
	});
});