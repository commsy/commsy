;(function(UI){

    "use strict";

    UIkit.on('beforeready.uk.dom', function() {

        var ref = UI.components.toggle.prototype.toggle;

        UI.components.toggle.prototype.toggle = function() {
            var ret = ref.apply(this, arguments);

            this.element.first().find('i').each(function() {
                if ($(this).hasClass('uk-icon-chevron-up')) {
                    $(this).removeClass('uk-icon-chevron-up').addClass('uk-icon-chevron-down');
                } else {
                    $(this).removeClass('uk-icon-chevron-down').addClass('uk-icon-chevron-up');
                }
            });

            // needed to update the display when a dynamic grid or slider is inside the hidden element
            UIkit.$html.trigger('changed.uk.dom');

            return ret;
        };
    });

})(UIkit);