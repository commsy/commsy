;(function($, document, window) {
    "use strict";

    $(document).ready(function() {
        $('#sidebar').mouseenter(function() {
            $('body').removeClass('nav-collapsed');
        }).mouseleave(function() {
            $('body').addClass('nav-collapsed');
        });
    });

    // highligh sections that can be toggled
    $(document).ready(function() {
        $('div.cs-detail-toggle')
            .mouseover(function() {
                $(this).toggleClass('cs-detail-toggle-selected', true);
            })
            .mouseout(function() {
                $(this).toggleClass('cs-detail-toggle-selected', false);
            });
    });

})(jQuery, document, window);