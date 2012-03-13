/**
 * Ajax Popup Handler Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.tools.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		init: function(commsy_functions, parameters) {
			// set preconditions
			this.setPreconditions(commsy_functions, this.loadPopup, {handle: this, commsy_functions: commsy_functions, handling: parameters});
		},

		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};

			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},

		loadPopup: function(preconditions, parameters) {
			var commsy_functions = parameters.commsy_functions;
			var module = parameters.handling.module;
			var actors = parameters.handling.objects;
			var handle = parameters.handle;
			
			jQuery.each(actors, function() {
				jQuery(this).bind('click', {commsy_functions: commsy_functions, module: module}, handle.onClick);
			});
		},
		
		onClick: function(event) {
			var commsy_functions = event.data.commsy_functions;
			var module = {module: event.data.module};
			
			var cid = commsy_functions.getURLParam('cid');
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + cid + '&mod=ajax&fct=popup&action=getHTML',
				data: JSON.stringify(module),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function() {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(status === 'success') {
						// we recieved html - append it
						jQuery('body').prepend(data);
						
						// reinvoke CKEditor
						require(['commsy/ck_editor'], function($) {
							// call init
							$.init(commsy_functions, {register_on: jQuery('div[id="ckeditor"]'), input_object: jQuery('input[id="ckeditor_content"]')});
						});
						
						// reinvoke Uploadify
						require(['commsy/uploadify'], function($) {
							// call init
							$.init(commsy_functions, {register_on: jQuery('input[id="uploadify"]'), upload_object: jQuery('a[id="uploadify_doUpload"]'), clear_object: jQuery('a[id="uploadify_clearQuery"]')});
						});
					}
				}
			});
			
			// stop processing
			return false;
		}
	};
});