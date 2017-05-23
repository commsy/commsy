;(function() {

    "use strict";

    CKEDITOR.plugins.add('commsyhelp', {
        icons: 'commsyhelp',
        lang: 'de,en',

        init: function(editor) {
            editor.addCommand('commsyhelp', new CKEDITOR.dialogCommand('commsyhelpDialog'));
            editor.ui.addButton('Commsyhelp', {
                label: editor.lang.commsyhelp.help,
                command: 'commsyhelp'
            });

            CKEDITOR.dialog.add('commsyhelpDialog', this.path + 'dialogs/dialog.js');
        }
    });
})();