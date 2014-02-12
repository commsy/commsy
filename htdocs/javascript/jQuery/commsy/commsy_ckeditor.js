jQuery(document).ready(function() {
	jQuery('#ckeditor_file_form').bind('submit', function(event){
		event.preventDefault();
		window.opener.CKEDITOR.tools.callFunction(jQuery('#ckeditor_file_func_number').val(), jQuery('#ckeditor_file_select').find(':selected').val());
		window.close();
	});
});