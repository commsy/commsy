CKEDITOR.plugins.add('CommSyVideo', {
    icons: 'icon',

    // afterInit: function (editor) {
    //     function createFakeElement(editor, realElement) {
    //         return editor.createFakeParserElement(realElement, 'cke_video', 'video', true);
    //     }
    //
    //     var dataProcessor = editor.dataProcessor,
    //         dataFilter = dataProcessor && dataProcessor.dataFilter;
    //     if (dataFilter) {
    //         dataFilter.addRules(
    //             {
    //                 elements: {
    //                     'div': function (element) {
    //                         //alert("here");
    //                         var attributes = element.attributes;
    //                         if (attributes['class'] == 'commsyPlayer') {
    //                             //alert("here");
    //
    //                             return createFakeElement(editor, element);
    //                         }
    //                         return null;
    //
    //                     }
    //                 }
    //             },
    //             5);
    //     }
    // },
    //
    // onLoad: function () {
    //     CKEDITOR.addCss('img.cke_video' +
    //         '{' +
    //         'background-image: url(' + CKEDITOR.getUrl(this.path + 'icons/placeholder.png') + ');' +
    //         'background-position: center center;' +
    //         'background-repeat: no-repeat;' +
    //         'border: 1px solid #a9a9a9;' +
    //         'width: 640px;' +
    //         'height: 360px;' +
    //         '}'
    //     );
    //
    // },

    init: function(editor) {
        editor.addCommand("CommSyVideo", new CKEDITOR.dialogCommand("CommSyVideoDialog", {
            allowedContent: 'video[*]{*}(*);source[*]{*}(*)'
        }));

        editor.ui.addButton("CommSyVideo", {
            label: "Video",
            command: "CommSyVideo",
            icon: this.path + 'icons/icon.png'
        });

        // context menu support
        if (editor.contextMenu) {
            editor.addMenuGroup('csVideoGroup');
            editor.addMenuItem('csVideoItem', {
                label: 'CommSy Video',
                icon: this.path + 'icons/icon.png',
                command: 'CommSyVideo',
                group: 'csVideoGroup'
            });

            // register the video context menu for each selected <video> element
            editor.contextMenu.addListener(function (element) {
                if (element.getAscendant('video', true)) {
                    return { csVideoItem: CKEDITOR.TRISTATE_OFF };
                }
            });
        }

        CKEDITOR.dialog.add('CommSyVideoDialog', this.path + 'dialogs/dialog.js');
    }
});