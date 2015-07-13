;(function(UI) {

    "use strict";

    UI.component('tree', {

        defaults: {
            tree: {
                core: {
                    themes: {
                        icons: false
                    }
                },
                plugins: [
                    "wholerow"
                ]
            }
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-tree]", context).each(function() {
                    var element = UI.$(this);

                    if (!element.data("tree")) {
                        var obj = UI.tree(element, UI.Utils.options(element.attr("data-cs-tree")));
                    }
                });
            });
        },

        init: function() {
            var $this = this;

            var element = $this.element[0];

            // init jstree
            $(element).jstree(this.options.tree);

            // var target = this.options.target ? UI.$(this.options.target) : [];
            // if (!target.length) return;
        }
    });

})(UIkit);