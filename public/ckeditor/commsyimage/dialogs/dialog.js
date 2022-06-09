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
                                            label: editor.lang.commsyimage.uploadnew + '<br/>' + '<span style="font-size: 9px;color: red;">' + editor.lang.commsyimage.uploadnewlegend.replace('<quantity_reeplace>', editor.config.maxUploadSize/(1024*1024)) + '</span>',
                                            onChange: function () {
                                                const limit = editor.config.maxUploadSize;
                                                var upload = this.getDialog().getContentElement('imageTab', 'upload');
                                                var inputUpload = upload.getInputElement().$;
                                                var sizeUpload = inputUpload.files[0]? inputUpload.files[0].size: 0;
                                                if(sizeUpload === 0){
                                                 return;
                                                }                                                
                                                if(sizeUpload >= limit){
                                                	upload.setValue("");
                                                	alert(editor.lang.commsyimage.uploadnewfailed);                                                	
                                                } 
              
                                            }
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

                                    onKeyUp: function() {
                                        var dialog = this.getDialog();
                                        var ratioInput = dialog.getContentElement('imageTab', 'keepRatios');

                                        if (ratioInput.getValue()) {
                                            var widthInput = dialog.getContentElement('imageTab', 'width');
                                            var heightInput = dialog.getContentElement('imageTab', 'height');

                                            var ratio = heightInput.getInitValue() / widthInput.getInitValue();

                                            heightInput.setValue(widthInput.getValue() * ratio);
                                        }
                                    },

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

                                    onKeyUp: function() {
                                        var dialog = this.getDialog();
                                        var ratioInput = dialog.getContentElement('imageTab', 'keepRatios');

                                        if (ratioInput.getValue()) {
                                            var widthInput = dialog.getContentElement('imageTab', 'width');
                                            var heightInput = dialog.getContentElement('imageTab', 'height');

                                            var ratio = widthInput.getInitValue() / heightInput.getInitValue();

                                            widthInput.setValue(heightInput.getValue() * ratio);
                                        }
                                    },

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
                        },
                        {
                            id: 'keepRatios',
                            type: 'checkbox',
                            label: editor.lang.commsyimage.keepRatios,
                            'default': 'checked'
                        }
                    ]
                }
            ]
        };
    });
})();
