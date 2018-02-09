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

                    // update 'additional actions' list
                    $('#leave-group-link').removeClass('uk-hidden').css('pointer-events', 'auto');
                    $('#join-group-link, #join-group-and-room-link').addClass('uk-hidden').css('pointer-events', 'none');

                    // update member information
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
                        });
                    }

                    // update grouproom information
                    let $grouproomDiv = $("#grouproom" + data.groupId);
                    if($grouproomDiv.length > 0) {
                        let grouproomUrl = $this.options.url.replace("join", "grouproom");
                        $this.element.parent().hide();
                        $this.element.parent().next().show();
                        $.ajax({
                            url: grouproomUrl,
                            type: 'POST',
                            data: JSON.stringify({})
                        }).done(function(result) {
                            $grouproomDiv.html(result);
                        });
                    }

                    // update link information
                    let $linksDiv = $("#links" + data.groupId);
                    if($linksDiv.length > 0) {
                        let linksUrl = $this.options.url.replace("group", "item").replace("join", "links");
                        $this.element.parent().hide();
                        $this.element.parent().next().show();
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
