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
                console.log('Clicked on: ' + date.format());
                console.log('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);
                console.log('Current view: ' + view.name);
        
                // change the day's background color just for fun
                $(this).css('background-color', 'red');
        
            },
            eventClick: function(calEvent, jsEvent, view) {
                console.log('Event: ' + calEvent.title);
                console.log('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);
                console.log('View: ' + view.name);
        
                // change the border color just for fun
                $(this).css('border-color', 'red');
        
            },
            eventMouseover: function(calEvent, jsEvent, view) {
                $(jsEvent.currentTarget).tooltipster({
                    content: $('<div>'+calEvent.title+'</div>')
                }).tooltipster('show');
            },
            eventMouseout: function(calEvent, jsEvent, view) {
                //UIkit.modal('#tooltip-'+calEvent._id).hide();
            },
        });
    };

})(UIkit);