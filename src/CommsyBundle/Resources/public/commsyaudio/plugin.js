;(function() {

    "use strict";

    CKEDITOR.plugins.add('commsyaudio', {
        icons: 'commsyaudio',
        requires: 'widget',
        lang: 'de,en',

        init: function(editor) {
            editor.widgets.add('commsyaudio', {
                button: editor.lang.commsyaudio.button,

                template: '<div class="ckeditor-commsy-audio"></div>',

                allowedContent: 'div[data-responsive](!ckeditor-commsy-audio); audio[src,controls,width,height]{max-width,height};',
                requiredContent: 'div(ckeditor-commsy-audio); audio[src,controls];',

                upcast: function(element) {
                    return element.name === 'div' && element.hasClass('ckeditor-commsy-audio');
                },

                dialog: 'commsyaudio',

                init: function() {
                    var src = '';

                    var width = 400;
                    var height = 300;

                    if (this.element.getChild(0)) {
                        var audioElement = this.element.getChild(0);

                        src = audioElement.getAttribute('src');
                        width = audioElement.getAttribute('width');
                        height = audioElement.getAttribute('height');
                    }

                    if (src) {
                        this.setData('src', src);

                        if (width) {
                            this.setData('width', width);
                        }

                        if (height) {
                            this.setData('height', height);
                        }
                    }
                },
                data: function() {
                    if (this.data.src) {
                        if (!this.element.getChild(0)) {
                            var audioElement = new CKEDITOR.dom.element('audio');
                            audioElement.setAttribute('controls', true);
                            audioElement.addClass('video-js');
                            audioElement.addClass('vjs-default-skin');

                            this.element.append(audioElement);
                        }

                        var audioElement = this.element.getChild(0);

                        audioElement.setAttribute('src', this.data.src);

                        if (this.data.width) {
                            audioElement.setAttribute('width', this.data.width);
                        }

                        if (this.data.height) {
                            audioElement.setAttribute('height', this.data.height);
                        }
                    }
                }
            });

            // context menu support
            if (editor.contextMenu) {
                editor.addMenuGroup('csAudioGroup');
                editor.addMenuItem('csAudioItem', {
                    label: editor.lang.commsyaudio.properties,
                    icon: 'commsyaudio',
                    command: 'commsyaudio',
                    group: 'csAudioGroup'
                });

                // register the video context menu for each selected <video> element
                editor.contextMenu.addListener(function (element) {
                    if (element &&
                        element.getChild(0) &&
                        element.getChild(0).hasClass &&
                        element.getChild(0).hasClass('ckeditor-commsy-audio')) {
                        return { csAudioItem: CKEDITOR.TRISTATE_OFF };
                    }
                });
            }

            CKEDITOR.dialog.add('commsyaudio', this.path + 'dialogs/dialog.js');
        }
    });
})();