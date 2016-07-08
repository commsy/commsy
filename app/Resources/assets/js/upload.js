;(function(UI) {

    "use strict";

    var settings = {
        allow: '*.*'
    };

    $(document).ready(function() {
        $(".uk-position-cover div.uk-form-controls").css("margin-left", "0px");
    });

    var localStorageUpload = function(){

    }

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
                    
                    console.debug("response:");
                    console.debug(response);

                    var responseData = JSON.parse(response);

                    console.debug("responseData (JSON parsed):");
                    console.debug(responseData);

                    if (responseData['userImage']) {
                        $('#profile_form_user_image').attr('src', responseData['userImage'] + '?' + Math.random());
                    }
                    else if (responseData['fileIds']) {
                        console.log(responseData['fileIds']);
                        for (var key in responseData['fileIds']) {
                            $('#upload_oldFiles').append('<div class="uk-form-controls"><input type="checkbox" id="upload_oldFiles_' + key +'" name="upload[oldFiles][]" value="' + key +'" checked="checked"></div><label class="uk-form-label" for="upload_oldFiles_' + key +'">' + responseData['fileIds'][key] + '</label>');
                        }
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