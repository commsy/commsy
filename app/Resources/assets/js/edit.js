;(function(UI) {

    "use strict";

    let partMapping = {
        'material': 'section',
        'todo': 'step',
        'discussion': 'discarticle'
    }

    UI.component('edit', {

        defaults: {
            editUrl: ''
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-edit]", context).each(function() {
                    var element = UI.$(this);

                    if (!element.data("edit")) {
                        var obj = UI.edit(element, UI.Utils.options(element.attr("data-cs-edit")));
                    }
                });
            });
        },

        init: function() {
            var $this = this;

            var element = $this.element[0];

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
            var $this = this;

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
            var $this = this;
            var article = $(el).parents('.cs-edit-section');

            // show the loading spinner
            $(article).find('.cs-edit-spinner').toggleClass('uk-hidden', false);

            // send ajax request to get edit html
            $.ajax({
              url: this.options.editUrl
            })
            .done(function(result) {
                // replace article html
                //article.fadeOut(function() {
                    article.html($(result));

                    var buttonpressed;
                    $('button').click(function() {
                        buttonpressed = $(this).attr('name');
                    });

                    // override form submit behaviour
                    article.find('form').submit(function (event) {
                        event.preventDefault();

                        $(article).find('.cs-save-spinner').toggleClass('uk-hidden', false);
                        
                        $('div[id^="cke_"]div[role="application"]').each(function () {
                           var $textarea = $(this).attr('id').replace('cke_', '');
                           $('#'+$textarea).val(CKEDITOR.instances[$textarea].getData());
                        });
                        
                        // submit the form manually
                        $.ajax({
                            url: $this.options.editUrl,
                            type: "POST",
                            data: $(this).serialize()+'&'+buttonpressed+'=true'
                        })
                        .done(function(result) {
                            //article.fadeOut(function() {
                                article.html($(result));

                                $this.registerArticleEvents(article);

                                let title = $(result).find('.uk-article-title');
                                if (title !== null && title.text()) {
                                    // material/todo/discussion title edited
                                    if($this.options.editUrl.includes(window.location.pathname.split("/").pop())) {
                                        $('.uk-breadcrumb').find('.last').find('span').html(title.text());
                                    }
                                    // section/step/article title edited
                                    else {
                                        let editParts = $this.options.editUrl.split("/");
                                        let anchor = $("a[href='#" + partMapping[editParts[editParts.length-3]] + editParts[editParts.length-2] + "']");
                                        anchor.text(anchor.html().trim().split(" ")[0] + " " + title.text());
                                    }
                                }
                                
                                let workflow = $(result).find('.cs-workflow-traffic-light').html();
                                if (workflow !== null) {
                                    $('.uk-article').find('.cs-workflow-traffic-light').html(workflow);
                                }

                                let sections = $(result).find('#section-list');
                                if(sections !== null){
                                    let counter = 0;
                                    sections.find("li").each(function() {
                                        let section_container = $($(this).find("a:first").attr('href')).closest('article').parent().detach();
                                        section_container.attr("id", "section_"+counter);
                                        $("#section-content").append( section_container );
                                        counter++;
                                    })
                                }
                            //});
                        });
                    });
                //});
            });
        }
    });

})(UIkit);