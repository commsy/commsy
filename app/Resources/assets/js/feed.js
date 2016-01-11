;(function(UI) {

    "use strict";

    var feedStart = 10;
    var sort = 'modification_date';
    var sortOrder = '_rev';

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
        sort = 'title';
        sortOrder = '';
        reloadCurrent();
        setCheckSort('commsy-sort-title');
        setCheckOrder('commsy-sort-ascending');
    });
    
    $('#commsy-sort-modificator').on('click', function(event) {
        sort = 'modificator';
        sortOrder = '';
        reloadCurrent();
        setCheckSort('commsy-sort-modificator');
        setCheckOrder('commsy-sort-ascending');
    });
    
    $('#commsy-sort-modification_date').on('click', function(event) {
        sort = 'modification_date';
        sortOrder = '_rev';
        reloadCurrent();
        setCheckSort('commsy-sort-modification_date');
        setCheckOrder('commsy-sort-descending');
    });
    
    $('#commsy-sort-assessment').on('click', function(event) {
        sort = 'assessment';
        sortOrder = '_rev';
        reloadCurrent();
        setCheckSort('commsy-sort-assessment');
        setCheckOrder('commsy-sort-descending');
    });
    
    $('#commsy-sort-workflow_status').on('click', function(event) {
        sort = 'workflow_status';
        sortOrder = '_rev';
        reloadCurrent();
        setCheckSort('commsy-sort-workflow_status');
        setCheckOrder('commsy-sort-descending');
    });
    
    $('#commsy-sort-ascending').on('click', function(event) {
        sortOrder = '';
        reloadCurrent();
        setCheckOrder('commsy-sort-ascending');
    });
    
    $('#commsy-sort-descending').on('click', function(event) {
        sortOrder = '_rev';
        reloadCurrent();
        setCheckOrder('commsy-sort-descending');
    });

    function reloadCurrent () {
        feedStart = 0;
        
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

    function setCheckSort (check) {
        $('i[id*="-check-sort"]').removeClass('uk-icon-check');
        $('#'+check+'-check-sort').addClass('uk-icon-check');
    }
    
    function setCheckOrder (check) {
        $('i[id*="-check-order"]').removeClass('uk-icon-check');
        $('#'+check+'-check-order').addClass('uk-icon-check');
    }

})(UIkit);