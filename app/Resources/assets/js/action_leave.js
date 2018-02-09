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

                    // update 'additional actions' list
                    $('#join-group-link, #join-group-and-room-link').removeClass('uk-hidden').css('pointer-events', 'auto');
                    $('#leave-group-link').addClass('uk-hidden').css('pointer-events', 'none');

                    // update member information
                    let $membersDiv = $("#member" + data.groupId);
                    if($membersDiv.length > 0) {
                        let membersUrl = $this.options.url.replace("leave", "members");
                        $.ajax({
                            url: membersUrl,
                            type: 'POST',
                            data: JSON.stringify({})
                        }).done(function(result) {
                            $membersDiv.html(result);
                        });
                    }

                    // update grouproom information
                    let $grouproomDiv = $("#grouproom" + data.groupId);
                    if($grouproomDiv.length > 0) {
                        let grouproomUrl = $this.options.url.replace("leave", "grouproom");
                        $.ajax({
                            url: grouproomUrl,
                            type: 'POST',
                            data: JSON.stringify({})
                        }).done(function(result) {
                            $grouproomDiv.html(result);
                        })
                    }

                    // update link information
                    let $linksDiv = $("#links" + data.groupId);
                    if($linksDiv.length > 0) {
                        let linksUrl = $this.options.url.replace("group", "item").replace("leave", "links");
                        $.ajax({
                            url: linksUrl,
                            type: 'POST',
                            data: JSON.stringify({})
                        }).done(function(result) {
                            $linksDiv.html(result);
                        });
                    }

                    UIkit.notify($this.options.successMessage, 'success');

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify($this.options.errorMessage, 'danger');
                });
            });
        }
    });

})(UIkit);
