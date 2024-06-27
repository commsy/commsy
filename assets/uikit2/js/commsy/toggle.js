;(function(UI){

    "use strict";

    // TODO: this function somehow gets triggered twice but it should only get called once; the second invocation
    // would revert the hide/unhide again; so, as a workaround, we use `one()` to ensure that the function
    // only gets executed once
    // Addition: It turned out the problem occurred when including js with the "defer" attribute,
    // see webpack_encore.yaml
    UIkit.one('beforeready.uk.dom', function() {

        var toggleRef = UI.components.toggle.prototype.toggle;
        UI.components.toggle.prototype.toggle = function() {
            var ret = toggleRef.apply(this, arguments);

            // If the bypass options is set, ignore all the custom code
            // This is hacky, but allows for the default uk-toggle behaviour
            const bypass = this.options.bypass || false;
            if (bypass) {
              return ret;
            }

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
                if(!this.element.closest('article').find('span.cs-readmoreless').parent('a').hasClass('uk-invisible')) {
                    this.element.closest('article').find(".fade-preview").toggleClass("uk-hidden");
                }
            }
            if ($($(this.element).data('cs-toggle-link-moreless')).find('.cs-readmoreless')) {
                $($(this.element).data('cs-toggle-link-moreless')).find('.cs-readmoreless').toggleClass('uk-hidden');
            }

            this.getToggles();
            if (this.totoggle.length) {
                // check if the target has the "uk-hidden-small" class and is currently not visible
                if (this.totoggle.hasClass('uk-hidden-small') && !this.totoggle.is(':visible')) {
                    // remove the uk-hidden-small class so that toggling the uk-hidden class has an effect
                    this.totoggle.removeClass('uk-hidden-small');
                }
            }

            return ret;
        };

        var initRef = UI.components.toggle.prototype.init;
        UI.components.toggle.prototype.init = function() {
            var ret = initRef.apply(this, arguments);

            this.getToggles(); // populates the `totoggle` variable
            let self = this;

            if(!this.totoggle.length) return;

            // NOTE: We register an event handler which, on page load, orientation change or window resize, ensures that togglable
            // panels (which are hidden on small viewport sizes) are in fact togglable and have the correct toggle button state.
            // Also, we wrap our function within `debounce` and use a large wait parameter to avoid excessive execution.
            UI.$win.on('load orientationchange resize', UI.Utils.debounce((function(){

                var fn = function() {
                    // check if the target has the "uk-hidden-small" class and is currently not visible
                    if (self.totoggle.hasClass('uk-hidden-small') && !self.totoggle.is(':visible')) {
                        // on small devices, we initially hide some content like sidebar panels;
                        // to reflect the correct state, we need to toggle once and remove the "uk-hidden-small" class
                        self.toggle();
                        self.totoggle.removeClass('uk-hidden-small');
                    }
                    return fn;
                };

                return fn();

            })(), 500));

            return ret;
        };
    });

})(UIkit);
