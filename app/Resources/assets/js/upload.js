;(function(UI) {

    "use strict";

    UI.component('csupload', {

        defaults: {
            path: '',
            settings: {
                allow: '*.*'
            }
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-uk-csupload]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("csupload")) {
                        UI.csupload(element, UI.Utils.options(element.attr("data-uk-csupload")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            let $progressbar = $($this.element).parentsUntil('.uk-placeholder').parent().siblings('.uk-progress').first();
            let $bar = $progressbar.find('.uk-progress-bar');

            let elementSettings = {
                action: $this.options.path,
                single: false,

                loadstart: function() {
                    $bar.css("width", "0%").text("0%");
                    $progressbar.removeClass("uk-hidden");
                },

                progress: function(percent) {
                    percent = Math.ceil(percent);
                    $bar.css("width", percent+"%").text(percent+"%");
                },

                allcomplete: function(response) {
                    $bar.css("width", "100%").text("100%");

                    setTimeout(function(){
                        $progressbar.addClass("uk-hidden");
                    }, 250);
                    
                    let responseData = JSON.parse(response);

                    if (responseData['userImage']) {
                        $('#profile_form_user_image').attr('src', responseData['userImage'] + '?' + Math.random());
                    } else if (responseData['fileIds']) {
                        for (let key in responseData['fileIds']) {
                            $('#upload_oldFiles').append('<div class="uk-form-controls"><input type="checkbox" id="upload_oldFiles_' + key +'" name="upload[oldFiles][]" value="' + key +'" checked="checked"></div><label class="uk-form-label" for="upload_oldFiles_' + key +'">' + responseData['fileIds'][key] + '</label>');
                        }
                    }
                }
            };

            let merged = $.extend($this.options.settings, elementSettings);

            let select = UI.uploadSelect($this.element, merged);
            let drop = UI.uploadDrop($($this.element).parentsUntil('.uk-placeholder').parent(), merged);
        },
    });
})(UIkit);