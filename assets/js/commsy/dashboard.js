;(function(UI) {
    
    "use strict";
if ($('#dashboard-ul').length > 0) {
    // listen for moved widgets
    UI.$html.on('stop.uk.sortable', function(e) {
        
        
        let data = [];
        $('#dashboard-ul').children('li').each(function() {
            data.push($(this).attr('id'));
        });
        
        $.ajax({
            url: $('#dashboard-ul').data('dashboard').url,
            type: 'POST',
            data: JSON.stringify({
                data,
            })
        }).done(function(data, textStatus, jqXHR) {
            UIkit.notify({
                message : data.message,
                status  : data.status,
                timeout : data.timeout,
                pos     : 'top-center'
            });
        }).fail(function(jqXHR, textStatus, errorThrown) {
            UIkit.notify(textStatus, 'danger');
        });
    });
}
})(UIkit);