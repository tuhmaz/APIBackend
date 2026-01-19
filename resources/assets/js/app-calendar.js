/**
 * App Calendar
 */

/**
 * ! If both start and end dates are same Full calendar will nullify the end date value.
 * ! Full calendar will end the event on a day before at 12:00:00AM thus, event won't extend to the end date.
 * ! We are getting events from a separate file named app-calendar-events.js. You can add or remove events from there.
 *
 **/

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const direction = isRtl ? 'rtl' : 'ltr';
  (function () {
    // DOM Elements
    const calendarEl = document.getElementById('calendar');
    const appCalendarSidebar = document.querySelector('.app-calendar-sidebar');
    const addEventSidebar = document.getElementById('addEventSidebar');
    const appOverlay = document.querySelector('.app-overlay');
    const offcanvasTitle = document.querySelector('.offcanvas-title');
    const btnToggleSidebar = document.querySelector('.btn-toggle-sidebar');
    // Submit button inside offcanvas form
    const btnSubmit = document.querySelector('.btn-add-event');
    const btnDeleteEvent = document.querySelector('.btn-delete-event');
    const btnCancel = document.querySelector('.btn-cancel');
    const eventTitle = document.getElementById('eventTitle');
    const eventStartDate = document.getElementById('eventStartDate');
    const eventEndDate = document.getElementById('eventEndDate');
    const eventUrl = document.getElementById('eventURL');
    const eventLocation = document.getElementById('eventLocation');
    const eventDescription = document.getElementById('eventDescription');
    const allDaySwitch = document.querySelector('.allDay-switch');
    const selectAll = document.querySelector('.select-all');
    const filterInputs = Array.from(document.querySelectorAll('.input-filter'));
    const inlineCalendar = document.querySelector('.inline-calendar');

    // Calendar settings
    const calendarColors = {
      Business: 'primary',
      Holiday: 'success',
      Personal: 'danger',
      Family: 'warning',
      ETC: 'info'
    };

    // External jQuery Elements
    const eventLabel = $('#eventLabel'); // ! Using jQuery vars due to select2 jQuery dependency
    const eventGuests = $('#eventGuests'); // ! Using jQuery vars due to select2 jQuery dependency

    // Event Data
    // Events are loaded from backend
    let currentEvents = [];
    let isFormValid = false;
    let eventToUpdate = null;
    let inlineCalInstance = null;

    // Offcanvas Instance (guard if sidebar exists)
    const bsAddEventSidebar = addEventSidebar ? new bootstrap.Offcanvas(addEventSidebar) : null;

    //! TODO: Update Event label and guest code to JS once select removes jQuery dependency
    // Initialize Select2 with custom templates
    if (eventLabel.length) {
      function renderBadges(option) {
        if (!option.id) {
          return option.text;
        }
        var $badge =
          "<span class='badge badge-dot bg-" + $(option.element).data('label') + " me-2'> " + '</span>' + option.text;

        return $badge;
      }
      eventLabel.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Select value',
        dropdownParent: eventLabel.parent(),
        templateResult: renderBadges,
        templateSelection: renderBadges,
        minimumResultsForSearch: -1,
        escapeMarkup: function (es) {
          return es;
        }
      });
    }

    // Render guest avatars
    if (eventGuests.length) {
      function renderGuestAvatar(option) {
        if (!option.id) return option.text;
        return `
    <div class='d-flex flex-wrap align-items-center'>
      <div class='avatar avatar-xs me-2'>
        <img src='${assetsPath}img/avatars/${$(option.element).data('avatar')}'
          alt='avatar' class='rounded-circle' />
      </div>
      ${option.text}
    </div>`;
      }
      eventGuests.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Select value',
        dropdownParent: eventGuests.parent(),
        closeOnSelect: false,
        templateResult: renderGuestAvatar,
        templateSelection: renderGuestAvatar,
        escapeMarkup: function (es) {
          return es;
        }
      });
    }

    // Event start (flatpicker)
    if (eventStartDate) {
      var start = eventStartDate.flatpickr({
        monthSelectorType: 'static',
        static: true,
        enableTime: true,
        altFormat: 'Y-m-dTH:i:S',
        onReady: function (selectedDates, dateStr, instance) {
          if (instance.isMobile) {
            instance.mobileInput.setAttribute('step', null);
          }
        }
      });
    }

    // Event end (flatpicker)
    if (eventEndDate) {
      var end = eventEndDate.flatpickr({
        monthSelectorType: 'static',
        static: true,
        enableTime: true,
        altFormat: 'Y-m-dTH:i:S',
        onReady: function (selectedDates, dateStr, instance) {
          if (instance.isMobile) {
            instance.mobileInput.setAttribute('step', null);
          }
        }
      });
    }

    // Inline sidebar calendar (flatpicker)
    if (inlineCalendar) {
      inlineCalInstance = inlineCalendar.flatpickr({
        monthSelectorType: 'static',
        static: true,
        inline: true
      });
    }

    // Event click function
    function eventClick(info) {
      eventToUpdate = info.event;
      if (eventToUpdate.url) {
        info.jsEvent.preventDefault();
        window.open(eventToUpdate.url, '_blank');
      }
      if (bsAddEventSidebar) bsAddEventSidebar.show();
      // For update event set offcanvas title text: Update Event
      if (offcanvasTitle) {
        offcanvasTitle.innerHTML = 'Update Event';
      }
      if (btnSubmit) {
        btnSubmit.innerHTML = 'Update';
        btnSubmit.classList.add('btn-update-event');
        btnSubmit.classList.remove('btn-add-event');
      }
      if (btnDeleteEvent) btnDeleteEvent.classList.remove('d-none');

      if (eventTitle) eventTitle.value = eventToUpdate.title || '';
      if (typeof start !== 'undefined' && start) start.setDate(eventToUpdate.start, true, 'Y-m-d');
      if (allDaySwitch) eventToUpdate.allDay === true ? (allDaySwitch.checked = true) : (allDaySwitch.checked = false);
      if (typeof end !== 'undefined' && end) {
        eventToUpdate.end !== null
          ? end.setDate(eventToUpdate.end, true, 'Y-m-d')
          : end.setDate(eventToUpdate.start, true, 'Y-m-d');
      }
      eventLabel.val(eventToUpdate.extendedProps.calendar).trigger('change');
      if (eventLocation && eventToUpdate.extendedProps.location !== undefined) {
        eventLocation.value = eventToUpdate.extendedProps.location;
      }
      eventToUpdate.extendedProps.guests !== undefined
        ? eventGuests.val(eventToUpdate.extendedProps.guests).trigger('change')
        : null;
      if (eventDescription && eventToUpdate.extendedProps.description !== undefined) {
        eventDescription.value = eventToUpdate.extendedProps.description;
      }
    }

    // Modify sidebar toggler
    function modifyToggler() {
      const fcSidebarToggleButton = document.querySelector('.fc-sidebarToggle-button');
      if (!fcSidebarToggleButton) return;
      fcSidebarToggleButton.classList.remove('fc-button-primary');
      fcSidebarToggleButton.classList.add('d-lg-none', 'd-inline-block', 'ps-0');
      while (fcSidebarToggleButton.firstChild) {
        fcSidebarToggleButton.firstChild.remove();
      }
      fcSidebarToggleButton.setAttribute('data-bs-toggle', 'sidebar');
      fcSidebarToggleButton.setAttribute('data-overlay', '');
      fcSidebarToggleButton.setAttribute('data-target', '#app-calendar-sidebar');
      fcSidebarToggleButton.insertAdjacentHTML(
        'beforeend',
        '<i class="icon-base ti tabler-menu-2 icon-lg text-heading"></i>'
      );
    }

    // Filter events by calender
    function selectedCalendars() {
      let selected = [],
        filterInputChecked = [].slice.call(document.querySelectorAll('.input-filter:checked'));

      filterInputChecked.forEach(item => {
        selected.push(item.getAttribute('data-value'));
      });

      return selected;
    }

    // --------------------------------------------------------------------------------------------------
    // AXIOS: fetchEvents
    // * This will be called by fullCalendar to fetch events. Also this can be used to refetch events.
    // --------------------------------------------------------------------------------------------------
    function fetchEvents(info, successCallback) {
      const dbSelect = document.getElementById('select-database');
      const database = dbSelect ? dbSelect.value : (window.appCalendar && window.appCalendar.currentDatabase) || null;
      const params = new URLSearchParams();
      if (database) params.append('database', database);
      if (info && info.startStr) params.append('start', info.startStr);
      if (info && info.endStr) params.append('end', info.endStr);

      fetch(`/dashboard/calendar-events?${params.toString()}`, {
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(res => res.json())
        .then(json => {
          if (json && json.status === 'success') {
            currentEvents = json.data || [];
            successCallback(currentEvents);
          } else {
            successCallback([]);
          }
        })
        .catch(() => successCallback([]));
    }

    // Init FullCalendar
    // ------------------------------------------------
    if (!calendarEl) {
      // Calendar container not present, do not init
      return;
    }
    let calendar = new Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      events: fetchEvents,
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
      editable: true,
      dragScroll: true,
      dayMaxEvents: 2,
      eventResizableFromStart: true,
      customButtons: {
        sidebarToggle: {
          text: 'Sidebar'
        }
      },
      headerToolbar: {
        start: 'sidebarToggle, prev,next, title',
        end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
      },
      direction: direction,
      initialDate: new Date(),
      navLinks: true, // can click day/week names to navigate views
      eventClassNames: function ({ event: calendarEvent }) {
        const colorName = calendarColors[calendarEvent._def.extendedProps.calendar];
        // Background Color
        return ['bg-label-' + colorName];
      },
      dateClick: function (info) {
        let date = moment(info.date).format('YYYY-MM-DD');
        resetValues();
        if (bsAddEventSidebar) bsAddEventSidebar.show();

        // For new event set offcanvas title text: Add Event
        if (offcanvasTitle) {
          offcanvasTitle.innerHTML = 'Add Event';
        }
        if (btnSubmit) {
          btnSubmit.innerHTML = 'Add';
          btnSubmit.classList.remove('btn-update-event');
          btnSubmit.classList.add('btn-add-event');
        }
        if (btnDeleteEvent) btnDeleteEvent.classList.add('d-none');
        if (eventStartDate) eventStartDate.value = date;
        if (eventEndDate) eventEndDate.value = date;
      },
      eventClick: function (info) {
        eventClick(info);
      },
      datesSet: function () {
        modifyToggler();
      },
      viewDidMount: function () {
        modifyToggler();
      }
    });

    // Render calendar
    calendar.render();
    // Modify sidebar toggler
    modifyToggler();

    const eventForm = document.getElementById('eventForm');
    if (eventForm && window.FormValidation && FormValidation.formValidation) {
      const fv = FormValidation.formValidation(eventForm, {
        fields: {
          eventTitle: {
            validators: {
              notEmpty: { message: 'Please enter event title ' }
            }
          },
          eventStartDate: {
            validators: {
              notEmpty: { message: 'Please enter start date ' }
            }
          },
          eventEndDate: {
            validators: {
              notEmpty: { message: 'Please enter end date ' }
            }
          }
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap5: new FormValidation.plugins.Bootstrap5({
            eleValidClass: '',
            rowSelector: function () { return '.form-control-validation'; }
          }),
          submitButton: new FormValidation.plugins.SubmitButton(),
          autoFocus: new FormValidation.plugins.AutoFocus()
        }
      })
        .on('core.form.valid', function () { isFormValid = true; })
        .on('core.form.invalid', function () { isFormValid = false; });
    } else {
      // Fallback simple validation if FormValidation is not loaded
      isFormValid = true;
      eventForm.addEventListener('input', () => {
        isFormValid = Boolean(eventTitle.value && eventStartDate.value);
      });
    }

    // Sidebar Toggle Btn
    if (btnToggleSidebar) {
      btnToggleSidebar.addEventListener('click', e => {
        if (btnCancel) btnCancel.classList.remove('d-none');
      });
    }

    // Add Event
    // ------------------------------------------------
    function addEvent(eventData) {
      // ? Add new event data to current events object and refetch it to display on calender
      // ? You can write below code to AJAX call success response

      currentEvents.push(eventData);
      calendar.refetchEvents();

      // ? To add event directly to calender (won't update currentEvents object)
      // calendar.addEvent(eventData);
    }

    // Update Event
    // ------------------------------------------------
    function updateEvent(eventData) {
      // ? Update existing event data to current events object and refetch it to display on calender
      // ? You can write below code to AJAX call success response
      eventData.id = parseInt(eventData.id);
      currentEvents[currentEvents.findIndex(el => el.id === eventData.id)] = eventData; // Update event by id
      calendar.refetchEvents();

      // ? To update event directly to calender (won't update currentEvents object)
      // let propsToUpdate = ['id', 'title', 'url'];
      // let extendedPropsToUpdate = ['calendar', 'guests', 'location', 'description'];

      // updateEventInCalendar(eventData, propsToUpdate, extendedPropsToUpdate);
    }

    // Remove Event
    // ------------------------------------------------

    function removeEvent(eventId) {
      // ? Delete existing event data to current events object and refetch it to display on calender
      // ? You can write below code to AJAX call success response
      currentEvents = currentEvents.filter(function (event) {
        return event.id != eventId;
      });
      calendar.refetchEvents();

      // ? To delete event directly to calender (won't update currentEvents object)
      // removeEventInCalendar(eventId);
    }

    // (Update Event In Calendar (UI Only)
    // ------------------------------------------------
    const updateEventInCalendar = (updatedEventData, propsToUpdate, extendedPropsToUpdate) => {
      const existingEvent = calendar.getEventById(updatedEventData.id);

      // --- Set event properties except date related ----- //
      // ? Docs: https://fullcalendar.io/docs/Event-setProp
      // dateRelatedProps => ['start', 'end', 'allDay']
      // eslint-disable-next-line no-plusplus
      for (var index = 0; index < propsToUpdate.length; index++) {
        var propName = propsToUpdate[index];
        existingEvent.setProp(propName, updatedEventData[propName]);
      }

      // --- Set date related props ----- //
      // ? Docs: https://fullcalendar.io/docs/Event-setDates
      existingEvent.setDates(updatedEventData.start, updatedEventData.end, {
        allDay: updatedEventData.allDay
      });

      // --- Set event's extendedProps ----- //
      // ? Docs: https://fullcalendar.io/docs/Event-setExtendedProp
      // eslint-disable-next-line no-plusplus
      for (var index = 0; index < extendedPropsToUpdate.length; index++) {
        var propName = extendedPropsToUpdate[index];
        existingEvent.setExtendedProp(propName, updatedEventData.extendedProps[propName]);
      }
    };

    // Remove Event In Calendar (UI Only)
    // ------------------------------------------------
    function removeEventInCalendar(eventId) {
      calendar.getEventById(eventId).remove();
    }

    // Add new event
    // ------------------------------------------------
    if (btnSubmit) btnSubmit.addEventListener('click', e => {
      if (btnSubmit.classList.contains('btn-add-event')) {
        if (isFormValid) {
          const csrf = document.querySelector('meta[name="csrf-token"]');
          const token = csrf ? csrf.getAttribute('content') : '';
          const payload = {
            title: eventTitle.value,
            description: eventDescription.value,
            event_date: eventStartDate.value,
            eventDatabase: document.getElementById('eventDatabase')?.value
          };
          btnSubmit.disabled = true;
          fetch('/dashboard/calendar/store', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
          })
            .then(res => res.json())
            .then(json => {
              if (json && json.status === 'success') {
                // Optimistically add event from response shape
                addEvent(json.data);
                if (bsAddEventSidebar) bsAddEventSidebar.hide();
              } else {
                // Fallback: refetch to ensure UI sync
                calendar.refetchEvents();
              }
            })
            .catch(() => {
              calendar.refetchEvents();
            })
            .finally(() => {
              if (btnSubmit) btnSubmit.disabled = false;
            });
        }
      } else {
        // Update event
        // ------------------------------------------------
        if (isFormValid) {
          const csrf = document.querySelector('meta[name="csrf-token"]');
          const token = csrf ? csrf.getAttribute('content') : '';
          const id = eventToUpdate.id;
          const payload = {
            title: eventTitle.value,
            description: eventDescription.value,
            event_date: eventStartDate.value,
            eventDatabase: document.getElementById('eventDatabase')?.value
          };
          btnSubmit.disabled = true;
          fetch(`/dashboard/calendar/${id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
          })
            .then(res => res.json())
            .then(json => {
              if (json && json.status === 'success') {
                updateEvent(json.data);
                bsAddEventSidebar.hide();
              } else {
                calendar.refetchEvents();
              }
            })
            .catch(() => {
              calendar.refetchEvents();
            })
            .finally(() => {
              if (btnSubmit) btnSubmit.disabled = false;
            });
        }
      }
    });

    // Call removeEvent function
    if (btnDeleteEvent) btnDeleteEvent.addEventListener('click', e => {
      const csrf = document.querySelector('meta[name="csrf-token"]');
      const token = csrf ? csrf.getAttribute('content') : '';
      const id = parseInt(eventToUpdate.id);
      const db = document.getElementById('eventDatabase')?.value;
      fetch(`/dashboard/calendar/${id}?database=${encodeURIComponent(db || '')}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token
        }
      })
        .then(res => res.json())
        .finally(() => {
          removeEvent(id);
          if (bsAddEventSidebar) bsAddEventSidebar.hide();
        });
    });

    // Reset event form inputs values
    // ------------------------------------------------
    function resetValues() {
      if (eventEndDate) eventEndDate.value = '';
      if (eventUrl) eventUrl.value = '';
      if (eventStartDate) eventStartDate.value = '';
      if (eventTitle) eventTitle.value = '';
      if (eventLocation) eventLocation.value = '';
      if (allDaySwitch) allDaySwitch.checked = false;
      if (eventGuests && eventGuests.length) eventGuests.val('').trigger('change');
      if (eventDescription) eventDescription.value = '';
    }

    // When modal hides reset input values
    if (addEventSidebar) {
      addEventSidebar.addEventListener('hidden.bs.offcanvas', function () {
        resetValues();
      });
    }

    // Hide left sidebar if the right sidebar is open
    if (btnToggleSidebar) btnToggleSidebar.addEventListener('click', e => {
      if (offcanvasTitle) {
        offcanvasTitle.innerHTML = 'Add Event';
      }
      if (btnSubmit) {
        btnSubmit.innerHTML = 'Add';
        btnSubmit.classList.remove('btn-update-event');
        btnSubmit.classList.add('btn-add-event');
      }
      if (btnDeleteEvent) btnDeleteEvent.classList.add('d-none');
      if (appCalendarSidebar) appCalendarSidebar.classList.remove('show');
      if (appOverlay) appOverlay.classList.remove('show');
    });

    // Calender filter functionality
    // ------------------------------------------------
    if (selectAll) {
      selectAll.addEventListener('click', e => {
        if (e.currentTarget.checked) {
          document.querySelectorAll('.input-filter').forEach(c => (c.checked = 1));
        } else {
          document.querySelectorAll('.input-filter').forEach(c => (c.checked = 0));
        }
        calendar.refetchEvents();
      });
    }

    // Refetch events when database selection changes
    const dbSelectEl = document.getElementById('select-database');
    if (dbSelectEl) {
      dbSelectEl.addEventListener('change', () => {
        calendar.refetchEvents();
      });
    }

    if (filterInputs) {
      filterInputs.forEach(item => {
        item.addEventListener('click', () => {
          document.querySelectorAll('.input-filter:checked').length < document.querySelectorAll('.input-filter').length
            ? (selectAll.checked = false)
            : (selectAll.checked = true);
          calendar.refetchEvents();
        });
      });
    }

    // Jump to date on sidebar(inline) calendar change
    if (inlineCalInstance && inlineCalInstance.config && inlineCalInstance.config.onChange) {
      inlineCalInstance.config.onChange.push(function (date) {
        calendar.changeView(calendar.view.type, moment(date[0]).format('YYYY-MM-DD'));
        modifyToggler();
        if (appCalendarSidebar) appCalendarSidebar.classList.remove('show');
        if (appOverlay) appOverlay.classList.remove('show');
      });
    }
  })();
});
