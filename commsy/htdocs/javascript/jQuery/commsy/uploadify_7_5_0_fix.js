// UPLOADIFY RETTUNGSANKER
function check_upload_form() {
	if(jQuery('input[id="uploadify"]')) {
		// ist der Uploader nicht da?
		var uploader_test = jQuery('object[id="uploadifyUploader"]').length;
		if(uploader_test == 0) {
			jQuery('input[id="uploadify"]').attr('name', 'upload');
			jQuery('input[id="uploadify"]').attr('style', 'display: inline;');
			jQuery('input[id="uploadify_fixSubmitButton"]').attr('style', 'display: inline;');
			jQuery('a[id="uploadify_doUpload"]').attr('style', 'display: none;');
			jQuery('a[id="uploadify_clearQuery"]').attr('style', 'display: none;');
			
			//jQuery('input[id="uploadify"]').parent().append('<input type="submit"/>');
		}
	}
}