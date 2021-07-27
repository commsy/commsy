;(function(UI) {

    "use strict";

    UI.component('addCategory', {

        defaults: {
            targetDiv: ''
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-add-category]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("addCategory")) {
                        var obj = UI.addCategory(element, UI.Utils.options(element.attr("data-cs-add-category")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            this.element.on('click', function(event) {
                event.preventDefault();

                $this.onClickItem($this.element);
                
            });

            $('#itemLinks_newCategory').keypress(function(e) {
                if(e.which == 13) {
                    e.preventDefault();
                    $this.onClickItem($this.element);
                }
            });
        },

        onClickItem: function(element) {
            var $this = this;

            // add new category
            var categoryValue = $('#itemLinks_newCategory').val();
            var url = $('#itemLinks_newCategoryAdd').data('csAddCategory');

            $.ajax({
                url: url,
                type: "POST",
                data: {
                    title: categoryValue,
                }
            })
            .done(function(result, statusText, xhrObject) {
                // TODO: add new category to form: 1. add hidden checkbox field, 2. update jsTree object (via create_node()?)

                // clear user input
                $('#itemLinks_newCategory').val('');
            });
        }
    });

})(UIkit);