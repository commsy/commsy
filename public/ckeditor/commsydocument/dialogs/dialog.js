CKEDITOR.dialog.add('commsydocument', function (editor) {

    var slideshareVidId = function (url) {
        var p = /^\[slideshare\sid=(\d+)?(?:\S+)?$/;
        return ( url.match(p) ) ? RegExp.$1 : false;
    };

    return {
        title: editor.lang.commsydocument.title,
        minWidth: 500,
        minHeight: 200,
        contents: [
            {
                id: 'videoTab',
                elements: [
                    {
                        type: 'html',
                        html:
                        '<div>' +
                        editor.lang.commsydocument.helpintro +
                        '<ul>' +
                        '<li>' + editor.lang.commsydocument.helpslideshare + '</li>' +
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
                                label: editor.lang.commsydocument.urlcode,
                                required: true,
                                validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.commsydocument.emptyUrl),
                                setup: function (widget) {
                                    this.setValue(widget.data.src);
                                },
                                commit: function (widget) {
                                    var slideshareId = slideshareVidId(this.getValue());
                                    if (slideshareId) {
                                        widget.setData('type', 'slideshare');
                                        widget.setData('src', slideshareId);
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
                                label: editor.lang.commsydocument.width,
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
                                label: editor.lang.commsydocument.height,
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
