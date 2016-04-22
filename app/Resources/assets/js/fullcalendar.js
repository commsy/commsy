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
                window.location.href = $('#calendar').data('events').createUrl;
            },
            eventClick: function(calEvent, jsEvent, view) {
                
            },
            eventMouseover: function(calEvent, jsEvent, view) {
                $(jsEvent.currentTarget).tooltipster({
                    content: $(renderEvent(calEvent))
                }).tooltipster('show');
            },
            eventMouseout: function(calEvent, jsEvent, view) {
                //UIkit.modal('#tooltip-'+calEvent._id).hide();
            },
        });
    };

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