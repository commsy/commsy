;(function(UI) {

    "use strict";

    // UIkit 2 opens the lightbox as a non-modal dialog, which its ESC handler
    // ignores. Close an open lightbox on ESC here.
    UI.$doc.on('keydown.commsy.lightbox', function(e) {
        if (e.key !== 'Escape' && e.keyCode !== 27) {
            return;
        }

        const lightbox = UI.$('.uk-modal.uk-open').filter(function() {
            return UI.$(this).find('.uk-modal-dialog-lightbox').length > 0;
        });

        if (lightbox.length) {
            e.preventDefault();
            UI.modal(lightbox).hide();
        }
    });

})(UIkit);
