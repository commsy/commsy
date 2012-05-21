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
		preconditions: null,
		
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
			var register_on = parameters.register_on;
			var handle = parameters.handle;
			
			// store preconditions
			if(handle.preconditions === null) handle.preconditions = preconditions;
			
			// restore
			else if(preconditions === null) preconditions = handle.preconditions;
			
			// if there is no object, skip
			if(register_on.length == 0) return true;
			
			// create ckeditor instances for all register_on objects
			register_on.each(function() {
				// get id of this object and create a hidden input field beside
				// the id determs the form_data[]-key
				// this will later on get the editors content, when the form is submited
				var id = jQuery(this).attr('id');
				
				jQuery(this).after(jQuery('<input/>', {
					type:		'hidden',
					name:		'form_data[' + id  + ']'
				}));
				
				jQuery(this).ckeditor(function() { /* callback */}, handle.options);
				
				// get the form this editor belongs to
				var form_object = jQuery(this).parentsUntil('form').parent();
				
				// on form submit, attach editor content to hidden input
				handle.append_content(form_object, jQuery('input[name="form_data[\"' + id + '\"]'), jQuery(this).ckeditorGet());
			});
		},
		
		append_content: function(form_object, hidden_input_object, editor) {
			form_object.bind('submit', {hidden_input_object: hidden_input_object, editor: editor}, this.onSubmit);
		},
		
		onSubmit: function(event) {
			event.data.hidden_input_object.attr('value', event.data.editor.getData());
		}
	};
});