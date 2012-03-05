/**
 * CKEditor Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
        	"order!libs/ckeditor/ckeditor",
        	"order!libs/ckeditor/adapters/jquery",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		options: {
			language: 'de',
			skin: 'kama',
			uiColor: '#eeeeee',
			startupFocus: false,
			dialog_startupFocusTab: false,
			resize_enabled: true,
			resize_maxWidth: '100%',
			enterMode: CKEDITOR.ENTER_BR,
			shiftEnterMode: CKEDITOR.ENTER_P,
			//extraPlugins: 'CommSyImages,CommSyMDO',
			toolbar: [
			    ['Cut', 'Copy', 'Paste', 'PasteFromWord', '-', 'Undo', 'Redo', '-', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', 'SpecialChar', '-', 'NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote', '-', 'TextColor', 'BGColor', '-', 'RemoveFormat']
			    ,'/',
			    ['Format', 'Font', 'FontSize', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'Link', 'Unlink', '-', 'Table', 'HorizontalRule', 'Smiley'],
			    ,'/',
			    ['Maximize', 'Preview', 'About', '-', 'Image']
			]
		},
		
		init: function(commsy_functions, parameters) {
			parameters.handle = this;
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.create, parameters);
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		create: function(preconditions, parameters) {
			var object = parameters.register_on;
			var input_object = parameters.input_object;
			var handle = parameters.handle;
			
			// on form submit, attach editor content
			var form_object = object.parentsUntil('form').last().parent();
			handle.form_attach(form_object, input_object);
			var options = handle.options;
			
			
			object.ckeditor(function() { /* callback */ }, options);
		},
		
		form_attach: function(form_object, attach_object) {
			var handler = this.onSubmit;
			// register submit handling
			form_object.bind('submit', {attach_object: attach_object}, handler);
		},
		
		onSubmit: function(event) {
			var attach_object = event.data.attach_object;
			
			var editor = jQuery('div[id="ckeditor"]').ckeditorGet();
			attach_object.attr('value', editor.getData());
		}
	};
});