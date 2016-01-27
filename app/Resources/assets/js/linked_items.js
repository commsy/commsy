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
                event.preventDefault();

                $this.onClickItem($this.element);
                
            });
        },

        onClickItem: function(element) {
            var $this = this;

            // console.log(this.options);

            // toggle linked items
            if (element.parents('#linksSubTab')[0]) {
                $("#itemsLinkedList").append(element[0]);

                if (element.hasClass('cs-linked-item-selected')) {
                    element.removeClass('cs-linked-item-selected');
                }

                // element.addClass('cs-linked-item-selected');
                element.children('div').addClass('cs-linked-item-selected');
                // element.children('div').toggleClass('cs-linked-item');

                //unset checkbox
                element.find('input[type="checkbox"]').prop("checked", true);

            } else if (element.parents('#itemsLinkedListWrapper')[0]) {
                $("#linksSubTab ul").append(element[0]);

                element.children('div').removeClass('cs-linked-item-selected');
                // element.children('div').removeClass('cs-linked-item');
                // remove grid style
                element.removeAttr('style');
                element.removeAttr('data-grid-prepared');

                // set checkbox
                element.find  ('input[type="checkbox"]').prop("checked", false);
            }


        }
    });

})(UIkit);