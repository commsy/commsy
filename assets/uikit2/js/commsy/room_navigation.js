;(function(UI) {

    'use strict';

    UI.component('roomNavigation', {

        defaults: {
            msgNoResults       : 'No results found',

            renderer: function(data) {

                var opts = this.options;

                this.dropdown.append(this.template({"items":data.results || [],  "msgNoResults": opts.msgNoResults}));
                this.show();
            }
        },

        autocomplete: null,

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$('[data-commsy-room-navigation]', context).each(function() {
                    let element = UI.$(this);

                    if (!element.data('roomNavigation')) {
                        UI.roomNavigation(element, UI.Utils.options(element.attr('data-commsy-room-navigation')));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            let $div = $this.element.find('div.uk-form');

            $this.element.on('show.uk.dropdown', function() {
                // lazy autocomplete setup prevents some positioning issues
                // since we are dealing with two nested dropdowns
                if (!$this.autocomplete) {
                    $this.autocomplete = UI.autocomplete($div, $this.options);
                    $this.autocomplete.dropdown.css('width', '100%');
                }

                $this.autocomplete.request();
            });

            // change browser location on select
            $this.on('selectitem.uk.autocomplete', function(e, data) {
                if (data.url) {
                    location.href = data.url;
                }
            });
        }
    });

})(UIkit);