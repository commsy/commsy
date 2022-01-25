'use strict';

export class FormCollection {
    private defaultOptions = {
        'selector': '',
        'removeMessage': 'Remove'
    };

    private options;

    public static bootstrap() {
        $('[data-form-collection]').each(function() {
            let formCollection = new FormCollection();
            formCollection.init(this);
        });
    }

    private init(element) {
        let self = this;

        $(function() {
            self.options = $.extend(self.options, self.defaultOptions, $(element).data('form-collection'));

            let $collectionHolder = $(self.options.selector);

            // Register click handler to add new entries
            $(element).find('button.add_item_link').on('click', function() {
                self.addWidget($collectionHolder);
            })

            // Add delete link to all existing entries
            $collectionHolder.children().each(function () {
                self.addDeleteLink($(this));
            })
        });
    }

    private addWidget(holder) {
        let counter = holder.children().length;

        let newWidget = holder.data('prototype');
        newWidget = newWidget.replace(/__name__/g, String(counter));
        counter++;

        let newElement = $('<div></div>').html(newWidget);
        newElement.appendTo(holder);

        this.addDeleteLink(newElement);
    }

    private addDeleteLink(element) {
        let deleteButton = $('<button class="uk-button uk-button-default" type="button">' + this.options.removeMessage + '</button>');
        element.append(deleteButton);

        deleteButton.on('click', function() {
            element.remove();
        })
    }
}