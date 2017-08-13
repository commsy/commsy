;(function(UI) {
    
    "use strict";

    $('#modalMessage').each(function(){
        let message = $(this).data('title')+'<br/>'+$(this).data('message');
        let confirm = $(this).data('confirm');
        let cancel = $(this).data('cancel');
        UIkit.modal.confirm(
            message,
            function(){
                // will be executed on confirm.
                window.location.href = confirm;
            },
            function(){
                // will be executed on cancel.
                window.location.href = cancel;
            }
        );
    });

})(UIkit);