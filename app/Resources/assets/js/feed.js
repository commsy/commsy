;(function(UI) {

    "use strict";

    var feedStart = 10;

    // listen to "inview.uk.scrollspy" event on "feed-load-more" classes
    $('.feed-load-more').on('inview.uk.scrollspy', function() {
        var el = $(this);

        // get current query string
        var queryString = document.location.search;

        // build up the url
        var url = el.data('feed').url  + feedStart + queryString;

        // send ajax request to get more items
        $.ajax({
          url: url
        })
        .done(function(result) {
            if ($(result).filter('article').length) {
                // append the data
                var target = el.data('feed').target;
                $(target).append(result);
    
                var event = new CustomEvent(
                	"feedDidLoad", 
                	{
                		detail: {
                			feedStart: feedStart,
                		},
                		bubbles: true,
                		cancelable: true
                	}
                );
                window.dispatchEvent(event);
    
                // increase for next run
                feedStart += 10;
            } else {
                $('.feed-load-more').css('display', 'none');
            }
        });
    });

    // listen to "inview.uk.scrollspy" event on "feed-load-more-grid" classes
    $('.feed-load-more-grid').on('inview.uk.scrollspy', function() {
        var el = $(this);

        // get current query string
        var queryString = document.location.search;

        // build up the url
        var url = el.data('feed').url  + feedStart + queryString;

        // send ajax request to get more items
        $.ajax({
          url: url
        })
        .done(function(result) {
            if ($(result).filter('div').length) {
                // append the data
                var target = el.data('feed').target;
                $(target).append(result);
    
                // increase for next run
                feedStart += 20;
            } else {
                $('.feed-load-more-grid').css('display', 'none');
            }
        });
    });



})(UIkit);