;(function($, document, window) {
    "use strict";

    $(".cke_textarea_inline").prev("textarea").insertAfter($(".cke_textarea_inline")).css({"width": "1px", "height": "1px", "opacity":"0", "display":"inline"});

})(jQuery, document, window);