;(function(UI) {

    "use strict";

    UI.component('actionWorkflow', {

        defaults: {

        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-action-workflow]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("actionWorkflow")) {
                        UI.actionWorkflow(element, UI.Utils.options(element.attr("data-cs-action-workflow")));
                    }
                });
            });
        },

        init: function() {
            var $this = this;

            this.element.on('click', function() {
                event.preventDefault();

                // send ajax request
                $.ajax({
                    url: $this.options.url,
                    type: 'POST',
                    data: {
                        read: $this.options.read
                    }
                }).done(function(data) {
                    console.log(data);

                    UIkit.notify("done", "success");
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify("error", "danger");
                });
            });


            // $('.commsy-action-delete').on('click', function() {
            //     let $this = this;
            //     event.preventDefault();
            //     UIkit.modal.confirm($(this).data('confirm-delete'), function() {
            //         $.ajax({
            //             url: $($this).data('deleteUrl'),
            //             type: 'POST',
            //             data: {}
            //         })
            //         .done(function(result) {
            //             var type = $($this).data('itemType');
            //             // material
            //             if (type == 'material') {
            //                 location.href = $($this).data('returnUrl');
            //                 // location.reload();
            //             } else if (type == 'section') {
            //                 $($this).parents('.material-section').hide();
            //             }
            //         });
            //     }, {
            //         labels: {
            //             Cancel: $(this).data('confirm-delete-cancel'),
            //             Ok: $(this).data('confirm-delete-confirm')
            //         }
            //     });
            // });
        }
    });

})(UIkit);