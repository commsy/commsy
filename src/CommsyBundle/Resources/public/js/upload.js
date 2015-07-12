;(function(UI){

    "use strict";

    var settings = {
        allow: '*.*'
    };

    // var select = UIkit.uploadSelect($("#upload-select"), settings);
    // var drop = UIkit.uploadDrop($("#upload-drop"), settings);

    UIkit.on('beforeready.uk.dom', function() {
        $('.upload').each(function() {
            var progressbar = $(this).siblings('.uk-progress').first();
            var bar = progressbar.find('.uk-progress-bar');

            // get data from input element
            var data = $(this).find('input').data('upload');

            var elementSettings = {
                action: data.path,

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
                }
            };

            var merged = $.extend(settings, elementSettings);

            var select = UI.uploadSelect($(this).find('input'), merged);
            var drop = UI.uploadDrop(this, merged);
        });
    });

})(UIkit);