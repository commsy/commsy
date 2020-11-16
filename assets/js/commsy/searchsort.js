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

        // set new sort icon class
        $element
            .find('i')
            .addClass('uk-icon-sort-' + sortOrder);

        let path = window.location.href.replace("#", "") + "/" + sortBy + "_" + sortOrder;
        let uri = new URI(path);

        // build up the url
        let url = uri.toString();

        // rewrite url
        window.history.pushState("","", url);

        // click the search-button as if done manually to keep search parameter as is
        document.getElementById('search_submit').click();
    }
})(UIkit);
