;(function(UI){

    "use strict";

    UIkit.on('selectitem.uk.autocomplete', function(event, {value: itemId}) {
        if (typeof($('div[data-cs-link-search]').data('cs-link-search')) != 'undefined') {
            let route = $('div[data-cs-link-search]').data('cs-link-search').source;
            route = route.replace(/-1/g, itemId);

            // since the value of the input field is changed after the
            // selectitem.uk.autocomplete event, we register a one time
            // listener to reset the input value
            //
            // this change event is triggered by uikit's autocomplete.js
            // this.input.val(data.value).trigger('change');
            let $searchInput = $('ul#linked-entry-switcher div.uk-autocomplete input');
            $searchInput.one("change", function () {
                // reset input field
                $(this).val('');
            });

            // check if we did not already added the item
            let duplicate = false;
            $('ul#itemsLinkedList input[type="checkbox"]').each(function () {
                if ($(this).val() == itemId) {
                    duplicate = true;
                }
            })

            if (!duplicate) {
                $.ajax({
                    url: route
                })
                    .done(function (result) {
                        // prepend html response to list
                        $(result).prependTo($('#itemsLinkedList'));

                        $('#itemsLinkedList').children().first().addClass("uk-comment-primary");
                    });
            }
        }
    });

})(UIkit);