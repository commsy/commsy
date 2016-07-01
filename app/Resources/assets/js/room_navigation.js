;(function(UI) {

    "use strict";

    // Attach a handler to the "mouseover" event on "#switch_room",
    // but execute it at most once
    $('#switch_room').one('mouseover', function() {
        // This must be the first time the user openes the room navigation
        let $switchRoom = $(this);

        let $input = $switchRoom.find('input.cs-roomsearch-field');
        let $form = $switchRoom.find('form');
        console.log($input);
        console.log(UI.$($form));

        $input.val('');

        //$form.submit();
    });

})(UIkit);