;(function(UI) {

    "use strict";

    UI.component('csupload', {

        defaults: {
            path: '',
            settings: {
                allow: '*.*'
            },
            errorMessage: '',
            noFileIdsMessage: '',
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

                allcomplete: function(response, xhr) {
                    if (xhr.status != 200) {
                        this.onError();
                    } else {
                        $bar.css("width", "100%").text("100%");

                        setTimeout(function(){
                            $progressbar.addClass("uk-hidden");
                        }, 250);

                        let responseData = JSON.parse(response);

                        if (responseData['userImage']) {
                            $('#profile_form_user_image').attr('src', responseData['userImage'] + '?' + Math.random());
                        } else if (responseData['fileIds']) {
                            let prototypeNode = $('form[name="upload"] div[data-prototype]');
                            let prototype = prototypeNode.data('prototype');

                            let index = prototypeNode.find(':input[type="checkbox"]').length;

                            for (let key in responseData['fileIds']) {

                                let indexedPrototype = prototype.replace(/__name__/g, index);

                                let prototypeInputNode = $(indexedPrototype).find(':input');
                                prototypeInputNode.attr('checked', 'checked');
                                prototypeInputNode.val(key);

                                let labelNode = $('<label class="uk-form-label"></label>')
                                    .attr('for', 'upload_files_' + index + '_checked')
                                    .html(responseData['fileIds'][key]);

                                index++;

                                let formControlNode = $('<div class="uk-form-controls"></div>')
                                    .append(prototypeInputNode);

                                prototypeNode
                                    .append(formControlNode)
                                    .append(labelNode);
                            }

                            if (responseData['fileIds'].length == 0) {
                                UIkit.notify($this.options.noFileIdsMessage, 'danger');
                            }
                        }
                    }
                },

                error: function(event) {
                    this.onError();
                },

                onError: function() {
                    $bar.css("width", "100%").text("100%");

                    setTimeout(function(){
                        $progressbar.addClass("uk-hidden");
                    }, 250);
                    
                    UIkit.notify($this.options.errorMessage, 'danger');
                }
            };

            let merged = $.extend($this.options.settings, elementSettings);

            let select = UI.uploadSelect($this.element, merged);
            let drop = UI.uploadDrop($($this.element).parentsUntil('.uk-placeholder').parent(), merged);
        },
    });
})(UIkit);