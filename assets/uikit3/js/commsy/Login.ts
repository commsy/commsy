'use strict';

export function handleShibIdPSelect() {
    let $link = $('a#shib_login_link');
    let $select = $('select#shib_login_idps_select');

    $link.attr('href', String($select.val()));

    $select.on('change', function () {
        $link.attr('href', String($(this).val()));
    });
}