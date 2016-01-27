;(function(UI) {

    'use strict';

    UI.component('actionSend', {

        defaults: {
            url: '',
            errorMessage: '',
            itemId: null
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$('[data-cs-action-send]', context).each(function() {
                    let element = UI.$(this);

                    if (!element.data('actionSend')) {
                        UI.actionSend(element, UI.Utils.options(element.attr('data-cs-action-send')));
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
                    data: JSON.stringify({
                        itemId: $this.options.itemId
                    })
                }).done(function(data, textStatus, jqXHR) {
                    if (!jqXHR.responseJSON) {
                        // if we got back html, embed the form
                        let firstArticleDom = $('article:first');

                        if (firstArticleDom.length) {
                            if (firstArticleDom.hasClass('cs-send')) {
                                firstArticleDom.replaceWith(data)
                            } else {
                                firstArticleDom.before(data);
                            }
                        }
                    } else {
                        console.log('json response');
                        console.log(data);
                    }

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify($this.options.errorMessage, 'danger');
                });
            });
        }
    });

})(UIkit);