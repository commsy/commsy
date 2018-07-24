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

            if (source.length) {
                let element = source.get(0);
                let videoTags = $(element).find('video');

                if (videoTags.length || element.scrollHeight > $this.options.limit) {
                    $this.element
                        .removeClass('uk-hidden')
                        .removeClass('uk-invisible');
                    $this.element.siblings(".fade-preview:first").removeClass('uk-hidden');
                }
            } else {
                console.log('missing source:');
                console.dir(source);
            }
        }
    });

})(UIkit);