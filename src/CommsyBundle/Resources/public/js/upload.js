;(function(UI){

    "use strict";

    var settings = {
        action: '/',
        allow: '*.*',

        loadstart: function() {

        },

        progress: function(percent) {

        },

        allcomplete: function(response) {

        }
    };

    // var select = UIkit.uploadSelect($("#upload-select"), settings);
    // var drop = UIkit.uploadDrop($("#upload-drop"), settings);

    UIkit.on('beforeready.uk.dom', function() {
        $('.upload').each(function() {
            var select = UI.uploadSelect(this, settings);
            var drop = UI.uploadDrop($(this).find('input'), settings);

        });
    });

})(UIkit);