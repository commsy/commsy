;(function(UI) {

    'use strict';

    UI.component('actionCopy', {

        defaults: {
            url: '',
            successMessage: '',
            errorMessage: '',
            itemIds: []
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$('[data-cs-action-copy]', context).each(function() {
                    let element = UI.$(this);

                    if (!element.data('actionCopy')) {
                        UI.actionCopy(element, UI.Utils.options(element.attr('data-cs-action-copy')));
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
                    data: JSON.stringify({
                        itemIds: $this.options.itemIds
                    })
                }).done(function(data) {
                    UIkit.notify($this.options.successMessage, 'success');

                    let $indicator = $('#cs-nav-copy-indicator');
                    $indicator.html(data.count);

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify($this.options.errorMessage, 'danger');
                });
            });
        }
    });

})(UIkit);