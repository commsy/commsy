;(function(UI) {

    "use strict";

    UI.component('actionCopy', {

        defaults: {
            url: '',
            read: false,
            successMessage: '',
            errorMessage: ''
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-action-copy]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("actionCopy")) {
                        UI.actionCopy(element, UI.Utils.options(element.attr("data-cs-action-copy")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

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
                    UIkit.notify($this.options.successMessage, "success");

                    let $indicator = $('#cs-nav-copy-indicator');
                    $indicator.html(data.count);

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify($this.options.errorMessage, "danger");
                });
            });
        }
    });

})(UIkit);