import UIkit from 'uikit';

;(function(UI) {

    "use strict";

    UI.component('readMore', {

        defaults: {
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-readmore]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("readMore")) {
                        let obj = UI.readMore(element, UI.Utils.options(element.attr("data-cs-readmore")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            $this.element.on('click', function(event) {
                event.preventDefault();

                // trigger the normal toggle mechanism by faking a click
                if ($this.element.closest('article').find('a[data-uk-toggle]')[0]) {
                    $this.element.closest('article').find('a[data-uk-toggle]')[0].click();
                } else if ($($($this.element).data('cs-toggle-link'))[0]) {
                    $($($this.element).data('cs-toggle-link'))[0].click();
                }
            });
        }
    });

})(UIkit);