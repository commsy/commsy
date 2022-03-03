;(function(UI) {

    'use strict';

    let feedStart = 10;

    let lastRequest = '';

    let $spinner = $('.feed-load-more, .feed-load-more-grid');

    // listen to "inview.uk.scrollspy" event on corresponding classes
    $spinner.on('inview.uk.scrollspy', function() {
        let el = $(this);

        loadMore(el);
    });

    $('.cs-sort-actor').on('click', function(event) {
        onSortActorClick($(this));
    });

    /**
     * Since init.uk.scrollspy seems not to be triggered, we reuse the custom function here.
     * This will force a load more request and remove the spinner if it was initially visible and there are no
     * additional results.
     * @see #4397
     */
    if ($spinner.length && isElementInViewport($spinner)) {
        loadMore($spinner);
    }

    function onSortActorClick($element)
    {
        // change sorting will start feed at 0
        feedStart = 0;

        // default new sort order is ascending
        let sortOrder = 'asc';

        // if the current element is the active sorting actor,
        // determine the new order by inspecting the elements classes
        if ($element.hasClass('cs-sort-active-asc')) {
            sortOrder = 'desc';
        }

        // remove any previously set active and sort icon classes
        $('.cs-sort-actor')
            .removeClass('cs-sort-active')
            .removeClass('cs-sort-active-asc')
            .removeClass('cs-sort-active-desc')
            .find('i')
            .removeClass('uk-icon-sort-asc')
            .removeClass('uk-icon-sort-desc');

        // set new active classes
        $element
            .addClass('cs-sort-active')
            .addClass('cs-sort-active-' + sortOrder);

        // set new sort icon class
        $element
            .find('i')
            .addClass('uk-icon-sort-' + sortOrder);

        // empty current feed content
        let el = $('.feed-load-more');
        if(el.length < 1){
            el = $('.feed-load-more-grid');
        }

        let target = el.data('feed').target;
        $(target).empty();

        // re-enable spinner - otherwise feeds are reaching their end before changing sort order
        // and will not be able to load more entries
        el.css('display', 'block');

        // request for more
        loadMore(el);
    }

    function loadMore(spinner)
    {
        // determine current sortBy
        let sortBy = '';
        let $sortActive = $('.cs-sort-active');

        if ($sortActive.length) {
            if ($sortActive.hasClass('cs-sort-active-asc')) {
                sortBy = $sortActive.data('sort-order').asc;
            } else {
                sortBy = $sortActive.data('sort-order').desc;
            }
        } else {
            sortBy = 'date';
        }

        let path = spinner.data('feed').url  + feedStart + '/' + sortBy;
        let uri = new URI(path);

        // adopt current query parameter
        uri.search(new URI().search(true));

        let spinnerTarget = spinner.data('feed').target;
        if (spinnerTarget) {
            let lastArticle = $(spinnerTarget).find('article:last-child');
            if (lastArticle) {
                let lastItemId = lastArticle.data('item-id');

                if (lastItemId) {
                    uri.setSearch('lastId', lastItemId);
                }
            }
        }

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
