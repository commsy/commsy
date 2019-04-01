;(function() {

    "use strict";

    CKEDITOR.plugins.add('commsyimage', {
        icons: 'commsyimage',
        requires: 'widget',
        lang: 'de,en',

        init: function(editor) {
            editor.widgets.add('commsyimage', {
                button: editor.lang.commsyimage.button,

                template: '<div class="ckeditor-commsy-image"></div>',

                allowedContent: 'div(!ckeditor-commsy-image){text-align,float,margin-left,margin-right}; img[alt,!src,width,height];',
                requiredContent: 'div(ckeditor-commsy-image); img[alt,src];',

                upcast: function(element) {
                    return element.name === 'div' && element.hasClass('ckeditor-commsy-image');
                },

                dialog: 'commsyimage',

                init: function() {
                    var src = '';
                    var alt = '';

                    var width = 400;
                    var height = 300;

                    if (this.element.getChild(0)) {
                        var imageElement = this.element.getChild(0);

                        src = imageElement.getAttribute('src');
                        alt = imageElement.getAttribute('alt');
                        width = imageElement.getAttribute('width');
                        height = imageElement.getAttribute('height');
                    }

                    if (src) {
                        this.setData('src', src);

                        if (alt) {
                            this.setData('alt', alt);
                        }

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
                            var imageElement = new CKEDITOR.dom.element('img');
                            this.element.append(imageElement);
                        }

                        var imageElement = this.element.getChild(0);

                        imageElement.setAttribute('src', this.data.src);

                        if (this.data.width) {
                            imageElement.setAttribute('width', this.data.width);
                        }

                        if (this.data.height) {
                            imageElement.setAttribute('height', this.data.height);
                        }

                        if (this.data.alt) {
                            imageElement.setAttribute('alt', this.data.alt);
                        }
                    }
                }
            });

            // context menu support
            if (editor.contextMenu) {
                editor.addMenuGroup('csImageGroup');
                editor.addMenuItem('csImageItem', {
                    label: editor.lang.commsyimage.properties,
                    icon: 'commsyimage',
                    command: 'commsyimage',
                    group: 'csImageGroup'
                });

                // register the image context menu for each selected <img> element
                editor.contextMenu.addListener(function (element) {
                    if (element &&
                        element.getChild(0) &&
                        element.getChild(0).hasClass &&
                        element.getChild(0).hasClass('ckeditor-commsy-image')) {
                        return { csImageItem: CKEDITOR.TRISTATE_OFF };
                    }
                });
            }

            CKEDITOR.dialog.add('commsyimage', this.path + 'dialogs/dialog.js');
        }
    });
})();