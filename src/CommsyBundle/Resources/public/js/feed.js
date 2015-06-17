;(function(UI) {

    "use strict";

    $('#feed-load-more').on('inview.uk.scrollspy', function() {
        $.ajax({
          url: "/commsy.php/room/550282/feed/20"
        })
        .done(function(result) {
            $('#room-feed').append(result);
        });
    });

})(UIkit);