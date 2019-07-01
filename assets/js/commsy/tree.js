;(function(UI) {

    "use strict";

    UI.component('tree', {

        defaults: {
            tree: {
                core: {
                    themes: {
                        icons: false
                    },
                    multiple: true,
                    dblclick_toggle: false
                },
                checkbox: {
                    keep_selected_style: false,
                    three_state: false,
                    // cascade: 'down',
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
                    let element = UI.$(this);

                    if (!element.data("tree")) {
                        let obj = UI.tree(element, UI.Utils.options(element.attr("data-cs-tree")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            let element = $this.element[0];

            // init jstree
            $(element)
                .on('ready.jstree', function(event, data) {
                    // sync checkbox with tree state
                    let selectNode = function() {
                        let $input = $(this);

                        if ($input.prop('checked')) {
                            let value = $input.attr('value');

                            $(element).jstree(true).select_node('tag_' + value);
                            $(element).jstree(true).open_node('tag_' + value);
                        }
                    };

                    $('input[id*="filter_category_category"]').each(selectNode);
                    $('input[id*="filter_participant_participant"]').each(selectNode);
                    $('input[id*="filter_calendar_calendar"]').each(selectNode);
                    $('input[id*="itemLinks_categories"]').each(selectNode);
                    $('input[id*="portfolio_categories"]').each(selectNode);

                    $(element)
                        .on('select_node.jstree', function (event, data) {
                            let node = data.node;
                            let instance = data.instance;

                            instance.open_all(node);
                        });

                    /**
                     * Register handler for select and deselect events, recursively selecting or deselecting
                     * all child nodes. We could use the "cascade" configuration for this, but it's "down" mode
                     * always selects all child nodes, even if the parent was the only selected one. In this case
                     * propergation is also done when syncing the checkbox states with the tree after submitting the
                     * filter form.
                     */
                    if ($this.options.custom && $this.options.custom.customCascade) {
                        $(element)
                            .on('select_node.jstree', function(event, data) {
                                let node = data.node;
                                let instance = data.instance;

                                $.each(node.children, function() {
                                    instance.select_node(this, true);
                                });
                            });

                        $(element)
                            .on('deselect_node.jstree', function(event, data) {
                                let node = data.node;
                                let instance = data.instance;

                                $.each(node.children, function() {
                                    instance.deselect_node(this, true);
                                });
                            });
                    }

                    /**
                     * the following event handler are registered, after checkbox sync
                     * to prevent triggering the form submit (select_node can supress events,
                     * but this would prevent the tree from highlighting selected nodes)
                     */
                    $(element)
                        .on('changed.jstree', function(event, data) {
                            if (data.node.a_attr.href.length > 1) {
                                window.location.hash = data.node.a_attr.href.substring(1);
                            } else {
                                // sync tree state with Checkboxes
                                $('input[id*="filter_category_category"]').prop('checked', false);
                                $('input[id*="filter_participant_participant"]').prop('checked', false);
                                $('input[id*="filter_calendar_calendar"]').prop('checked', false);
                                $('input[id*="itemLinks_categories"]').prop('checked', false);
                                $('input[id*="portfolio_categories"]').prop('checked', false);

                                $.each(data.selected, function() {
                                    $('input[value="' + this.substring(4) + '"]')
                                        .prop('checked', true);

                                });

                                $('div#room-category').parents('form').submit();
                            }
                        });
                })
                // create the instance
                .jstree(this.options.tree);

            // expand / collapse all
            if (this.options.custom && this.options.custom.toggle) {
                let $toggle = $('#' + this.options.custom.toggle);

                $toggle.click(function(event) {
                    let $i = $(this).find('i');

                    if ($i.hasClass('uk-icon-expand')) {
                        $(element).jstree(true).open_all();
                    } else {
                        $(element).jstree(true).close_all();
                    }

                    $i.toggleClass('uk-icon-expand uk-icon-compress');

                    event.preventDefault();
                });
            }
        }
    });
    
    jQuery.jstree.plugins.nohover = function() {
        this.hover_node = jQuery.noop;
    };

    $('.cs-tree-plain').first('ul').jstree({
        core: {
            themes: {
                icons: false
            },
            multiple: true
        },
        checkbox: {
            keep_selected_style: false,
            three_state: false
        },
        plugins: [
            "wholerow", "nohover"
        ]
    });

})(UIkit);