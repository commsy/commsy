;(function(UI) {
    
    "use strict";

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
        /*events: [
            {
                "title"  : "event1",
                "start"  : '2016-04-08'
            },
            {
                title  : 'event1',
                start  : '2016-04-08'
            },
            {
                title  : 'event2',
                start  : '2016-04-05',
                end    : '2016-04-07'
            },
            {
                title  : 'event3',
                start  : '2016-04-09T12:30:00',
                allDay : false // will make the time show
            }
        ]*/
    });

})(UIkit);