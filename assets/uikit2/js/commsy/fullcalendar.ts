import { Calendar } from 'fullcalendar';
import dayGridPlugin from 'fullcalendar/daygrid';
import timeGridPlugin from 'fullcalendar/timegrid';
import interactionPlugin from 'fullcalendar/interaction';
import themePlugin from 'fullcalendar/themes/forma';
import { EventApi } from "fullcalendar";
import deLocale from 'fullcalendar/locales/de';
import enLocale from 'fullcalendar/locales/en-gb';

export function setup(id: string, editable: boolean = true): void {
  document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById(id);
    if (calendarEl) {
      let calendar = new Calendar(calendarEl, {
        plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin, themePlugin ],
        businessHours: {
          start: '8:00',
          end: '16:00',
          dow: [1, 2, 3, 4, 5]
        },
        initialView: calendarEl.dataset.defaultView ?? 'dayGridMonth',
        editable: editable,
        events: calendarEl.dataset.eventsUrl,
        // The theme marks an event in its own colour: a bar for events drawn as
        // a box, a dot for the entries of the month view. Both are invisible on
        // a light calendar, and a box needs an outline to set it off from the
        // one it overlaps. Styled in custom/full-calendar.less.
        blockEventClass: 'cs-fc-block-event',
        eventClass: (info) => info.event.extendedProps.lightColor ? 'cs-event-light-color' : '',
        listItemEventBeforeClass: (info) => info.event.extendedProps.lightColor ? 'cs-event-light-dot' : '',
        // An event's title is stuck to the top of its box by default, and
        // WebKit paints such an element above boxes of a higher stacking level:
        // the title of an event lying behind then shows through the one in
        // front, which is the overlap the users reported. Only the time grid
        // stacks events that way, so the row layouts keep their sticky titles.
        columnEventTitleSticky: false,
        headerToolbar: {
          left: 'dayGridMonth,timeGridWeek,timeGridDay',
          center: 'title',
          right: 'prevYear,prev,today,next,nextYear'
        },
        // Buttons are rendered with generated class names, so they get stable
        // hooks: one for sizing them in custom/full-calendar.less, and one per
        // navigation button for setupTooltips() below.
        buttonClass: 'cs-fc-button',
        buttons: {
          prevYear: { class: 'cs-fc-prev-year' },
          prev: { class: 'cs-fc-prev' },
          next: { class: 'cs-fc-next' },
          nextYear: { class: 'cs-fc-next-year' }
        },
        locales: [deLocale, enLocale],
        locale: calendarEl.dataset.locale === 'de' ? 'de' : 'en-gb',
        timeZone: 'UTC',
        views: {
          dayGridMonth: {
            eventTimeFormat: {
              hour: '2-digit',
              minute: '2-digit',
              omitZeroMinute: false,
              meridiem: false
            }
          },
          // Without this every view would be titled by its month alone
          timeGridWeek: {
            titleFormat: { year: 'numeric', month: 'short', day: 'numeric' }
          },
          timeGridDay: {
            titleFormat: { year: 'numeric', month: 'long', day: 'numeric' }
          }
        },
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
  $('.cs-fc-prev-year').tooltipster({
    content: translations.prevYear,
  });

  // @ts-ignore
  $('.cs-fc-prev').tooltipster({
    content: translations.prev,
  });

  // @ts-ignore
  $('.cs-fc-next').tooltipster({
    content: translations.next,
  });

  // @ts-ignore
  $('.cs-fc-next-year').tooltipster({
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
