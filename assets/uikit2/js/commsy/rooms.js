;(function(UI) {

    "use strict";

    var feedStart = 10;

    // listen to "inview.uk.scrollspy" event on "feed-load-more" classes
    $('.room-load-more').on('inview.uk.scrollspy', function() {
        var el = $(this);

        // get current query string
        var queryString = document.location.search;

        // build up the url
        var url = el.data('rooms').url  + feedStart + queryString;

        // send ajax request to get more items
        $.ajax({
          url: url
        })
        .done(function(result) {
            if ($(result).filter('li').length) {
                // append the data
                var target = el.data('rooms').target;
                $(target).append(result);
    
                // increase for next run
                feedStart += 10;
            } else {
                $('.room-load-more').css('display', 'none');
            }
        });
    });
})(UIkit);