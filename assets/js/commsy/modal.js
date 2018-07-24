;(function(UI) {
    
    "use strict";

    $('#modalMessage').each(function(){
        let message = $(this).data('title')+'<br/>'+$(this).data('message');
        let confirm = $(this).data('confirm');
        let cancel = $(this).data('cancel');
        let confirmButtonText = $(this).data('confirmbuttontext');
        let cancelButtonText = $(this).data('cancelbuttontext');
        UIkit.modal.confirm(
            message,
            function(){
                // will be executed on confirm.
                window.location.href = confirm;
            },
            function(){
                // will be executed on cancel.
                window.location.href = cancel;
            },
            {
                labels: {
                    'Ok': confirmButtonText,
                    'Cancel': cancelButtonText
                },
            }
        );
    });

})(UIkit);