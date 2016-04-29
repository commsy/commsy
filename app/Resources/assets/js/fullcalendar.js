;(function(UI) {
    
    "use strict";

    if ($("#calendar").length) {
        $('#calendar').fullCalendar({
            // put your options and callbacks here
            firstDay: 1,
            lang: 'de',
            businesHours: {
                start: '8:00',
                end: '16:00',
                dow: [ 1, 2, 3, 4, 5 ]
            },
            header: {
                left:   'month,agendaWeek,agendaDay',
                center: 'title',
                right:  'prevYear,prev,today,next,nextYear'
            },
            events: $('#calendar').data('events').url,
            dayClick: function(date, jsEvent, view) {
                if (!date.hasTime()) {
                    date.time('12:00:00');
                }
                window.location.href = $('#calendar').data('events').editUrl+'/create/'+encodeURIComponent(date.format('YYYY-MM-DD hh:mm:ss a'));
            },
            eventClick: function(calEvent, jsEvent, view) {
                window.location.href = $('#calendar').data('events').editUrl+'/'+calEvent.itemId+'/edit';
            },
            eventMouseover: function(calEvent, jsEvent, view) {
                $(jsEvent.currentTarget).tooltipster({
                    content: $(renderEvent(calEvent))
                }).tooltipster('show');
            },
            eventMouseout: function(calEvent, jsEvent, view) {
                //UIkit.modal('#tooltip-'+calEvent._id).hide();
            },
            eventDrop: function(event, delta, revertFunc) {
                editEvent(event);
            },
            eventResize: function(event, delta, revertFunc) {
                editEvent(event);
            },
        });
    };

    function editEvent (event) {
        event.description = '...';
        $('#calendar').fullCalendar('updateEvent', event);
        
        $.ajax({
            url: $('#calendar').data('events').editUrl+'/'+event.itemId+'/calendaredit',
            type: 'POST',
            data: JSON.stringify({
                event,
            })
        }).done(function(data, textStatus, jqXHR) {
            event.description = data.data.description;
            
            $('#calendar').fullCalendar('updateEvent', event);
            
            UIkit.notify({
                message : data.message,
                status  : data.status,
                timeout : data.timeout,
                pos     : 'top-center'
            });
        }).fail(function(jqXHR, textStatus, errorThrown) {
            UIkit.notify(errorMessage, 'danger');
        });
    }

    function renderEvent(calEvent) {
        return '<div>'
                +'<table>'
                +'<tr>'
                +'<td colspan="2"><b>'+calEvent.title+'</b></td>'
                +'</tr>'
                +'<tr>'
                +'<td>Datum:</td>'
                +'<td>'+calEvent.description+'</td>'
                +'</tr>'
                +'<tr>'
                +'<td>Ort:</td>'
                +'<td>'+calEvent.place+'</td>'
                +'</tr>'
                +'<td>Teilnehmer:</td>'
                +'<td>'+calEvent.participants+'</td>'
                +'</tr>'
                +'</table>'
                +'</div>';
        
    }

})(UIkit);