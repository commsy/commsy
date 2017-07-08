;(function(UI){

    "use strict";

    UIkit.on('beforeready.uk.dom', function() {

        var ref = UI.components.toggle.prototype.toggle;

        UI.components.toggle.prototype.toggle = function() {
            var ret = ref.apply(this, arguments);

            this.element.first().find('i').each(function() {
                if ($(this).hasClass('uk-icon-chevron-up')) {
                    $(this).parent('a').attr('title', $(this).parent('a').data('unhide-title'));
                    $('.uk-tooltip-inner').text($(this).parent('a').data('unhide-title'));
                    $(this).removeClass('uk-icon-chevron-up').addClass('uk-icon-chevron-down');
                } else {
                    $(this).parent('a').attr('title', $(this).parent('a').data('hide-title'));
                    $('.uk-tooltip-inner').text($(this).parent('a').data('hide-title'));
                    $(this).removeClass('uk-icon-chevron-down').addClass('uk-icon-chevron-up');
                }
            });

            // needed to update the display when a dynamic grid or slider is inside the hidden element
            UIkit.$html.trigger('changed.uk.dom');

            // toggle show more / show less
            if (this.element.closest('article').find('span.cs-readmoreless')) {
                this.element.closest('article').find('span.cs-readmoreless').toggleClass('uk-hidden');
            }
            if ($($(this.element).data('cs-toggle-link-moreless')).find('.cs-readmoreless')) {
                $($(this.element).data('cs-toggle-link-moreless')).find('.cs-readmoreless').toggleClass('uk-hidden');
            }

            return ret;
        };
    });

})(UIkit);