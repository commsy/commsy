define(["dojo/_base/declare"], function(declare) {	
	return declare(null, {
		constructor: function(args) {
			console.log('Test');
			// ...
			/*
			 * require(['commsy/popup_handler'], function(module) {
		//module.init();
	});
			 */
		},
		
		init: function() {
			console.log('test');
		}
	});
});