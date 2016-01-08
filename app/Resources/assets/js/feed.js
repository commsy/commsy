;(function(UI) {

    "use strict";

    var feedStart = 10;
    var sort = 'modification_date';

    // listen to "inview.uk.scrollspy" event on "feed-load-more" classes
    $('.feed-load-more').on('inview.uk.scrollspy', function() {
        var el = $(this);

        // get current query string
        var queryString = document.location.search;

        // build up the url
        var url = el.data('feed').url  + feedStart + '/' + sort + queryString;

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
                feedStart += 10;
            } else {
                $('.feed-load-more-grid').css('display', 'none');
            }
        });
    });

    $('#commsy-sort-title').on('click', function(event) {
        sort = 'title';
        reloadCurrent();
    });
    
    $('#commsy-sort-modificator').on('click', function(event) {
        sort = 'modificator';
        reloadCurrent();
    });
    
    $('#commsy-sort-modification_date').on('click', function(event) {
        sort = 'modification_date';
        reloadCurrent();
    });

    function reloadCurrent () {
        feedStart = 0;
        
        var el = $('.feed-load-more');
        
        // get current query string
        var queryString = document.location.search;

        // build up the url
        var url = el.data('feed').url  + feedStart + '/' + sort + queryString;
        
        // send ajax request to get more items
        $.ajax({
          url: url
        })
        .done(function(result) {
            if ($(result).filter('article').length) {
                // append the data
                var target = el.data('feed').target;
                //$(target).append(result);
                $(target).html($(result));
            } else {
                $('.feed-load-more').css('display', 'none');
            }
            
            // increase for next run
            feedStart += 10;
        });
    }

})(UIkit);