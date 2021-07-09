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
                // TODO: add new category to form
                var countElements = $('#linksForm .category-form').children().length;
                $('#linksForm .category-form').append(
                    '<div>' +
                    '<input type="checkbox" id="itemLinks_categories_' + countElements + '" name="itemLinks[categories][]" value="' + result.categoryId + '">' +
                    '<label class="uk-form-label" for="itemLinks_categories_' + countElements + '">' + result.categoryTitle + '</label></div>'
                );

                // clear user input
                $('#itemLinks_newCategory').val('');
            });
        }
    });

})(UIkit);