;(function($, document, window) {
    "use strict";

    if (typeof CKEDITOR != 'undefined') {
        CKEDITOR.on( 'instanceReady', function( evt ) {
            let editor = evt.editor;
            let data = $('.ckeditor-upload').data();
            if(data) {
                CKEDITOR.instances[editor.name].config.filebrowserUploadUrl = data.csUploadurl.path;
            }
        });
    }
})(jQuery, document, window);