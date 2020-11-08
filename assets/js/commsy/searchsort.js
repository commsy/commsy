;(function(UI) {

    'use strict';

    $('.cs-sort-actor-search').on('click', function(event) {
        onSortActorSearchClick($(this));
    });

    function onSortActorSearchClick($element)
    {
        // first functionality test
        alert("Hello world!");


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

        let target = el.data('feed').target;
        $(target).empty();

        // re-enable spinner - otherwise feeds are reaching their end before changing sort order
        // and will not be able to load more entries
        el.css('display', 'block');

        let path = $element.url + '/' + sortOrder;
        let uri = new URI(path);

        // build up the url
        let url = uri.toString();

        // send ajax request to get more items
        $.ajax({
            url: url
        })
    }
})(UIkit);
