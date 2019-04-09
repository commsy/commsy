;(function () {

    "use strict";

    CKEDITOR.dialog.add('commsyvideo', function (editor) {

        /**
         * JavaScript function to match (and return) the video Id
         * of any valid Youtube Url, given as input string.
         * @author: Stephan Schmitz <eyecatchup@gmail.com>
         * @url: http://stackoverflow.com/a/10315969/624466
         */
        var ytVidId = function (url) {
            var p = /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/;
            return ( url.match(p) ) ? RegExp.$1 : false;
        };

        var videoTag = function (url) {
            var p = /(?:<source[^>]+src=[\"\'])([^\"\']+)/;
            return ( url.match(p) ) ? RegExp.$1 : false;
        };

        var podcampusVidId = function (url) {
            var p = /^(?:https?:\/\/)?(?:www\.)?(?:podcampus\.de\/nodes\/)((\w)+)(?:\S+)?$/;
            return ( url.match(p) ) ? RegExp.$1 : false;
        };

        var l2gTag = function (url) {
            var p = /^(?:\(:lecture2go (\S+):\))$/;
            return ( url.match(p) ) ? RegExp.$1 : false;
        };

        var origHeight;
        var origWidth;

        return {
            title: editor.lang.commsyvideo.title,
            minWidth: 500,
            minHeight: 200,
            contents: [
                {
                    id: 'videoTab',
                    elements: [
                        {
                            type: 'hbox',
                            widths: ['50%', '50%'],
                            children: [
                                {
                                    type: 'select',
                                    id: 'fileselect',
                                    label: editor.lang.commsyvideo.fileselect,
                                    items: [
                                        [editor.lang.commsyvideo.fileselectchoice, null, null]
                                    ],
                                    onLoad: function () {
                                        var dialog = this.getDialog();
                                        var filelistUrl = $('*[data-cs-filelisturl]').data("csFilelisturl").path;

                                        if (filelistUrl) {
                                            $.ajax({
                                                url: filelistUrl,
                                            }).done(function (response) {
                                                // fill dropdown with file entries
                                                var fileSelect = dialog.getContentElement('videoTab', 'fileselect');
                                                for (var i = 0; i < response.files.length; i++) {
                                                    var file = response.files[i];

                                                    if (['mp4', 'webm', 'ogg'].indexOf(file.ext) === -1) {
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
                                        var inputUrl = dialog.getContentElement('videoTab', 'videoUrl');
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
                                            label: editor.lang.commsyvideo.uploadnew
                                        },
                                        {
                                            type: 'fileButton',
                                            id: 'uploadButton',
                                            filebrowser: 'videoTab:videoUrl',
                                            label: editor.lang.commsyvideo.upload,
                                            'for': ['videoTab', 'upload']
                                        }
                                    ]
                                },
                            ]
                        },
                        {
                            type: 'html',
                            html:
                                '<div>' +
                                    editor.lang.commsyvideo.helpintro +
                                    '<ul>' +
                                        '<li>' + editor.lang.commsyvideo.helpdirect + '</li>' +
                                        '<li>' + editor.lang.commsyvideo.helpyoutube + '</li>' +
                                        '<li>' + editor.lang.commsyvideo.helplecture2go + '</li>' +
                                        '<li>' + editor.lang.commsyvideo.helppodcampus + '</li>' +
                                    '</ul>' +
                                '</div>'
                        },
                        {
                            type: 'hbox',
                            widths: ['100%'],
                            children: [
                                {
                                    id: 'videoUrl',
                                    type: 'text',
                                    label: editor.lang.commsyvideo.urlcode,
                                    required: true,
                                    validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.commsyvideo.emptyUrl),
                                    setup: function (widget) {
                                        this.setValue(widget.data.src);
                                    },
                                    commit: function (widget) {
                                        var youTubeId = ytVidId(this.getValue());
                                        if (youTubeId) {
                                            widget.setData('type', 'youtube');
                                            widget.setData('src', youTubeId);
                                            return;
                                        }

                                        var podcampusId = podcampusVidId(this.getValue());
                                        if (podcampusId) {
                                            widget.setData('type', 'podcampus');
                                            widget.setData('src', podcampusId);
                                            return;
                                        }

                                        var videoSrc = videoTag(this.getValue());
                                        if (videoSrc) {
                                            widget.setData('type', 'commsy');
                                            widget.setData('src', videoSrc);
                                            return;
                                        }

                                        var l2gSrc = l2gTag(this.getValue());
                                        if (l2gSrc) {
                                            widget.setData('type', 'l2g');
                                            widget.setData('src', l2gSrc);
                                            return;
                                        }

                                        widget.setData('type', 'commsy');
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
                                    label: editor.lang.commsyvideo.width,
                                    'default': '100%',

                                    commit: function (widget) {
                                        widget.setData('width', this.getValue());
                                    },

                                    setup: function (widget) {
                                        if (widget.data.width) {
                                            this.setValue(widget.data.width);
                                        }
                                    },
                                    onLoad: function(){
                                        origWidth = this.getDialog().getContentElement('videoTab', 'width').getValue();
                                    },
                                    onBlur: function () {
                                        var dialog = this.getDialog();
                                        var selection = dialog.getContentElement('videoTab', 'scaling');
                                        var width = this.getDialog().getContentElement('videoTab', 'width').getValue();
                                        var height = this.getDialog().getContentElement('videoTab', 'height').getValue();
                                        var heightPercent = height.includes('%');

                                        width = width.replace('%', '');
                                        height = height.replace('%', '');
                                        origHeight = origHeight.replace('%', '');
                                        origWidth = origWidth.replace('%', '');

                                        if(selection.getValue().includes(editor.lang.commsyvideo.chained)){
                                            var factor = width / origWidth;
                                            var factoredWidth = height * factor;
                                            factoredWidth = factoredWidth.toFixed(2);
                                            if(heightPercent){
                                                factoredWidth = factoredWidth + "%";
                                            }
                                            this.getDialog().getContentElement('videoTab', 'height').setValue(factoredWidth);
                                        }
                                        if(selection.getValue().includes('16:9')){
                                            var factor = 9/16;
                                            var factoredWidth = width * factor;
                                            factoredWidth = factoredWidth.toFixed(2);
                                            if(heightPercent){
                                                factoredWidth = factoredWidth + "%";
                                            }
                                            this.getDialog().getContentElement('videoTab', 'height').setValue(factoredWidth);
                                        }

                                        origHeight = this.getDialog().getContentElement('videoTab', 'height').getValue();
                                        origWidth = this.getDialog().getContentElement('videoTab', 'width').getValue();

                                        }
                                },
                                {
                                    type: 'text',
                                    id: 'height',
                                    label: editor.lang.commsyvideo.height,
                                    'default': '400',

                                    commit: function (widget) {
                                        widget.setData('height', this.getValue());
                                    },
                                    onLoad : function(){
                                        origHeight = this.getDialog().getContentElement('videoTab', 'height').getValue();
                                    },
                                    setup: function (widget) {
                                       this.setValue(Math.trunc($(window).width() * 0.4));
                                        if (widget.data.height) {
                                            this.setValue(widget.data.height);
                                        }
                                    },
                                    onBlur: function () {
                                        var dialog = this.getDialog();
                                        var selection = dialog.getContentElement('videoTab', 'scaling');
                                        var height = this.getDialog().getContentElement('videoTab', 'height').getValue();
                                        var width = this.getDialog().getContentElement('videoTab', 'width').getValue();
                                        var widthPercent = width.includes('%');

                                        height = height.replace('%','');
                                        width = width.replace('%','');
                                        origHeight = origHeight.replace('%', '');
                                        origWidth = origWidth.replace('%', '');

                                        if(selection.getValue().includes(editor.lang.commsyvideo.chained)){
                                            var factor = height / origHeight;
                                            var factoredHeight = width * factor;
                                            factoredHeight = factoredHeight.toFixed(2);
                                            if(widthPercent){
                                                factoredHeight = factoredHeight + "%";
                                            }
                                            this.getDialog().getContentElement('videoTab', 'width').setValue(factoredHeight);
                                        }
                                        if(selection.getValue().includes('16:9')){
                                            var factor =  16/9;
                                            var factoredHeight = height * factor;
                                            factoredHeight = factoredHeight.toFixed(2);
                                            if(widthPercent){
                                                factoredHeight = factoredHeight + "%";
                                            }
                                            this.getDialog().getContentElement('videoTab', 'width').setValue(factoredHeight);
                                        }

                                        origHeight = this.getDialog().getContentElement('videoTab', 'height').getValue();
                                        origWidth = this.getDialog().getContentElement('videoTab', 'width').getValue();

                                    }
                                },
                                {
                                    type: 'select',
                                    id: 'scaling',
                                    label: editor.lang.commsyvideo.scaling,
                                    items: [ [ editor.lang.commsyvideo.chained ], [ '16:9' ], [ editor.lang.commsyvideo.freestyle ] ],
                                    'default': editor.lang.commsyvideo.freestyle,
                                    onChange: function() {
                                        if(this.getValue().includes('16:9')){
                                            var factor = 16/9;
                                            var currentHeight = this.getDialog().getContentElement('videoTab', 'height').getValue();
                                            var heightPercent = currentHeight.includes('%');
                                            currentHeight = currentHeight.replace('%', '');
                                            var resized = currentHeight * factor;
                                            resized = resized.toFixed(2);
                                            if(heightPercent){
                                                resized = resized + '%';
                                            }
                                            this.getDialog().getContentElement('videoTab', 'width').setValue(resized);
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