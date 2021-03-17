;(function($, document, window) {
    "use strict";

    $(document).ready(function() {
        $('.js-select2-choice').select2();

        // overwrite select2's default behavior for a select control (where enter would open the select control)
        // TODO: while this works for single-select controls, multi-select controls only seem to trigger keyup events
        $(document).on('keydown', '.select2-selection', function (evt) {
            if (evt.key === 'Enter') { // keyCode === 13
                // keep the select control closed
                $('.js-select2-choice').select2('close');

                // submit the hosting form
                $(evt.currentTarget).parents('form').submit();
            }
        });
    });

})(jQuery, document, window);