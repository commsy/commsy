;(function(UI) {

    "use strict";

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
                    $(this).find('div.cs-edit').toggleClass('uk-hidden', false);
                })
                .mouseout(function() {
                    $(this).find('div.cs-edit').toggleClass('uk-hidden', true);
                });

            $this.registerArticleEvents(element);
            
            if ($this.options.draft) {
                $this.onClickEdit($(element).find('div.cs-edit'));
            }
        },

        registerArticleEvents: function(element) {
            var $this = this;

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
                        buttonpressed = $(this).attr('name')
                    })

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
                            //});
                        });
                    });
                //});
            });
        }
    });

})(UIkit);