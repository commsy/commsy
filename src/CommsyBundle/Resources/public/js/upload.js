;(function(UI) {

    "use strict";

    var settings = {
        allow: '*.*'
    };

    var setupUpload = function() {
        $('.upload').each(function() {
            // get data from input element
            var data = $(this).find('input').data('upload');

            // skip already initialized uploa fields, may be optimized
            if (data.initialized) {
                return true;
            }

            var progressbar = $(this).siblings('.uk-progress').first();
            var bar = progressbar.find('.uk-progress-bar');

            var elementSettings = {
                action: data.path,
                single: false,

                loadstart: function() {
                    bar.css("width", "0%").text("0%");
                    progressbar.removeClass("uk-hidden");
                },

                progress: function(percent) {
                    percent = Math.ceil(percent);
                    bar.css("width", percent+"%").text(percent+"%");
                },

                allcomplete: function(response) {
                    bar.css("width", "100%").text("100%");

                    setTimeout(function(){
                        progressbar.addClass("uk-hidden");
                    }, 250);
                    
                    var responseData = JSON.parse(response);
                    if (responseData['userImage']) {
                        $('#profile_form_user_image').attr('src', responseData['userImage'] + '?' + Math.random());
                    }
                }
            };

            var merged = $.extend(settings, elementSettings);

            var select = UI.uploadSelect($(this).find('input'), merged);
            var drop = UI.uploadDrop(this, merged);

            // set an initialized flag to prevent re-setup
            data.initialized = true;
            $(this).find('input').data('upload', data);
        });
    };

    UIkit.on('beforeready.uk.dom', function() {
        setupUpload();
    });

    UIkit.on('changed.uk.dom', function(event) {
        setupUpload();
    });

})(UIkit);