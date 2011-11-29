/**
 * Tag Tree Module
 */

define([	"libs/jQuery/jquery-1.7.1.min",
        	"libs/jQuery/jquery-ui-1.8.16.custom.min",
        	"libs/jQuery_plugins/dynatree-1.2.0/jquery.cookie",
        	"libs/jQuery_plugins/dynatree-1.2.0/jquery.dynatree.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		buildTree: function(div_object) {
			div_object.dynatree({
				persist: true
			});
		}
	};
});