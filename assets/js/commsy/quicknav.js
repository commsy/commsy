import UIkit from 'uikit';

;(function(UI){

    "use strict";

    var lastNodeId = "";

    $('.cs-nav-quick').on('inview.uk.scrollspynav', function(event, data) {
        var id = data.attr('id');

        if (lastNodeId !== id) {
            lastNodeId = id;

            var containerOffset = $('#cs-nav-quick').offset().top;

            var quickNavTarget = $('.cs-nav-quick a[href="#' + id + '"]');
            var offset = $(quickNavTarget[0]).offset().top;

            $('#cs-nav-quick').scrollTop(offset - containerOffset);
        }
    });

})(UIkit);