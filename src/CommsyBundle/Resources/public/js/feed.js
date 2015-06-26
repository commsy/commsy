;(function(UI) {

    "use strict";

    var feedStart = 10;

    // listen to "inview.uk.scrollspy" event on "feed-load-more" classes
    $('.feed-load-more').on('inview.uk.scrollspy', function() {
        var el = $(this);

        // send ajax request to get more items
        $.ajax({
          url: el.data('feed').url + feedStart
        })
        .done(function(result) {
            if (result) {
                // append the data
                var target = el.data('feed').target;
                $(target).append(result);
    
                // increase for next run
                feedStart += 10;
            } else {
                $('.feed-load-more').css('display', 'none');
            }
        });
    });

})(UIkit);