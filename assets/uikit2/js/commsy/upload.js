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
                        } else if (responseData['base64']) {
                            let form = $($this.element).closest('form');
                            if (!!form) {
                                // NOTE: form is undefined when using the file dialog (instead of drag+drop) for upload
                                // FIXME: dynamically get the form name in a less-hacky way
                                let fileInputIdElements = $this.element.attr('id').split('_');
                                fileInputIdElements.splice(-1,1);
                                let formID = fileInputIdElements.join('_');
                                form = $('form[name=' + formID + ']');
                            }

                            let prototypeNode = form.find('div[data-prototype]');
                            let prototype = prototypeNode.data('prototype');

                            let index = prototypeNode.find(':input[type="checkbox"]').length;

                            for (let key in responseData['base64']) {

                                let indexedPrototype = prototype.replace(/__name__/g, index);

                                let prototypeInputNode = $(indexedPrototype).find(':input');
                                prototypeInputNode.attr('checked', 'checked');
                                prototypeInputNode.val(responseData['base64'][key]['content']);

                                let labelNode = $('<label class="uk-form-label"></label>')
                                    .attr('for', form.attr('name') + '_base64_' + index + '_checked')
                                    .html(responseData['base64'][key]['filename']);

                                index++;

                                let formControlNode = $('<div class="uk-form-controls"></div>')
                                    .append(prototypeInputNode);

                                prototypeNode
                                    .append(formControlNode)
                                    .append(labelNode);
                            }

                            if (responseData['base64'].length == 0) {
                                UIkit.notify($this.options.noFileIdsMessage, 'danger');
                            }

                        } else if (responseData['fileIds']) {
                            let prototypeNode = $('form[name="upload"] div[data-prototype]');
                            let prototype = prototypeNode.data('prototype');

                            let index = prototypeNode.find(':input[type="checkbox"]').length;

                            if (prototype) {
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
                            }

                            if (responseData['fileIds'].length == 0) {
                                UIkit.notify($this.options.noFileIdsMessage, 'danger');
                            }

                            // Call the "refreshFiles" live action of the hosting component
                            let $component = $($this.element).closest('div[data-controller]');
                            if ($component) {
                                $component[0].__component.refreshFiles();
                            }

                        } else if (responseData['attachmentInfo']) {
                            let attachmentInfoArray = responseData['attachmentInfo'];
                            let form = $($this.element).closest('form');
                            if (!!form) {
                                // NOTE: form is undefined when using the file dialog (instead of drag+drop) for upload
                                // FIXME: dynamically get the form name in a less-hacky way
                                let fileInputIdElements = $this.element.attr('id').split('_');
                                fileInputIdElements.splice(-1,1);
                                let formID = fileInputIdElements.join('_');
                                form = $('form[name=' + formID + ']');
                            }

                            // NOTE: this only works if the wanted prototype node is the last one in the form
                            let prototypeNode = form.find('div[data-prototype]').last();
                            let prototype = prototypeNode.data('prototype');

                            let index = prototypeNode.find(':input[type="checkbox"]').length;

                            for (let key in attachmentInfoArray) {
                                let attachmentInfo = attachmentInfoArray[key];
                                let nodeIDPrefix = form.attr('name') + '_files_' + index + '_';

                                let indexedPrototype = prototype.replace(/__name__/g, index);
                                let prototypeInputNode = $(indexedPrototype).find(':input');

                                prototypeInputNode.filter('[id$=checked]')
                                    .attr('checked', 'checked')
                                    .val(attachmentInfo['fileId']);
                                prototypeInputNode.filter('[id$=fileId]').val(attachmentInfo['fileId']);
                                prototypeInputNode.filter('[id$=filename]').val(attachmentInfo['filename']);
                                prototypeInputNode.filter('[id$=filePath]').val(attachmentInfo['filePath']);

                                let labelNode = $('<label class="uk-form-label"></label>')
                                    .attr('for', nodeIDPrefix + 'checked')
                                    .html(attachmentInfo['filename']);

                                index++;

                                let formControlNode = $('<div class="uk-form-controls"></div>')
                                    .append(prototypeInputNode);

                                prototypeNode
                                    .append(formControlNode)
                                    .append(labelNode);
                            }

                            if (attachmentInfoArray.length == 0) {
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

            UIkit.uploadSelect($this.element, merged);
            UIkit.uploadDrop($($this.element).parentsUntil('.uk-placeholder').parent(), merged);
        },
    });
})(UIkit);
