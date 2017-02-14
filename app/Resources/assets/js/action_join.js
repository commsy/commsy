;(function(UI) {

    'use strict';

    UI.component('actionJoin', {

        defaults: {
            url: '',
            successMessage: '',
            errorMessage: '',
            groupId: []
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$('[data-cs-action-join]', context).each(function() {
                    let element = UI.$(this);

                    if (!element.data('actionJoin')) {
                        UI.actionJoin(element, UI.Utils.options(element.attr('data-cs-action-join')));
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
                    data: JSON.stringify({})
                }).done(function(data) {
                    let $membersDiv = $("#member" + data.groupId);
                    if($membersDiv.length > 0) {
                        let membersUrl = $this.options.url.replace("join", "members");
                        $this.element.parent().hide();
                        $this.element.parent().next().show();
                        $.ajax({
                            url: membersUrl,
                            type: 'POST',
                            data: JSON.stringify({})
                        }).done(function(result) {
                            $membersDiv.html(result);
                            UIkit.notify($this.options.successMessage, 'success');
                        });
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify($this.options.errorMessage, 'danger');
                });
            });
        }
    });

})(UIkit);
