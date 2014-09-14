$(function () {

    var date = new Date();
    var d = date.getDate();
    var m = date.getMonth();
    var y = date.getFullYear();

    $('#full-calendar').fullCalendar({
        header: {
            left: 'prev,next',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        editable: true,
        droppable: true,
        drop: function (date, allDay) { // this function is called when something is dropped

            // retrieve the dropped element's stored Event Object
            var originalEventObject = $(this).data('eventObject');

            // we need to copy it, so that multiple events don't have a reference to the same object
            var copiedEventObject = $.extend({}, originalEventObject);

            // assign it the date that was reported
            copiedEventObject.start = date;
            copiedEventObject.allDay = allDay;
            copiedEventObject.className = $(this).attr("data-category");

            // render the event on the calendar
            // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
            $('#full-calendar').fullCalendar('renderEvent', copiedEventObject, true);

            // is the "remove after drop" checkbox checked?
            //if ($('#drop-remove').is(':checked')) {
            // if so, remove the element from the "Draggable Events" list
            $(this).remove();
            //}

        },

        events: [
            {
                title: 'WP 1010 Introduction to Academic Writing - James Smith',
                start: new Date(y, m, d, 9, 30),
                end: new Date(y, m, d, 10, 0),
                allDay: false,
                className: 'fc-yellow'
            },
            {
                title: 'WP 1111 Academic Writing - Lilly Potter',
                start: new Date(y, m, d, 9, 30),
                end: new Date(y, m, d, 10, 30),
                allDay: false,
                className: 'fc-grey'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d, 11, 30),
                end: new Date(y, m, d, 12, 0),
                allDay: false,
                className: 'fc-grey'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d, 13, 30),
                end: new Date(y, m, d, 15, 0),
                allDay: false,
                className: 'fc-red'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d, 11, 30),
                end: new Date(y, m, d, 12, 0),
                allDay: false,
                className: 'fc-charcoal'
            },
            {
                title: 'WP 1010 Introduction to Academic Writing - James Smith',
                start: new Date(y, m, d, 10, 30),
                end: new Date(y, m, d, 11, 30),
                allDay: false,
                className: 'fc-yellow'
            },
            {
                title: 'WP 1111 Academic Writing - Lilly Potter',
                start: new Date(y, m, d, 12, 30),
                end: new Date(y, m, d, 13, 30),
                allDay: false,
                className: 'fc-grey'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d, 11, 30),
                end: new Date(y, m, d, 12, 0),
                allDay: false,
                className: 'fc-grey'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d, 13, 0),
                end: new Date(y, m, d, 15, 30),
                allDay: false,
                className: 'fc-red'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d, 11, 30),
                end: new Date(y, m, d, 12, 0),
                allDay: false,
                className: 'fc-charcoal'
            },

            {
                title: 'WP 1010 Introduction to Academic Writing - James Smith',
                start: new Date(y, m, d+1, 9, 30),
                end: new Date(y, m, d+1, 10, 0),
                allDay: false,
                className: 'fc-yellow'
            },
            {
                title: 'WP 1111 Academic Writing - Lilly Potter',
                start: new Date(y, m, d+1, 9, 30),
                end: new Date(y, m, d+1, 10, 30),
                allDay: false,
                className: 'fc-grey'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d+1, 11, 30),
                end: new Date(y, m, d+1, 12, 0),
                allDay: false,
                className: 'fc-grey'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d+1, 13, 30),
                end: new Date(y, m, d+1, 15, 0),
                allDay: false,
                className: 'fc-red'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d+1, 11, 30),
                end: new Date(y, m, d+1, 12, 0),
                allDay: false,
                className: 'fc-charcoal'
            },
            {
                title: 'WP 1010 Introduction to Academic Writing - James Smith',
                start: new Date(y, m, d+1, 10, 30),
                end: new Date(y, m, d+1, 11, 30),
                allDay: false,
                className: 'fc-yellow'
            },
            {
                title: 'WP 1111 Academic Writing - Lilly Potter',
                start: new Date(y, m, d+1, 12, 30),
                end: new Date(y, m, d+1, 13, 30),
                allDay: false,
                className: 'fc-grey'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d+1, 11, 30),
                end: new Date(y, m, d+1, 12, 0),
                allDay: false,
                className: 'fc-grey'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d+1, 13, 0),
                end: new Date(y, m, d+1, 15, 30),
                allDay: false,
                className: 'fc-red'
            },
            {
                title: 'WP 1212 Academic Writing and Research - Emma Brown',
                start: new Date(y, m, d+1, 11, 30),
                end: new Date(y, m, d+1, 12, 0),
                allDay: false,
                className: 'fc-charcoal'
            }
        ]
    });


    $('#external-events div.external-event').each(function () {

        // create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
        // it doesn't need to have a start or end
        var eventObject = {
            title: $.trim($(this).text()) // use the element's text as the event title
        };

        // store the Event Object in the DOM element so we can get to it later
        $(this).data('eventObject', eventObject);

        // make the event draggable using jQuery UI
        $(this).draggable({
            zIndex: 999,
            revert: true,      // will cause the event to go back to its
            revertDuration: 0  //  original position after the drag
        });

    });


    var addEvent = function (title, category) {
        title = title.length == 0 ? "Untitled Event" : title;
        category = category.length == 0 ? 'fc-secondary' : category;
        var html = $('<div data-category="' + category + '" class="external-event ui-draggable label ' + category + '">' + title + '</div>');
        jQuery('#event_box').append(html);
        initDrag(html);
    }

    $('#event-form').unbind('submit').submit(function (e) {
        e.preventDefault();
        var title = $('#event_title');
        var category = $('#event_category');
        addEvent(title.val(), category.val());
        title.val('');
    });

    var initDrag = function (el) {
        // create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
        // it doesn't need to have a start or end
        var eventObject = {
            title: $.trim(el.text()) // use the element's text as the event title
        };
        // store the Event Object in the DOM element so we can get to it later
        el.data('eventObject', eventObject);
        // make the event draggable using jQuery UI
        el.draggable({
            zIndex: 999,
            revert: true, // will cause the event to go back to its
            revertDuration: 0 //  original position after the drag
        });
    }

});