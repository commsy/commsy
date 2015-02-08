;(function($, window, document, undefined) {
    "use strict"

    $('.left.sidebar')
        .sidebar('attach events', '.toggle.navigation');

    $('select.dropdown')
        .dropdown();
})(jQuery, window, document);