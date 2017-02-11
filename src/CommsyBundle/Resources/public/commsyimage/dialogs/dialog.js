;(function() {

    "use strict";

    CKEDITOR.dialog.add('commsyimage', function (editor) {
        return {
            title: editor.lang.commsyimage.title,
            minWidth: 500,
            minHeight: 200,
            contents: [
                {
                    id: 'imageTab',
                    elements: [
                        {
                            type: 'hbox',
                            widths: ['50%', '50%'],
                            children: [
                                {
                                    type: 'select',
                                    id: 'fileselect',
                                    label: editor.lang.commsyimage.fileselect,
                                    items: [
                                        [editor.lang.commsyimage.fileselectchoice, null, null]
                                    ],
                                    onLoad: function () {
                                        var dialog = this.getDialog();
                                        var filelistUrl = $('*[data-cs-filelisturl]').data("csFilelisturl").path;

                                        if (filelistUrl) {
                                            $.ajax({
                                                url: filelistUrl,
                                            }).done(function (response) {
                                                // fill dropdown with file entries
                                                var fileSelect = dialog.getContentElement('imageTab', 'fileselect');
                                                for (var i = 0; i < response.files.length; i++) {
                                                    var file = response.files[i];

                                                    if (['jpg', 'png', 'gif'].indexOf(file.ext) === -1) {
                                                        continue;
                                                    }

                                                    fileSelect.add(
                                                        file.name,
                                                        file.path,
                                                        file.ext
                                                    );
                                                }
                                            });
                                        }
                                    },
                                    onChange: function () {
                                        // disable textInput if file is selected
                                        var dialog = this.getDialog();
                                        var inputUrl = dialog.getContentElement('imageTab', 'imageUrl');
                                        if (this.getValue() == 'null') {
                                            inputUrl.setValue('');
                                            inputUrl.focus();
                                        } else {
                                            // set file url in textInput;
                                            var encodeFileUrl = encodeURI(this.getValue());
                                            inputUrl.setValue(encodeFileUrl);
                                        }
                                    }
                                },
                                {
                                    type: 'vbox',
                                    children: [
                                        {
                                            type: 'file',
                                            id: 'upload',
                                            label: editor.lang.commsyimage.uploadnew,
                                        },
                                        {
                                            type: 'fileButton',
                                            id: 'uploadButton',
                                            filebrowser: 'imageTab:imageUrl',
                                            label: editor.lang.commsyimage.upload,
                                            'for': ['imageTab', 'upload']
                                        }
                                    ]
                                },
                            ]
                        },
                        {
                            type: 'hbox',
                            widths: ['100%'],
                            children: [
                                {
                                    id: 'imageUrl',
                                    type: 'text',
                                    label: editor.lang.commsyimage.url,
                                    required: true,
                                    validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.commsyimage.emptyUrl),
                                    setup: function (widget) {
                                        this.setValue(widget.data.src);
                                    },
                                    commit: function (widget) {
                                        widget.setData('src', this.getValue());
                                    }
                                }
                            ]
                        },
                        {
                            type: 'hbox',
                            children: [
                                {
                                    id: 'alt',
                                    type: 'text',
                                    label: 'Alt',
                                    setup: function (widget) {
                                        if (widget.data.alt) {
                                            this.setValue(widget.data.alt);
                                        }
                                    },
                                    commit: function (widget) {
                                        widget.setData('alt', this.getValue());
                                    }
                                }
                            ]
                        },
                        {
                            type: 'hbox',
                            children: [
                                {
                                    type: 'text',
                                    id: 'width',
                                    label: editor.lang.commsyimage.width,
                                    'default': '400',

                                    commit: function (widget) {
                                        widget.setData('width', this.getValue());
                                    },

                                    setup: function (widget) {
                                        if (widget.data.width) {
                                            this.setValue(widget.data.width);
                                        }
                                    }
                                },
                                {
                                    type: 'text',
                                    id: 'height',
                                    label: editor.lang.commsyimage.height,
                                    'default': '300',

                                    commit: function (widget) {
                                        widget.setData('height', this.getValue());
                                    },

                                    setup: function (widget) {
                                        if (widget.data.height) {
                                            this.setValue(widget.data.height);
                                        }
                                    }
                                }
                            ]
                        }
                    ]
                }
            ]
        };
    });
})();