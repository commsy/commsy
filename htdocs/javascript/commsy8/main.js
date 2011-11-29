/**
 * java script main function, loaded by RequireJS
 */

// set configuration
require.config({
	baseUrl: "javascript/commsy8"
});

// load jquery, commsy_functions and call init
require(["commsy/commsy_functions_8_0_0", "libs/jQuery/jquery-1.7.1.min"], function(commsy) {
	// init commsy functions
	commsy.init();
});