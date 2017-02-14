;(function(UI) {

    'use strict';

    UI.component('actionLeave', {

        defaults: {
            url: '',
            successMessage: '',
            errorMessage: '',
            groupId: []
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$('[data-cs-action-leave]', context).each(function() {
                    let element = UI.$(this);

                    if (!element.data('actionLeave')) {
                        UI.actionLeave(element, UI.Utils.options(element.attr('data-cs-action-leave')));
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
                        let membersUrl = $this.options.url.replace("leave", "members");
                        $this.element.parent().prev().show();
                        $this.element.parent().hide();
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
