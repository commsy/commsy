;(function(UI) {

    'use strict';

    let feedStart = 10;
    let sort = 'date';
    let sortOrder = '';

    let lastRequest = '';

    // listen to "inview.uk.scrollspy" event on "feed-load-more" classes
    $('.feed-load-more').on('inview.uk.scrollspy', function() {
        let el = $(this);

        loadMore(el);
    });

    // listen to "inview.uk.scrollspy" event on "feed-load-more-grid" classes
    $('.feed-load-more-grid').on('inview.uk.scrollspy', function() {
        let el = $(this);

        loadMore(el);
    });

    $('#commsy-sort-title').on('click', function(event) {
        reloadCurrent('title', false);
    });
    
    $('#commsy-sort-modificator').on('click', function(event) {
        reloadCurrent('modificator', false);
    });

    $('#commsy-sort-creator').on('click', function(event) {
        reloadCurrent('creator', false);
    });
    
    $('#commsy-sort-date').on('click', function(event) {
        reloadCurrent('date', true);
    });

    $('#commsy-sort-latest').on('click', function(event) {
        reloadCurrent('latest', true);
    });
    
    $('#commsy-sort-assessment').on('click', function(event) {
        reloadCurrent('assessment', true);
    });
    
    $('#commsy-sort-workflow_status').on('click', function(event) {
        reloadCurrent('workflow_status', false);
    });

    $('#commsy-sort-status').on('click', function(event) {
        reloadCurrent('status', false);
    });
    
    $('#commsy-sort-time').on('click', function(event) {
        reloadCurrent('time', false);
    });
    
    $('#commsy-sort-place').on('click', function(event) {
        reloadCurrent('place', false);
    });

    $('#commsy-sort-name').on('click', function(event) {
        reloadCurrent('name', false);
    });

    $('#commsy-sort-email').on('click', function(event) {
        reloadCurrent('email', false);
    });

    $('#commsy-sort-duedate').on('click', function(event) {
        reloadCurrent('duedate', true);
    });

    function loadMore(spinner)
    {
        let $activeSort = $(".cs-sort-active");


        if ($activeSort.attr("id")) {
            sort = $activeSort.attr("id").split("-")[$activeSort.attr("id").split("-").length-1];
        }

        if ($activeSort.find("i").hasClass("uk-icon-chevron-up")) {
            sortOrder = "_rev";
        }

        let path = spinner.data('feed').url  + feedStart + '/' + sort + sortOrder;
        let uri = new URI(path);

        // adopt current query parameter
        uri.search(new URI().search(true));

        if (spinner.data('feed').query) {
            // augment additional data
            uri.search(function(data) {
                $.extend(data, spinner.data('feed').query);
            });
        }

        // build up the url
        let url = uri.toString();

        if (lastRequest == url) {
            return;
        }
        lastRequest = url;

        // send ajax request to get more items
        $.ajax({
            url: url
        })
        .done(function(result) {
            try {
                let foundArticles = false;
                if ($(result).filter('article').length) {
                    foundArticles = true;
                } else if ($(result).find('article').length) {
                    foundArticles = true
                }

                if (foundArticles) {
                    // append the data
                    let target = spinner.data('feed').target;
                    $(target).append(result);

                    let event = new CustomEvent(
                        'feedDidLoad',
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

                    if (isElementInViewport(spinner)) {
                        loadMore(spinner);
                    }
                } else {
                    $('.feed-load-more, .feed-load-more-grid').css('display', 'none');
                }
            } catch (error) {
                $('.feed-load-more, .feed-load-more-grid').css('display', 'none');
            }
        });
    }

    function isElementInViewport (el) {
        if (typeof jQuery === "function" && el instanceof jQuery) {
            el = el[0];
        }

        var rect = el.getBoundingClientRect();

        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
            rect.right <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */
        );
    }

    function reloadCurrent(newSort, invert)
    {
        feedStart = 0;

        sort = $(".cs-sort-active").attr("id").split("-")[$(".cs-sort-active").attr("id").split("-").length-1];
        // current sort order depends on state of chevron
        let chevronDown = $('#commsy-sort-'+sort+'-chevron').attr('class').slice(-4) === 'down';
        sortOrder = (chevronDown && !invert) || (!chevronDown && invert) ? '_rev' : '';

        $('#commsy-sort-'+sort+'-chevron')
            .removeClass('uk-icon-chevron-up')
            .removeClass('uk-icon-chevron-down');
        if (newSort == sort) {
            if (sortOrder == '') {
                sortOrder = '_rev';
                if (invert) {
                    $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-up');
                } else {
                    $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-down');
                }
            } else {
                sortOrder = '';
                if (invert) {
                    $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-down');
                } else {
                    $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-up');
                }
            }
        } else {
            $('#commsy-sort-'+sort).removeClass('cs-sort-active');
            $('#commsy-sort-'+newSort).addClass('cs-sort-active');
            if (invert) {
                $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-down');
            } else {
                $('#commsy-sort-'+newSort+'-chevron').addClass('uk-icon-chevron-up');
            }
            sortOrder = '';
        }
        sort = newSort;
        
        let el = $('.feed-load-more');
        if(el.length < 1){
            el = $('.feed-load-more-grid');
        }

        let target = el.data('feed').target;
        $(target).empty();

        // re-enable spinner - otherwise feeds reaching their end before changing sort order will not
        // be able to load more entries
        el.css('display', 'block');

        loadMore(el);
    }

    (function () {
        if ( typeof window.CustomEvent === "function" ) return false; //If not IE

        function CustomEvent ( event, params ) {
            params = params || { bubbles: false, cancelable: false, detail: undefined };
            var evt = document.createEvent( 'CustomEvent' );
            evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
            return evt;
        }

        CustomEvent.prototype = window.Event.prototype;

        window.CustomEvent = CustomEvent;
    })();

})(UIkit);
