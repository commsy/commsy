;(function(UI) {

    "use strict";

    let $confirm_calendar_delete = false;
    $('#calendar_edit_delete').on('click', function(event){
        if ($confirm_calendar_delete) {
            $confirm_calendar_delete = false;
            return;
        }

        event.preventDefault();
        let $this = $(this);

        UI.modal.confirm($(this).data('confirm-delete'), function() {
            $confirm_calendar_delete = true;
            $this.trigger('click');
        }, {
            labels: {
                Cancel: $(this).data('confirm-delete-cancel'),
                Ok: $(this).data('confirm-delete-confirm')
            }
        });
    });

})(UIkit);