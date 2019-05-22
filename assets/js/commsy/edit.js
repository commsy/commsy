;(function(UI) {

    "use strict";

    let partMapping = {
        'material': 'section',
        'todo': 'step',
        'discussion': 'discarticle'
    };

    var draftFormCount = 0;

    UI.component('edit', {

        defaults: {
            editUrl: ''
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-edit]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("edit")) {
                        let obj = UI.edit(element, UI.Utils.options(element.attr("data-cs-edit")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            let element = $this.element[0];

            // look for div.cs-edit and show on mouseover
            $(element)
                .mouseover(function() {
                    $(this).find('div.cs-delete').toggleClass('uk-invisible', false);
                })
                .mouseout(function() {
                    $(this).find('div.cs-delete').toggleClass('uk-invisible', true);
                });

            $this.registerArticleEvents(element);

            if ($this.options.draft == '1') {
                $this.onClickEdit($(element).find('div.cs-edit'));
            }
        },

        registerArticleEvents: function(element) {
            let $this = this;

            $(element).find('div.cs-edit').find('a').attr('data-uk-tooltip', '');
            $(element).find('div.cs-edit').find('a').attr('title', $(element).find('div.cs-edit').data('edit-title'));

            // show articles as selected, when mouseover the edit icon
            $(element).find('div.cs-edit')
                .mouseenter(function() {
                    if (!$(this).closest('article').find('.cs-readmoreless:first').parent("a").hasClass('uk-invisible')) {
                        $(this).parents(".cs-edit-section").find(".fade-preview").toggleClass("uk-hidden", true);
                    }
                    $(this).parents('.cs-edit-section').toggleClass('cs-selected', true);
                })
                .mouseleave(function() {
                    if (!$(this).closest('article').find('.cs-readmoreless:first').parent("a").hasClass('uk-invisible') &&
                        !$(this).closest('article').find('.cs-toggle-preview-small').hasClass('cs-toggle-full')) {
                        $(this).parents(".cs-edit-section").find(".fade-preview").toggleClass("uk-hidden", false);
                    }
                    $(this).parents('.cs-edit-section').toggleClass('cs-selected', false);
                });

            // send ajax requests on click to load the form
            $(element).find('div.cs-edit').click(function(event) {
                event.preventDefault ? event.preventDefault() : (event.returnValue = false);

                // reset article selection class and remove event handling
                $(this).parents('.cs-edit-section').toggleClass('cs-selected', false);
                $(this).off();

                $this.onClickEdit(this);
            });

            // active form if item is draft
            $(element).find('div.cs-edit-draft').each(function() {
                $this.onClickEdit(this);
            });
        },

        onClickEdit: function(el) {
            draftFormCount++;

            let $this = this;
            let article = $(el).parents('.cs-edit-section');

            // show the loading spinner
            $(article).find('.cs-edit-spinner').toggleClass('uk-hidden', false);

            let editButtons = $('.cs-edit');
            editButtons.removeClass('cs-edit');
            editButtons.each(function(){
                $(this).find('a').attr('title', 'close the current form to edit this section');
            });

            $("#cs-additional-actions")
                .addClass('uk-hidden')
                .parent().find("button.uk-button").addClass("uk-text-muted");

            // send ajax request to get edit html
            $.ajax({
              url: this.options.editUrl
            })
            .done(function(result) {
                // replace article html
                article.html($(result));

                $this.handleFormSubmit(article);

                // Trigger an resize event. This is a workaround for the data-uk-grid component for example used
                // by hashtags. There is some odd behaviour after replacing the content with ajax. Sometimes labels
                // which are too long become truncated. However data-uk-grid-match will now adjust the height of all
                // columns in a row.
                UI.trigger('resize');
            });
        },

        handleFormSubmit: function(article) {
            let $this = this;

            // override form submit behaviour
            article.find('button').click(function (event) {
                let $button = $(this);

                // cancel is not handled via ajax
                if ($button.attr('name').indexOf('cancel') > -1) {
                    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
                    /*
                    // cancel editing a NEW entry => return to list view
                    if($("#breadcrumb-nav .current.last").text().trim() == "") {
                        let pathParts = window.location.pathname.split("/");
                        pathParts.pop();
                        window.location.href = pathParts.join("/");
                    }
                    // cancel editing an EXISTING entry => return to detail view of the entry
                    else {
                        // trigger reload of the current URL
                        // We are using the Location.reload() method, since
                        // setting window.location.href might not result in a reload, if
                        // there is an anchor currently set
                        window.location.reload(true);
                    }
                    */
                    // request backend to remove edit lock
                    $.ajax({
                        url: $this.options.cancelEditUrl,
                        type: "POST",
                        data: null
                    })
                    .always(function(result, statusText, xhrObject) {
                        // cancel editing a NEW entry => return to list view
                        if($('#wo-breadcrumbs li').last().text().trim() == "") {
                            let pathParts = window.location.pathname.split("/");
                            pathParts.pop();
                            window.location.href = pathParts.join("/");
                        }
                        // cancel editing an EXISTING entry => return to detail view of the entry
                        else {
                            // trigger reload of the current URL
                            // We are using the Location.reload() method, since
                            // setting window.location.href might not result in a reload, if
                            // there is an anchor currently set
                            window.location.reload(true);
                        }
                    });
                } else {
                    if (!$button.attr('name').indexOf('newHashtagAdd') > -1) {
                        let form = $(this).closest('form');
                        if (form[0].checkValidity()) {
                            event.preventDefault ? event.preventDefault() : (event.returnValue = false);

                            if($('#date_start_date').length > 0){
                                var dateArray = $('#date_start_date').val().split('.');
                                if (dateArray.length == 3) {
                                    if (dateArray[2].length == 2) {
                                        $('#date_start_date').val(dateArray[0]+'.'+dateArray[1]+'.20'+dateArray[2]);
                                    }
                                }
                            }

                            if($('#date_end_date').length > 0){
                                var dateArray = $('#date_end_date').val().split('.');
                                if (dateArray.length == 3) {
                                    if (dateArray[2].length == 2) {
                                        $('#date_end_date').val(dateArray[0]+'.'+dateArray[1]+'.20'+dateArray[2]);
                                    }
                                }
                            }

                            if($('#date_start_time').length > 0){
                                $('#date_start_time').prop('disabled', false);
                            }
                            if($('#date_end_time').length > 0){
                                $('#date_end_time').prop('disabled', false);
                            }

                            let formData = form.serializeArray();
                            formData.push({name: this.name, value: this.value});

                            // submit the form manually
                            $.ajax({
                                url: $this.options.editUrl,
                                type: "POST",
                                data: formData
                            })
                                .done(function (result, statusText, xhrObject) {
                                    let $result = $(result);

                                    if ($result.find('ul.form-errors').length) {
                                        article.html($result);
                                        $this.handleFormSubmit(article);
                                    } else {
                                        article.html($result);
                                        draftFormCount--;
                                        if (draftFormCount == 0) {
                                            window.location.reload(true);
                                        }
                                    }
                                });
                        }
                    }
                }
            });
        }
    });

    $('.cs-delete').on('click', function(e){
        e.preventDefault();

        let $delete = $(this);

        UIkit.modal.confirm($delete.data('delete-confirm'), function() {
            $.ajax({
                url: $delete.data('delete-url'),
            }).done(function(data, textStatus, jqXHR) {
                if (data.deleted) {
                    window.location.reload(true);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.log('fail')
            });
        }, function () {
        }, {
            labels: {
                Cancel: $delete.data('confirm-delete-cancel'),
                Ok: $delete.data('confirm-delete-confirm')
            }
        });
    });

    $('#draft-save-combine-link').on('click', function(event){
        event.preventDefault ? event.preventDefault() : (event.returnValue = false);
        $(this).parents('article').find('form').each(function(){
            let button = $(this).find('.uk-button-primary');
            if (button.length) {
                button.click();
            }
        });
    });

    $('#draft-cancel-link').on('click', function(event){
        event.preventDefault ? event.preventDefault() : (event.returnValue = false);
        let pathParts = window.location.pathname.split("/");
        pathParts.pop();
        window.location.href = pathParts.join("/");
    });


})(UIkit);