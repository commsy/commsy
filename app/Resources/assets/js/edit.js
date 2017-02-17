;(function(UI) {

    "use strict";

    let partMapping = {
        'material': 'section',
        'todo': 'step',
        'discussion': 'discarticle'
    };

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
                    $(this).find('div.cs-edit').toggleClass('uk-invisible', false);
                })
                .mouseout(function() {
                    $(this).find('div.cs-edit').toggleClass('uk-invisible', true);
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
                .mouseover(function() {
                    $(this).parents('.cs-edit-section').toggleClass('cs-selected', true);
                })
                .mouseout(function() {
                    $(this).parents('.cs-edit-section').toggleClass('cs-selected', false);
                });

            // send ajax requests on click to load the form
            $(element).find('div.cs-edit').click(function(event) {
                event.preventDefault();

                // reset article selection class and remove event handling
                $(this).parents('.cs-edit-section').toggleClass('cs-selected', false);
                $(this).off();

                $this.onClickEdit(this);
            });
        },

        onClickEdit: function(el) {
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

            });
        },

        handleFormSubmit: function(article) {
            let $this = this;

            // override form submit behaviour
            article.find('button').click(function (event) {
                let $button = $(this);

                // cancel is not handled via ajax
                if ($button.attr('name').includes('cancel')) {
                    event.preventDefault();

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
                } else {
                    let form = $(this).closest('form');
                    if (form[0].checkValidity()) {
                        event.preventDefault();

                        // editButtons.addClass('cs-edit');
                        // $("#cs-additional-actions")
                        //     .removeClass('uk-hidden')
                        //     .parent().find("button.uk-button").removeClass("uk-text-muted");
                        //
                        // editButtons.each(function(){
                        //     $(this).find('a').attr('title', $(this).data('edit-title'));
                        // });

                        // $(article).find('.cs-save-spinner').toggleClass('uk-hidden', false);

                        // sync cke content
                        $('div[id^="cke_"]div[role="application"]').each(function () {
                            let $textarea = $(this).attr('id').replace('cke_', '');
                            $('#'+$textarea).val(CKEDITOR.instances[$textarea].getData());
                        });

                        let formData = form.serializeArray();
                        formData.push({ name: this.name, value: this.value });

                        // submit the form manually
                        $.ajax({
                            url: $this.options.editUrl,
                            type: "POST",
                            data: formData
                        })
                        .done(function(result, statusText, xhrObject) {
                            let $result = $(result);

                            if ($result.find('ul.form-errors').length) {
                                article.html($result);
                                $this.handleFormSubmit(article);
                            } else {
                                window.location.reload(true);
                            }






                            // $this.registerArticleEvents(article);
                            //
                            // let title = $(result).find('.uk-article-title');
                            // if (title !== null && title.text()) {
                            //     // material/todo/discussion title edited
                            //     if($this.options.editUrl.includes(window.location.pathname.split("/").pop())) {
                            //         $('.uk-breadcrumb').find('.last').find('span').html(title.text());
                            //     }
                            //     // section/step/article title edited
                            //     else {
                            //         let editParts = $this.options.editUrl.split("/");
                            //         let anchor = $("a[href='#" + partMapping[editParts[editParts.length-3]] + editParts[editParts.length-2] + "']");
                            //         anchor.text(anchor.html().trim().split(" ")[0] + " " + title.text());
                            //     }
                            // }
                            //
                            // let workflow = $(result).find('.cs-workflow-traffic-light').html();
                            // if (workflow !== null) {
                            //     $('.uk-article').find('.cs-workflow-traffic-light').html(workflow);
                            // }
                            //
                            // let sections = $(result).find('#section-list');
                            // if (sections !== null) {
                            //     let counter = 0;
                            //     sections.find("li").each(function() {
                            //         let section_container = $($(this).find("a:first").attr('href')).closest('article').parent().detach();
                            //         section_container.attr("id", "section_"+counter);
                            //         $("#section-content").append( section_container );
                            //         counter++;
                            //     })
                            // }
                            //
                            // // only redirect if there was no error
                            // if (!$(result).find('ul.form-errors').length) {
                            //     window.location.reload(true);
                            // }
                        });
                    }
                }
            });
        }
    });

})(UIkit);