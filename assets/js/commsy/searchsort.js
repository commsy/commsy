;(function(UI) {

    'use strict';

    $('.cs-sort-actor-search').on('click', function(event) {
        onSortActorSearchClick($(this));
    });

    function onSortActorSearchClick($element)
    {
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

        let path = window.location.href.replace("#", "") + "/" + sortOrder;
        let uri = new URI(path);

        // build up the url
        let url = uri.toString();

        // first functionality test
        alert(url);

        window.location.replace(url);
    }
})(UIkit);
