<?php

require 'config.php';
dol_include_once('/operationorder/class/operationorder.class.php');

if(empty($user->rights->operationorder->planning->read)) accessforbidden();

$langs->loadLangs(array('operationorder@operationorder'));

$title = $langs->trans("OperationOrderPlanning");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';

list($langjs, $dummy) = explode('_', $langs->defaultlang);

if($langjs == 'en') $langjs = 'en-gb';

$TIncludeCSS = array(
    '/operationorder/vendor/fullcalendar-4.4.0/packages/core/main.css',
    '/operationorder/vendor/fullcalendar-4.4.0/packages/daygrid/main.css',
    '/operationorder/vendor/fullcalendar-4.4.0/packages/timegrid/main.css'
);

$TIncludeJS = array(
    '/operationorder/vendor/fullcalendar-4.4.0/packages/core/main.js',
    '/operationorder/vendor/fullcalendar-4.4.0/packages/core/locales-all.js',
    '/operationorder/vendor/fullcalendar-4.4.0/packages/daygrid/main.js',
    '/operationorder/vendor/fullcalendar-4.4.0/packages/interaction/main.js',
    '/operationorder/vendor/fullcalendar-4.4.0/packages/timegrid/main.js'
);
$langs->loadLangs(array('operationorder@operationorder'));

$title = $langs->trans("OperationOrderPlanning");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, $TIncludeJS, $TIncludeCSS);
?>
    <script>

        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {

                events: [
                    {
                        title: 'My Event',
                        start: '2020-05-07T08:30:00',
                        allDay: true
                    },
                    {
                        title: 'My Event 2',
                        start: '2020-05-08T15:30:00',
                        end: '2020-05-08T16:30:00',
                        allDay: false
                    }
                ],

                businessHours: {
                    // days of week. an array of zero-based day of week integers (0=Sunday)
                    daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday

                    startTime: '8:00', // a start time (10am in this example)
                    endTime: '18:00', // an end time (6pm in this example)
                },

                plugins: ['timeGrid', 'interaction'],
                defaultView: 'timeGridWeek',
                locale: '<?php echo $langjs; ?>',
                minTime: '05:00:00',
                maxTime: '21:00:00',
                scrollTime: '10:00:00',
                height: 'auto',
                editable: true,
                selectable: true,
                selectMirror: true,

                select: function (selectionInfo) {
                    let startTimestamp = Math.floor(selectionInfo.start.getTime()/1000);
                    let endTimestamp = Math.floor(selectionInfo.end.getTime()/1000);
                    $.ajax({
                        url: '<?php echo dol_buildpath('/operationorder/scripts/interface.php?action=getFormDialogPlanable', 1); ?>',
                        method: 'POST',
                        data: {
                            'url' : window.location.href,
                            'startTime' : startTimestamp,
                            'endTime' : endTimestamp,
                            'allDay' : selectionInfo.allDay
                        },
                        dataType: 'json',
                        // La fonction à apeller si la requête aboutie
                        success: function (data) {
                            $('#dialog-add-event').append(data.result);
                        }
                    });
                }
            });
            calendar.render();
        });
    </script>
<?php
print '<div id="calendar"></div>';
print '<div id="dialog-add-event"></div>';

llxFooter();

