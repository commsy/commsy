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
			enterMODE: CKEDITOR.ENTER_BR,
			shiftEnterMODE: CKEDITOR.ENTER_P,
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
		}
	};
});