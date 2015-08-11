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
        $('div.cs-toggle')
            .mouseover(function() {
                $(this).toggleClass('cs-toggle-selected', true);
            })
            .mouseout(function() {
                $(this).toggleClass('cs-toggle-selected', false);
            });
    });

})(jQuery, document, window);