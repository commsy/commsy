;(function($, document, window) {
    "use strict";

    if (typeof CKEDITOR != 'undefined') {
        CKEDITOR.on( 'instanceReady', function( evt ) {
            let editor = evt.editor;
            let data = $('.ckeditor-upload').data();
            if(data) {
                CKEDITOR.instances[editor.name].config.filebrowserUploadMethod = 'form';
                CKEDITOR.instances[editor.name].config.filebrowserUploadUrl = data.csUploadurl.path;
            }
        });
    }

    $(".cke_textarea_inline").prev("textarea").insertAfter($(".cke_textarea_inline")).css({"width": "1px", "height": "1px", "opacity":"0", "display":"inline"});

})(jQuery, document, window);