;(function(UI) {

    "use strict";

    var feedStart = 10;
    var sort = 'date';
    var sortOrder = '';

    // listen to "inview.uk.scrollspy" event on "feed-load-more" classes
    $('.feed-load-more').on('inview.uk.scrollspy', function() {
        var el = $(this);

        // get current query string
        var queryString = document.location.search;

        // build up the url
        var url = el.data('feed').url  + feedStart + '/' + sort + sortOrder + queryString;

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
        reloadCurrent('title', false);
    });
    
    $('#commsy-sort-modificator').on('click', function(event) {
        reloadCurrent('modificator', false);
    });
    
    $('#commsy-sort-date').on('click', function(event) {
        reloadCurrent('date', true);
    });
    
    $('#commsy-sort-assessment').on('click', function(event) {
        reloadCurrent('assessment', false);
    });
    
    $('#commsy-sort-workflow_status').on('click', function(event) {
        reloadCurrent('workflow_status', false);
    });

    function reloadCurrent (newSort, invert) {
        feedStart = 0;
        
        $('#commsy-sort-'+sort+'-chevron').removeClass('uk-icon-chevron-up');
        $('#commsy-sort-'+sort+'-chevron').removeClass('uk-icon-chevron-down');
        if (newSort == sort) {
            if (sortOrder == '') {
                sortOrder = '_rev';
                if (!invert) {
                    $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-down');
                } else {
                    $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-up');
                }
            } else {
                sortOrder = '';
                if (!invert) {
                    $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-up');
                } else {
                    $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-down');
                }
            }
        } else {
            if (!invert) {
                $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-up');
            } else {
                $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-down');
            }
            sortOrder = '';
        }
        sort = newSort;
        
        var el = $('.feed-load-more');
        
        // get current query string
        var queryString = document.location.search;

        // build up the url
        var url = el.data('feed').url  + feedStart + '/' + sort + sortOrder + queryString;

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
                
                var event = new CustomEvent(
                	"feedDidReload", 
                	{
                		detail: {},
                		bubbles: true,
                		cancelable: true
                	}
                );
                window.dispatchEvent(event);
            } else {
                $('.feed-load-more').css('display', 'none');
            }
            
            // increase for next run
            feedStart += 10;
        });
    }

})(UIkit);