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
                right:  'prev,today,next'
            },
            events: $('#calendar').data('events').url
        });
    };

})(UIkit);