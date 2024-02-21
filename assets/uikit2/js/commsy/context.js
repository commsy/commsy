;(function(UI){

    "use strict";

    const $type = $('#context_type_select > input');
    $type.on('change', function() {
        const $form = $(this).closest('form');

        const data = {};
        data[$(this).attr('name')] = $(this).val();
        data['context[_token]'] = $form.find('#context__token').val();

        // Submit data via AJAX to the form's action path.
        $.ajax({
            url : $form.attr('action'),
            type: $form.attr('method'),
            data : data,
            success: function(html) {
                // replace with content from ajax response
                if (!$('#context_type_sub').length) {
                    $('#context_type_select').after(
                        $(html).find('#context_type_sub')
                    );
                } else {
                    $('#context_type_sub').replaceWith(
                        $(html).find('#context_type_sub')
                    );
                }
            }
        });
    });

})(UIkit);
