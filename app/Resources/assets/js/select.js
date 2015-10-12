;(function(UI) {

    "use strict";

    UI.component('select', {

        defaults: {
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-commsy-select]", context).each(function() {
                    var element = UI.$(this);

                    if (!element.data("select")) {
                        var obj = UI.select(element, UI.Utils.options(element.attr("data-commsy-select")));
                    }
                });
            });
        },

        init: function() {
            var $this = this;

            var target = this.options.target ? UI.$(this.options.target) : [];
            if (!target.length) return;

            this.articles = target.find('article');
            this.inputs = target.find('input');

            this.bind();

            // button change
            this.on("change.uk.button", function(event) {
                if ($('#commsy-select-actions').css('display') == 'none') {
                    $('#commsy-select-actions').css('display', 'block');
                    $('#commsy-select-actions').css('height', '150px');
                } else {
                    $('#commsy-select-actions').css('display', 'none');
                    $('#commsy-select-actions').css('height', '0px');
                }

                UIkit.$html.trigger('changed.uk.dom');

                $this.articles.toggleClass('selectable');
            });

            // listen for dom changes
            UI.$html.on("changed.uk.dom", function(e) {
                $this.articles = target.find('article');
                $this.inputs = target.find('input');

                if ($this.articles.first().hasClass('selectable')) {
                    $this.articles.addClass('selectable');
                }

                $this.bind();
            });
        },

        bind: function() {
            // handle clicks on articles
            this.articles.off().on("click", function(event) {
                var article = $(this);

                // select mode?
                if (article.hasClass('selectable')) {
                    var checkbox = article.find('input[type="checkbox"]').first();

                    // only select if element has a checkbox
                    if (checkbox.length) {
                        // highlight the article
                        article.toggleClass('uk-comment-primary');

                        // toggle checkbox
                        checkbox.prop('checked', article.hasClass('uk-comment-primary'));

                        // disable normal click behaviour
                        event.preventDefault();
                    }
                }
            });

            // handle clicks on inputs
            this.inputs.off().on("click", function(event) {
                event.stopPropagation();
                $(this).parents('article').click();
            });
        }
    });

})(UIkit);