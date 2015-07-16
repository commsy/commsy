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

            // look for div.cs-article-edit and show on mouseover
            $(element)
                .mouseover(function() {
                    $(this).find('div.cs-article-edit').toggleClass('uk-hidden', false);
                })
                .mouseout(function() {
                    $(this).find('div.cs-article-edit').toggleClass('uk-hidden', true);
                });

            // show articles as selected, when mouseover the edit icon
            $(element).find('div.cs-article-edit')
                .mouseover(function() {
                    $(this).parent('article').toggleClass('cs-article-selected', true);
                })
                .mouseout(function() {
                    $(this).parent('article').toggleClass('cs-article-selected', false);
                });

            // send ajax requests on click to load the form
            $(element).find('div.cs-article-edit').click(function(event) {
                event.preventDefault();
                $this.onClickEdit(this);
            });
        },

        onClickEdit: function(el) {
            var $this = this;
            var article = $(el).parent('article');

            // show the loading spinner
            $(article).find('.cs-edit-spinner').toggleClass('uk-hidden', false);

            // send ajax request to get edit html
            $.ajax({
              url: this.options.editUrl
            })
            .done(function(result) {
                // replace article html with some nice effecits
                article.fadeOut(function() {
                    article.html($(result)).fadeIn();

                    // override form submit behaviour
                    article.find('form').submit(function (event) {
                        event.preventDefault();

                        // submit the form manually
                        $.ajax({
                            url: $this.options.editUrl,
                            type: "POST",
                            data: $(this).serialize()
                        })
                        .done(function(result) {
                            article.fadeOut(function() {
                                article.html($(result)).fadeIn();
                                
                                article.find('div.cs-article-edit').click(function(event) {
                                    event.preventDefault();
                                    $this.onClickEdit(this);
                                });
                            });
                        });
                    });
                });
            });
        }
    });

})(UIkit);