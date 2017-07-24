;(function(UI) {
    
    "use strict";

    $('.calendars_check_all').on('change', function(){
        var target = '';
        var checked = false;

        if ($(this).attr('id') == 'calendars_check_all_dashboard') {
            target = '.calendars_checkboxes_dashboard';
            checked = $('#calendars_check_all_dashboard:checkbox:checked').length > 0;
        } else if ($(this).attr('id') == 'calendars_check_all_caldav') {
            target = '.calendars_checkboxes_caldav';
            checked = $('#calendars_check_all_caldav:checkbox:checked').length > 0;
        }

        setCheckboxes(target, checked);
    });

    function setCheckboxes (target, checked) {
        if (checked) {
            $(target).find('input[type=checkbox]').attr('checked', true);
        } else {
            $(target).find('input[type=checkbox]').attr('checked', false);
        }
    }

})(UIkit);