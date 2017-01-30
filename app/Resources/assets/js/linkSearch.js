;(function(UI){

    "use strict";

    UIkit.on('selectitem.uk.autocomplete', function(evt, data) {

        $('#itemsLinkedList article').first().clone().prependTo($('#itemsLinkedList'));
        var newElement = $('#itemsLinkedList article').first();

    });

})(UIkit);