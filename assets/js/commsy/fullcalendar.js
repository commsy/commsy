;(function(UI) {
    
    "use strict";

    if ($("#calendar").length) {
        $('#calendar').fullCalendar({
            // put your options and callbacks here
            firstDay: 1,
            locale: $("#calendar").data("options").locale,
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
                if ($("#create-date").length) {
                    if (!date.hasTime()) {
                        date.time('12:00:00');
                    }
                    window.location.href = $('#calendar').data('events').dateUrl+'/create/'+encodeURIComponent(date.format('YYYY-MM-DD hh:mm:ss a'));
                }
            },
            eventClick: function(calEvent, jsEvent, view) {
                window.location.href = $('#calendar').data('events').dateUrl+'/'+calEvent.itemId;
            },
            eventMouseover: function(calEvent, jsEvent, view) {
                $(jsEvent.currentTarget).tooltipster({
                    content: $(renderEvent(calEvent)),
                    delay: 0,
                    animationDuration: 0,
                }).tooltipster('show');
            },
            eventMouseout: function(calEvent, jsEvent, view) {
                //UIkit.modal('#tooltip-'+calEvent._id).hide();
            },
            eventDrop: function(event, delta, revertFunc) {
                editEvent(event, revertFunc);
            },
            eventResize: function(event, delta, revertFunc) {
                editEvent(event, revertFunc);
            },
        });
        if ($('#calendar').data('height')) {
            $('#calendar').fullCalendar('option', 'height', $('#calendar').data('height'));
        }
        
        $('.fc-prevYear-button').tooltipster({
            content: $('#calendar').data('translations').prevYear,
        });
        
        $('.fc-prev-button').tooltipster({
            content: $('#calendar').data('translations').prev,
        });
        
        $('.fc-next-button').tooltipster({
            content: $('#calendar').data('translations').next,
        });
        
        $('.fc-nextYear-button').tooltipster({
            content: $('#calendar').data('translations').nextYear,
        });
    }

    if ($("#calendarDashboard").length) {
        $('#calendarDashboard').fullCalendar({
            // put your options and callbacks here
            firstDay: 1,
            locale: $("#calendarDashboard").data("options").locale,
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
            events: $('#calendarDashboard').data('events').url,
            dayClick: function(date, jsEvent, view) {
                if (!date.hasTime()) {
                    date.time('12:00:00');
                }
            },
            eventClick: function(calEvent, jsEvent, view) {
                window.location.href = $('#calendarDashboard').data('events').dateUrl+'/room/'+calEvent.contextId+'/date/'+calEvent.itemId;
            },
            eventMouseover: function(calEvent, jsEvent, view) {
                $(jsEvent.currentTarget).tooltipster({
                    content: $(renderEvent(calEvent)),
                    delay: 0,
                    animationDuration: 0,
                }).tooltipster('show');
            },
            eventMouseout: function(calEvent, jsEvent, view) {
                //UIkit.modal('#tooltip-'+calEvent._id).hide();
            },
            eventDrop: function(event, delta, revertFunc) {
                revertFunc();
            },
            eventResize: function(event, delta, revertFunc) {
                revertFunc();
            },
        });
        if ($('#calendarDashboard').data('height')) {
            $('#calendarDashboard').fullCalendar('option', 'height', $('#calendar').data('height'));
        }
        
        $('.fc-prevYear-button').tooltipster({
            content: $('#calendarDashboard').data('translations').prevYear,
        });
        
        $('.fc-prev-button').tooltipster({
            content: $('#calendarDashboard').data('translations').prev,
        });
        
        $('.fc-next-button').tooltipster({
            content: $('#calendarDashboard').data('translations').next,
        });
        
        $('.fc-nextYear-button').tooltipster({
            content: $('#calendarDashboard').data('translations').nextYear,
        });
    }

    function editEvent (event, revertFunc) {
        UIkit.modal.confirm($('#calendar').data('confirm-change'), function() {
            event.description = '...';
            $('#calendar').fullCalendar('updateEvent', event);
            
            $.ajax({
                url: $('#calendar').data('events').dateUrl+'/'+event.itemId+'/calendaredit',
                type: 'POST',
                data: JSON.stringify({
                    start: event.start,
                    end: event.end,
                    allDay: event.allDay
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
                UIkit.notify(textStatus, 'danger');
            });
        }, function () {
            revertFunc();
        }, {
            labels: {
                Cancel: $('#calendar').data('confirm-change-cancel'),
                Ok: $('#calendar').data('confirm-change.ok')
            }
        });
    }

    function renderEvent(calEvent) {
        let titleDisplay = '';
        if (calEvent.contextTitle != '') {
            titleDisplay = ' / '+calEvent.contextTitle;
        }
        
        let recurringDescription = '';
        if (calEvent.recurringDescription != '') {
            recurringDescription = '<tr>'
                                  +'<td>Serientermin:</td>'
                                  +'<td>'+calEvent.recurringDescription+'</td>'
                                  +'</tr>';
        }
        
        return '<div>'
                +'<table>'
                +'<tr>'
                +'<td colspan="2"><b>'+calEvent.title+'</b></td>'
                +'</tr>'
                +'<tr>'
                +'<td>Datum:</td>'
                +'<td>'+calEvent.description+'</td>'
                +'</tr>'
                +recurringDescription
                +'<tr>'
                +'<td>Ort:</td>'
                +'<td>'+calEvent.place+'</td>'
                +'</tr>'
                +'<tr>'
                +'<td>Teilnehmer:</td>'
                +'<td>'+calEvent.participants+'</td>'
                +'</tr>'
                +'<tr>'
                +'<td>Kalender:</td>'
                +'<td>'+calEvent.calendar+titleDisplay+'</td>'
                +'</tr>'
                +'</table>'
                +'</div>';
    }
    

})(UIkit);