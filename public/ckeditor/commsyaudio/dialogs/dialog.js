;(function () {

    "use strict";

    CKEDITOR.dialog.add('commsyaudio', function (editor) {

        return {
            title: editor.lang.commsyaudio.title,
            minWidth: 500,
            minHeight: 200,
            contents: [
                {
                    id: 'audioTab',
                    elements: [
                        {
                            type: 'hbox',
                            widths: ['50%', '50%'],
                            children: [
                                {
                                    type: 'select',
                                    id: 'fileselect',
                                    label: editor.lang.commsyaudio.fileselect,
                                    items: [
                                        [editor.lang.commsyaudio.fileselectchoice, null, null]
                                    ],
                                    onLoad: function () {
                                        var dialog = this.getDialog();
                                        var filelistUrl = $('*[data-cs-filelisturl]').data("csFilelisturl").path;

                                        if (filelistUrl) {
                                            $.ajax({
                                                url: filelistUrl,
                                            }).done(function (response) {
                                                // fill dropdown with file entries
                                                var fileSelect = dialog.getContentElement('audioTab', 'fileselect');
                                                for (var i = 0; i < response.files.length; i++) {
                                                    var file = response.files[i];

                                                    if (['mp3', 'wav', 'ogg'].indexOf(file.ext) === -1) {
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
                                        var inputUrl = dialog.getContentElement('audioTab', 'audioUrl');
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
                                            label: editor.lang.commsyaudio.uploadnew
                                        },
                                        {
                                            type: 'fileButton',
                                            id: 'uploadButton',
                                            filebrowser: 'audioTab:audioUrl',
                                            label: editor.lang.commsyaudio.upload,
                                            'for': ['audioTab', 'upload']
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
                                    id: 'audioUrl',
                                    type: 'text',
                                    label: editor.lang.commsyaudio.urlcode,
                                    required: true,
                                    validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.commsyaudio.emptyUrl),
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
                                    type: 'text',
                                    id: 'width',
                                    label: editor.lang.commsyaudio.width,
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
                                    label: editor.lang.commsyaudio.height,
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