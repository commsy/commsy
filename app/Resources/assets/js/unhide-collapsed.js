;(function(UI) {

    "use strict";

    UI.component('unhideCollapsed', {

        defaults: {
            src: '',
            limit: 140
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-unhide-collapsed]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("unhideCollapsed")) {
                        let obj = UI.unhideCollapsed(element, UI.Utils.options(element.attr("data-cs-unhide-collapsed")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            let source = $($this.options.src);

            if (source.get(0).scrollHeight > $this.options.limit) {
                $this.element
                    .removeClass('uk-hidden')
                    .removeClass('uk-invisible');
            }
        }
    });

})(UIkit);