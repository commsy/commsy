;(function(UI){

    "use strict";

    $('.commsy-action-delete').on('click', function(event) {
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
                if (type == 'section') {
                    $($this).parents('.material-section').hide();
                } else {
                    location.href = $($this).data('returnUrl');
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