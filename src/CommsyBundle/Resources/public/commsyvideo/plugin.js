;(function() {

    "use strict";

    CKEDITOR.plugins.add('commsyvideo', {
        icons: 'commsyvideo',
        requires: 'widget',
        lang: 'de,en',

        init: function (editor) {
            editor.widgets.add('commsyvideo', {
                button: editor.lang.commsyvideo.button,

                template: '<div class="ckeditor-commsy-video"></div>',

                allowedContent:
                    'div[data-type](!ckeditor-commsy-video);' +
                    'video[src,controls,width,height]{max-width,height};' +
                    'iframe[src,frameborder,width,height]',
                requiredContent: 'div(ckeditor-commsy-video); video[src,controls];',

                upcast: function (element) {
                    return element.name === 'div' && element.hasClass('ckeditor-commsy-video');
                },

                dialog: 'commsyvideo',

                /**
                 * HTML -> Widget
                 */
                init: function () {
                    var src = '';

                    var width = 400;
                    var height = 300;

                    var type = '';

                    if (this.element.getChild(0)) {
                        var videoElement = this.element.getChild(0);

                        src = videoElement.getAttribute('src');
                        width = videoElement.getAttribute('width');
                        height = videoElement.getAttribute('height');
                        type = videoElement.getAttribute('data-type');
                    }

                    if (src) {
                        this.setData('src', src);

                        if (width) {
                            this.setData('width', width);
                        }

                        if (height) {
                            this.setData('height', height);
                        }

                        if (type) {
                            this.setData('type', type);
                        }
                    }
                },

                /**
                 * Widget -> HTML
                 */
                data: function () {
                    if (this.data.src) {

                        switch (this.data.type) {
                            case 'commsy':
                                this.commsyData(this.element);
                                break;
                            case 'youtube':
                                this.youtubeData(this.element);
                                break;
                            case 'podcampus':
                                this.podcampusData(this.element);
                                break;
                            case 'l2g':
                                this.l2gData(this.element);
                                break;
                        }

                        this.element.setAttribute('data-type', this.data.type);

                        var innerElement = this.element.getChild(0);

                        if (this.data.width) {
                            innerElement.setAttribute('width', this.data.width);
                        }

                        if (this.data.height) {
                            innerElement.setAttribute('height', this.data.height);
                        }
                    }
                },

                commsyData: function (divElement) {
                    if (!divElement.getChild(0)) {
                        var videoElement = new CKEDITOR.dom.element('video');
                        videoElement.setAttribute('controls', true);
                        videoElement.addClass('video-js');
                        videoElement.addClass('vjs-default-skin');

                        divElement.append(videoElement);
                    }

                    var videoElement = divElement.getChild(0);
                    videoElement.setAttribute('src', this.data.src);
                },

                youtubeData: function (divElement) {
                    if (!divElement.getChild(0)) {
                        var frameElement = new CKEDITOR.dom.element('iframe');
                        frameElement.setAttribute('allowfullscreen', true);
                        frameElement.setAttribute('frameborder', '0');

                        divElement.append(frameElement);
                    }

                    var frameElement = divElement.getChild(0);
                    frameElement.setAttribute('src', 'https://www.youtube.com/embed/' + this.data.src);
                },

                podcampusData: function (divElement) {
                    if (!divElement.getChild(0)) {
                        var frameElement = new CKEDITOR.dom.element('iframe');
                        frameElement.setAttribute('allowfullscreen', true);
                        frameElement.setAttribute('frameborder', '0');

                        divElement.append(frameElement);
                    }

                    var frameElement = divElement.getChild(0);
                    frameElement.setAttribute('src', 'https://www.podcampus.de/nodes/' + this.data.src + '/embed');
                },

                l2gData: function (divElement) {
                    if (!divElement.getChild(0)) {
                        var frameElement = new CKEDITOR.dom.element('iframe');
                        frameElement.setAttribute('allowfullscreen', true);
                        frameElement.setAttribute('frameborder', '0');

                        divElement.append(frameElement);
                    }

                    var frameElement = divElement.getChild(0);
                    frameElement.setAttribute('src', 'https://lecture2go.uni-hamburg.de/lecture2go-portlet/player/iframe/?v=' + this.data.src);
                }
            });

            // context menu support
            if (editor.contextMenu) {
                editor.addMenuGroup('csVideoGroup');
                editor.addMenuItem('csVideoItem', {
                    label: editor.lang.commsyvideo.properties,
                    icon: 'commsyvideo',
                    command: 'commsyvideo',
                    group: 'csVideoGroup'
                });

                // register the video context menu for each selected <video> element
                editor.contextMenu.addListener(function (element) {
                    if (element &&
                        element.getChild(0) &&
                        element.getChild(0).hasClass &&
                        element.getChild(0).hasClass('ckeditor-commsy-video')) {
                        return { csVideoItem: CKEDITOR.TRISTATE_OFF };
                    }
                });
            }

            CKEDITOR.dialog.add('commsyvideo', this.path + 'dialogs/dialog.js');
        }
    });
})();