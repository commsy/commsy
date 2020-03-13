;(function(UI){

    "use strict";

    var $type = $('#context_type_select > input');
    // When sport gets selected ...
    $type.change(function() {
        // ... retrieve the corresponding form.
        var $form = $(this).closest('form');
        // Simulate form data, but only include the selected sport value.
        var data = {};

        data[$(this).attr('name')] = $(this).val();
        // Submit data via AJAX to the form's action path.
        $.ajax({
            url : $form.attr('action'),
            type: $form.attr('method'),
            data : data,
            success: function(html) {
                // Replace current position field ...
                // add field
                if (!$('#context_type_sub').length) {
                    $('#context_type_select').after(
                        // ... with the returned one from the AJAX response.
                        $(html).find('#context_type_sub')
                        // html
                    );
                } else {
                    $('#context_type_sub').replaceWith(
                        // ... with the returned one from the AJAX response.
                        $(html).find('#context_type_sub')
                        // html
                    );
                }

                $('.js-select2-choice').select2();
            }
        });
    });

})(UIkit);