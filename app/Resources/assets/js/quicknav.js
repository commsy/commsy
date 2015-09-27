;(function(UI){

    "use strict";

    var lastNodeId = "";

    $('.cs-nav-quick').on('inview.uk.scrollspynav', function(event, data) {
        var id = data.attr('id');

        var quickNavTarget = $('.cs-nav-quick a[href="#' + id + '"]');

        if (lastNodeId !== id) {
            lastNodeId = id;
            
            quickNavTarget[0].scrollIntoView();
        }
    });

})(UIkit);