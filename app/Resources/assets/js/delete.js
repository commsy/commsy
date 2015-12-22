;(function(UI){

    "use strict";

    $('.commsy-action-delete').on('click', function() {
        let $this = this;
        event.preventDefault();
        UIkit.modal.confirm($(this).data('confirm-delete'), function() {
            $.ajax({
                url: $($this).data('deleteUrl'),
                type: 'POST',
                data: {}
            })
            .done(function(result) {
                var type = $($this).data('itemType');
                // material
                if (type == 'material') {
                    location.href = $($this).data('returnUrl');
                    // location.reload();
                } else if (type == 'section') {
                    $($this).parents('.material-section').hide();
                }
            });
        }, {
            labels: {
                Cancel: $(this).data('confirm-delete-cancel'),
                Ok: $(this).data('confirm-delete-confirm')
            }
        });
    });

})(UIkit);