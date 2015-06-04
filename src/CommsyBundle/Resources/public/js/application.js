;(function($, document, window) {
    "use strict";

    $(document).ready(function() {
        $('#sidebar').mouseenter(function() {
            $('body').removeClass('nav-collapsed');
        }).mouseleave(function() {
            $('body').addClass('nav-collapsed');
        });
    });

})(jQuery, document, window);