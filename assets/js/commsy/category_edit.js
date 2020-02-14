import UIkit from 'uikit';

'use strict';

let buildStructure = function(rootNode) {
    // rootNode is an <ul/>, look for <li/> children
    let $children = rootNode.children('li');

    let structure = [];

    $children.each(function(position) {
        let $child = $(this);

        let id = $child.data('id');

        // add child
        structure[position] = {
            itemId: id,
            children: []
        };

        // recursive call
        let $childList = $child.children('ul');
        if ($childList.length) {
            structure[position].children = buildStructure($childList);
        }
    });

    return structure;
};

// listen for nestable change events
UIkit.util.on('#categoryEditList', 'moved', function() {
    let structure = buildStructure($target);

    $('input#category_edit_structure').val(JSON.stringify(structure));
});

// delete buttons
$('ul#categoryEditList').find('button.category_delete').click(function(event) {
    let $button = $(this);

    let id = $button.data('id');
    let $input = $('input[value="' + id + '"]');

    $input.prop('checked', 'checked');
    $input.closest('form').submit();

    event.preventDefault();
});