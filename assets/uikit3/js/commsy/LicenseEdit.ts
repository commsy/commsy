'use strict';

import * as UIkit from 'uikit3';

export class LicenseEdit {

    public static bootstrap() {
        // listen for nestable change events
        $('#licenseSortList').on('stop', function(event) {
            let $target = $(event.target);

            if ($target.attr('id') === 'licenseSortList') {
                let structure = LicenseEdit.buildStructure($target);
                $('input#license_sort_structure').val(JSON.stringify(structure));
            }
        });

        // delete buttons
        $('ul#licenseSortList').find('button.license_delete').click(function(event) {
            let $button = $(this);

            let id = $button.data('id');
            let $input = $('input[value="' + id + '"]');

            $input.prop('checked', 'checked');
            $input.closest('form').submit();

            event.preventDefault();
        });
    }

    public static buildStructure(rootNode) {
        // rootNode is an <ul/>, look for <li/> children
        let $children = rootNode.children('li');

        let structure = [];

        $children.each(function(position) {
            let $child = $(this);

            let id = $child.data('id');

            // add child
            structure[position] = {
                itemId: id
            }
        });

        return structure;
    }
}