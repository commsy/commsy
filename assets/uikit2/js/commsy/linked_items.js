;(function(UI) {

    "use strict";

    UI.component('linkedItems', {

        defaults: {
            targetDiv: ''
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-linked-items]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("linkedItems")) {
                        var obj = UI.linkedItems(element, UI.Utils.options(element.attr("data-cs-linked-items")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            this.element.on('click', function(event) {
                if (event.target.nodeName != "INPUT") {
                    var checkbox = $(this).find('input[type="checkbox"]')[0];
                    $(checkbox).prop("checked", !$(checkbox).prop("checked"));
                }

                $(this).toggleClass('uk-comment-primary');
            });
        }
    });

})(UIkit);