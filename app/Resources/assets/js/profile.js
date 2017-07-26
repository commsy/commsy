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
            $(target).find('input[type=checkbox]').prop('checked', true);
        } else {
            $(target).find('input[type=checkbox]').prop('checked', false);
        }
    }

    $('.calendars_checkboxes_dashboard input[type=checkbox]').on('change', function(){
        setAllNoneCheckbox('#calendars_check_all_dashboard', '.calendars_checkboxes_dashboard');
    });

    $('.calendars_checkboxes_caldav input[type=checkbox]').on('change', function(){
        setAllNoneCheckbox('#calendars_check_all_caldav', '.calendars_checkboxes_caldav');
    });

    function setAllNoneCheckbox (target, checkboxes) {
        if ($(checkboxes+' input[type=checkbox]:checked').length == $(checkboxes+' input[type=checkbox]').length) {
            $(target).prop('checked', true);
        } else {
            $(target).prop('checked', false);
        }
    }

})(UIkit);