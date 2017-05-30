;(function() {

    "use strict";

    CKEDITOR.plugins.add('commsydocument', {
        icons: 'commsydocument',
        requires: 'widget',
        lang: 'de,en',

        init: function (editor) {
            editor.widgets.add('commsydocument', {
                button: editor.lang.commsydocument.button,

                template: '<div class="ckeditor-commsy-document"></div>',

                allowedContent:
                    'div[data-type](!ckeditor-commsy-document);' +
                    'iframe[src,frameborder,width,height]',
                requiredContent: 'div(ckeditor-commsy-document);',

                upcast: function (element) {
                    return element.name === 'div' && element.hasClass('ckeditor-commsy-document');
                },

                dialog: 'commsydocument',

                /**
                 * HTML -> Widget
                 */
                init: function () {
                    var src = '';

                    var width = 400;
                    var height = 300;

                    var type = '';

                    if (this.element.getChild(0)) {
                        var documentElement = this.element.getChild(0);

                        src = documentElement.getAttribute('src');
                        width = documentElement.getAttribute('width');
                        height = documentElement.getAttribute('height');
                        type = documentElement.getAttribute('data-type');
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
                            case 'slideshare':
                                this.slideshareData(this.element);
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

                slideshareData: function (divElement) {
                    if (!divElement.getChild(0)) {
                        var frameElement = new CKEDITOR.dom.element('iframe');
                        frameElement.setAttribute('allowfullscreen', true);
                        frameElement.setAttribute('frameborder', '0');

                        divElement.append(frameElement);
                    }

                    var frameElement = divElement.getChild(0);
                    frameElement.setAttribute('src', 'https://www.slideshare.net/slideshow/embed_code/' + this.data.src);
                },
            });

            // context menu support
            if (editor.contextMenu) {
                editor.addMenuGroup('csDocumentGroup');
                editor.addMenuItem('csDocumentItem', {
                    label: editor.lang.commsydocument.properties,
                    icon: 'commsydocument',
                    command: 'commsydocument',
                    group: 'csDocumentGroup'
                });

                // register the document context menu for each selected <div> element
                editor.contextMenu.addListener(function (element) {
                    if (element &&
                        element.getChild(0) &&
                        element.getChild(0).hasClass &&
                        element.getChild(0).hasClass('ckeditor-commsy-document')) {
                        return { csDocumentItem: CKEDITOR.TRISTATE_OFF };
                    }
                });
            }

            CKEDITOR.dialog.add('commsydocument', this.path + 'dialogs/dialog.js');
        }
    });
})();