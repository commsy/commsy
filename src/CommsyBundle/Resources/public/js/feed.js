;(function(UI) {

    "use strict";

    var feedStart = 10;
    $('#feed-load-more').on('inview.uk.scrollspy', function() {
        $.ajax({
          url: "/commsy.php/room/550282/feed/"+feedStart
        })
        .done(function(result) {
            $('#room-feed').append(result);
            feedStart = feedStart + 10;
        });
    });

})(UIkit);