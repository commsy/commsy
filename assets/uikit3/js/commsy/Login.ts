'use strict';

export function handleShibIdPSelect() {
    let $select = $('select#shib_login_idps_select');

    if($select.length) {
        let $link = $('a#shib_login_link');
        $link.attr('href', String($select.val()));

        $select.on('change', function () {
            $link.attr('href', String($(this).val()));
        });
    }
}