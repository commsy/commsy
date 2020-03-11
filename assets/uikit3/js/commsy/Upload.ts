'use strict';

import * as UIkit from 'uikit';

export class Upload {
    private options = {
        'url': '',
        'multiple': true,
    };

    public static bootstrap() {
        $(".js-upload").each(function() {
            let upload = new Upload();
            upload.init(this);
        });
    }

    private init(ele) {
        let self = this;

        let $element = $(ele);
        self.options = $.extend(self.options, $element.data('upload-options'));

        let progressBar: any = $element.parent().find('progress.uk-progress').get(0);

        UIkit.upload($element, {
            url: self.options.url,
            multiple: self.options.multiple,

            beforeSend: function (environment) {
                console.log('beforeSend', arguments);

                // The environment object can still be modified here.
                // var {data, method, headers, xhr, responseType} = environment;

            },
            beforeAll: function () {
                console.log('beforeAll', arguments);
            },
            load: function () {
                console.log('load', arguments);
            },
            error: function () {
                console.log('error', arguments);
            },
            complete: function () {
                console.log('complete', arguments);
            },

            loadStart: function (e) {
                console.log('loadStart', arguments);

                progressBar.removeAttribute('hidden');
                progressBar.max = e.total;
                progressBar.value = e.loaded;
            },

            progress: function (e) {
//                     percent = Math.ceil(percent);
                console.log('progress', arguments);

                progressBar.max = e.total;
                progressBar.value = e.loaded;
            },

            loadEnd: function (e) {
                console.log('loadEnd', arguments);

                progressBar.max = e.total;
                progressBar.value = e.loaded;
            },

            completeAll: function () {
                console.log('completeAll', arguments);

                setTimeout(function () {
                    progressBar.setAttribute('hidden', 'hidden');
                }, 1000);

                alert('Upload Completed');
            }
        });
    }
}






//         defaults: {
//             path: '',
//             settings: {
//                 allow: '*.*'
//             },
//             errorMessage: '',
//             noFileIdsMessage: '',
//         },
//
//         init: function() {
//             let $this = this;
//
//             let $progressbar = $($this.element).parentsUntil('.uk-placeholder').parent().siblings('.uk-progress').first();
//             let $bar = $progressbar.find('.uk-progress-bar');
//
//             let elementSettings = {
//                 action: $this.options.path,
//                 single: false,
//
//
//
//                 allcomplete: function(response, xhr) {
//                     if (xhr.status != 200) {
//                         this.onError();
//                     } else {
//                         $bar.css("width", "100%").text("100%");
//
//                         setTimeout(function(){
//                             $progressbar.addClass("uk-hidden");
//                         }, 250);
//
//                         let responseData = JSON.parse(response);
//
//                         if (responseData['userImage']) {
//                             $('#profile_form_user_image').attr('src', responseData['userImage'] + '?' + Math.random());
//                         } else if (responseData['base64']) {
//                             let $form = $($this.element).closest('form');
//
//                             let prototypeNode = $form.find('div[data-prototype]');
//                             let prototype = prototypeNode.data('prototype');
//
//                             let index = prototypeNode.find(':input[type="checkbox"]').length;
//
//                             for (let key in responseData['base64']) {
//
//                                 let indexedPrototype = prototype.replace(/__name__/g, index);
//
//                                 let prototypeInputNode = $(indexedPrototype).find(':input');
//                                 prototypeInputNode.attr('checked', 'checked');
//                                 prototypeInputNode.val(responseData['base64'][key]['content']);
//
//                                 let labelNode = $('<label class="uk-form-label"></label>')
//                                     .attr('for', $form.attr('name') + '_base64' + index + '_checked')
//                                     .html(responseData['base64'][key]['filename']);
//
//                                 index++;
//
//                                 let formControlNode = $('<div class="uk-form-controls"></div>')
//                                     .append(prototypeInputNode);
//
//                                 prototypeNode
//                                     .append(formControlNode)
//                                     .append(labelNode);
//                             }
//
//                             if (responseData['base64'].length == 0) {
//                                 UIkit.notify($this.options.noFileIdsMessage, 'danger');
//                             }
//
//                         } else if (responseData['fileIds']) {
//                             let prototypeNode = $('form[name="upload"] div[data-prototype]');
//                             let prototype = prototypeNode.data('prototype');
//
//                             let index = prototypeNode.find(':input[type="checkbox"]').length;
//
//                             for (let key in responseData['fileIds']) {
//
//                                 let indexedPrototype = prototype.replace(/__name__/g, index);
//
//                                 let prototypeInputNode = $(indexedPrototype).find(':input');
//                                 prototypeInputNode.attr('checked', 'checked');
//                                 prototypeInputNode.val(key);
//
//                                 let labelNode = $('<label class="uk-form-label"></label>')
//                                     .attr('for', 'upload_files_' + index + '_checked')
//                                     .html(responseData['fileIds'][key]);
//
//                                 index++;
//
//                                 let formControlNode = $('<div class="uk-form-controls"></div>')
//                                     .append(prototypeInputNode);
//
//                                 prototypeNode
//                                     .append(formControlNode)
//                                     .append(labelNode);
//                             }
//
//                             if (responseData['fileIds'].length == 0) {
//                                 UIkit.notify($this.options.noFileIdsMessage, 'danger');
//                             }
//                         }
//                     }
//                 },
//
//                 error: function(event) {
//                     this.onError();
//                 },
//
//                 onError: function() {
//                     $bar.css("width", "100%").text("100%");
//
//                     setTimeout(function(){
//                         $progressbar.addClass("uk-hidden");
//                     }, 250);
//
//                     UIkit.notify($this.options.errorMessage, 'danger');
//                 }
//             };
//
//             let merged = $.extend($this.options.settings, elementSettings);
//
//             let select = UI.uploadSelect($this.element, merged);
//             let drop = UI.uploadDrop($($this.element).parentsUntil('.uk-placeholder').parent(), merged);
//         },