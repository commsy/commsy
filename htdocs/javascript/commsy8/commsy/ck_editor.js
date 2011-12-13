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
		
		init: function() {
		},
		
		create: function(div_objects) {
			options = this.options;
			
			div_objects.each(function() {
				jQuery(this).ckeditor(function() { /* callback */ }, options);
			});
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