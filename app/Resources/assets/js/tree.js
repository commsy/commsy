;(function(UI) {

    "use strict";

    UI.component('tree', {

        defaults: {
            tree: {
                core: {
                    themes: {
                        icons: false
                    },
                    multiple: true
                },
                checkbox: {
                    keep_selected_style: false
                },
                plugins: [
                    "wholerow",
                    "checkbox"
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
            $(element)
                .on('ready.jstree', function(event, data) {
                    // sync checkbox with tree state
                    $('input[id*="filter_category_category"]').each(function() {
                        var $input = $(this);

                        if ($input.prop('checked')) {
                            var value = $input.attr('value');

                            $(element).jstree(true).select_node('tag_' + value);
                        }
                    });

                    /**
                     * the following event handler are registered, after checkbox sync
                     * to prevent triggering the form submit (select_node can supress events,
                     * but this would prevent the tree from highlighting selected nodes)
                     */
                    $(element)
                        .on('changed.jstree', function(event, data) {
                            // sync tree state with Checkboxes
                            $('input[id*="filter_category_category"]').prop('checked', false);

                            $.each(data.selected, function() {
                                $('input[value="' + this.substring(4) + '"]')
                                    .prop('checked', true);

                            });

                            $('div#room-category').parents('form').submit();
                        });
                })
                // create the instance
                .jstree(this.options.tree);
        }
    });
    
    $('.cs-tree-plain').first('ul').jstree({
                                                core: {
                                                    themes: {
                                                        icons: false
                                                    },
                                                    multiple: true
                                                },
                                                checkbox: {
                                                    keep_selected_style: false
                                                },
                                                plugins: [
                                                    "wholerow"
                                                ]
                                            });

})(UIkit);