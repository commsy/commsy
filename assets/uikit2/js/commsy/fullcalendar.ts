import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import EventApi from "@fullcalendar/core/api/EventApi";

export function setup(locales, id: string, editable: boolean = true): void {
  document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById(id);
    if (calendarEl) {
      let calendar = new Calendar(calendarEl, {
        plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin ],
        businessHours: {
          start: '8:00',
          end: '16:00',
          dow: [1, 2, 3, 4, 5]
        },
        defaultView: calendarEl.dataset.defaultView ?? null,
        editable: editable,
        events: calendarEl.dataset.eventsUrl,
        header: {
          left: 'dayGridMonth,timeGridWeek,timeGridDay',
          center: 'title',
          right: 'prevYear,prev,today,next,nextYear'
        },
        locales: locales,
        locale: calendarEl.dataset.locale === 'de' ? 'de' : 'en-gb',
        timeZone: 'UTC',
        dateClick(info) {
          if (editable) {
            const createEl = document.getElementById('create-date');
            if (createEl) {
              const start = encodeURIComponent(info.date.toISOString());
              window.location.href = calendarEl.dataset.eventsCreateUrl + '/' + start;
            }
          }
        },
        eventClick(info)  {
          let listUrl = decodeURI(calendarEl.dataset.eventsListUrl);
          listUrl = listUrl.replace('<roomId>', info.event.extendedProps.contextId);
          window.location.href = listUrl + '/' + info.event.id;
        },
        eventMouseEnter(info) {
          // @ts-ignore
          $(info.el).tooltipster({
            content: $(renderEvent(calendarEl, info.event)),
            delay: 0,
            animationDuration: 0,
          }).tooltipster('show');
        },
        eventDrop(info) {
          editEvent(calendarEl, info.event, info.revert);
        },
        eventResize(info) {
          editEvent(calendarEl, info.event, info.revert);
        }
      });

      calendar.render();

      setupTooltips(calendarEl);
    }
  });
}

function setupTooltips(calendarEl: HTMLElement): void {
  const translations = JSON.parse(calendarEl.dataset.translations);

  // @ts-ignore
  $('.fc-prevYear-button').tooltipster({
    content: translations.prevYear,
  });

  // @ts-ignore
  $('.fc-prev-button').tooltipster({
    content: translations.prev,
  });

  // @ts-ignore
  $('.fc-next-button').tooltipster({
    content: translations.next,
  });

  // @ts-ignore
  $('.fc-nextYear-button').tooltipster({
    content: translations.nextYear,
  });
}

function editEvent(calendarEl: HTMLElement, event: EventApi, revert: () => void): void {
  UIkit.modal.confirm(calendarEl.dataset.confirmChange, function () {
    event.setExtendedProp('description', '...');

    let listUrl = decodeURI(calendarEl.dataset.eventsListUrl);
    listUrl = listUrl.replace('<roomId>', event.extendedProps.contextId);

    $.ajax({
      url: listUrl + '/' + event.id + '/calendaredit',
      type: 'POST',
      data: JSON.stringify({
        start: event.start,
        end: event.end,
        allDay: event.allDay
      })
    }).done((data) => {
      event.setExtendedProp('description', data.description);

      UIkit.notify({
        message: data.message,
        status: data.status,
        timeout: data.timeout,
        pos: 'top-center'
      });
    }).fail((jqXHR, textStatus) => {
      UIkit.notify(textStatus, 'danger');
    });
  }, () => {
    revert();
  }, <any>{
    labels: {
      Cancel: calendarEl.dataset.confirmChangeCancel,
      Ok: calendarEl.dataset.confirmChangeOk
    }
  });
}

function renderEvent(calendarEl: HTMLElement, event: EventApi): string {
  let titleDisplay: string = '';
  if (event.extendedProps.contextTitle != '') {
    titleDisplay = ' / ' + event.extendedProps.contextTitle;
  }

  const translations = JSON.parse(calendarEl.dataset.translations);

  let recurringDescription: string = '';
  if (event.extendedProps.recurringDescription != '') {
    recurringDescription = '<tr>'
      + '<td>' + translations.recurringDate + ':</td>'
      + '<td>' + event.extendedProps.recurringDescription + '</td>'
      + '</tr>';
  }

  return '<div class="uk-grid">'
    + '<table>'
    + '<tr>'
    + '<td colspan="2"><b>' + event.title + '</b></td>'
    + '</tr>'
    + '<tr>'
    + '<td>' + translations.date + ':</td>'
    + '<td>' + event.extendedProps.description + '</td>'
    + '</tr>'
    + recurringDescription
    + '<tr>'
    + '<td>' + translations.place + ':</td>'
    + '<td>' + event.extendedProps.place + '</td>'
    + '</tr>'
    + '<tr>'
    + '<td>' + translations.participants + ':</td>'
    + '<td>' + event.extendedProps.participants + '</td>'
    + '</tr>'
    + '<tr>'
    + '<td>' + translations.calendar + ':</td>'
    + '<td>' + event.extendedProps.calendar + titleDisplay + '</td>'
    + '</tr>'
    + '</table>'
    + '</div>';
}
