;(function(UI) {

    "use strict";

    UI.component('actionWorkflow', {

        defaults: {
            url: '',
            read: false,
            successMessage: '',
            errorMessage: ''
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
            let $this = this;

            this.element.on('click', function(event) {
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

                    $('.cs-workflow-action').toggleClass('uk-hidden');
                    
                    window.location.href = window.location.href;
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify($this.options.errorMessage, "danger");
                });
            });
        }
    });

})(UIkit);