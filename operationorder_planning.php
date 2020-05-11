<?php

require 'config.php';
dol_include_once('/operationorder/class/operationorder.class.php');
dol_include_once('/operationorder/class/operationorderaction.class.php');


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

$action = GETPOST('action');
$id_operationorder = GETPOST('operationorder');
$startTime = GETPOST('startTime');
$endTime = GETPOST('endTime');
$allDay = GETPOST('allDay');

if($action == 'create-event'){

    global $user;

    $error = 0;

    if(!empty($id_operationorder)){

        $action_or = new OperationOrderAction($db);

        $action_or->dated = $startTime;
        $action_or->datef = $endTime;
        $action_or->fk_operationorder = $id_operationorder;
        $action_or->fk_user_author = $user->id;

        $res = $action_or->save($user);

        $operationorder = new OperationOrder($db);
        $res = $operationorder->fetch($id_operationorder);

        if($res)
        {
			$fk_status = $conf->global->OPODER_STATUS_ON_PLANNED;

			$statusAllowed = new OperationOrderStatus($db);
			$res = $statusAllowed->fetch($fk_status);
			if($res>0 && $statusAllowed->userCan($user, 'changeToThisStatus')){
				$res = $operationorder->setStatus($user, $fk_status);
				if ($res < 0) $error++;
			}else{
				//setEventMessage($langs->trans('ConfirmSetStatusNotAllowed'), 'errors');
			}
        } else {
            $error ++;
        }
    }
}

$title = $langs->trans("OperationOrderPlanning");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, $TIncludeJS, $TIncludeCSS);
?>
    <script>

		operationOrderInterfaceUrl = "<?php print dol_buildpath('/operationorder/scripts/interface.php', 1); ?>?action=getPlannedOperationOrder";
		fullcalendarscheduler_initialLangCode = "<?php print (!empty($conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG) ? $conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG : $langjs); ?>";
		fullcalendarscheduler_snapDuration = "<?php (!empty($conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION) ? $conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION : '00:15:00'); ?>";
		fullcalendarscheduler_aspectRatio = "<?php (!empty($conf->global->FULLCALENDARSCHEDULER_ASPECT_RATIO) ? $conf->global->FULLCALENDARSCHEDULER_ASPECT_RATIO : '1.6'); ?>";
		fullcalendarscheduler_minTime = "<?php (!empty($conf->global->FULLCALENDARSCHEDULER_MIN_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MIN_TIME : '00:00'); ?>";
		fullcalendarscheduler_maxTime = "<?php (!empty($conf->global->FULLCALENDARSCHEDULER_MAX_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MAX_TIME : '23:00'); ?>";


		fullcalendar_scheduler_businessHours_week_start = "<?php (!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START : '08:00'); ?>";
		fullcalendar_scheduler_businessHours_week_end = "<?php (!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END : '18:00'); ?>";

		fullcalendar_scheduler_businessHours_weekend_start = "<?php (!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START : '10:00'); ?>";
		fullcalendar_scheduler_businessHours_weekend_end = "<?php (!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END : '16:00'); ?>";


		document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {

				plugins: [ 'interaction', 'dayGrid', 'timeGrid', 'list', 'rrule' ],
				defaultDate: '<?php print date('Y-m-d'); ?>',
				defaultView: 'timeGridWeek',
				snapDuration: fullcalendarscheduler_snapDuration,
				weekNumbers: true,
				weekNumbersWithinDays: true,
				weekNumberCalculation: 'ISO',
				header: {
					left: 'prev,next today',
					center: 'title',
					right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
				},
				editable: false, // next step add rights and allow edition
				selectable: true,
				minTime: '05:00:00',
				maxTime: '21:00:00',
				scrollTime: '10:00:00',
				height: 'auto',
				selectMirror: true,
				locale: fullcalendarscheduler_initialLangCode,
				eventLimit: true, // allow "more" link when too many events

                businessHours: {
                    // days of week. an array of zero-based day of week integers (0=Sunday)
                    daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday

                    startTime: '8:00', // a start time (10am in this example)
                    endTime: '18:00', // an end time (6pm in this example)
                },

				eventRender: function(info) {

					// $(info.el).popover('destroy');

					// $(info.el).popover({
					// 	title: info.event.title ,
					// 	content: info.event.extendedProps.msg,
					// 	html: true,
					// 	trigger: "hover"
					// });

				},
				eventSources: [
					{
						url: operationOrderInterfaceUrl,
						extraParams: {
							eventsType: 'orPlanned'
						},
						failure: function() {
							//document.getElementById('script-warning').style.display = 'block'
						}
					}
					// another source
					// ,{
					// 	url: operationOrderInterfaceUrl,
					// 	extraParams: {
					// 		eventsType: 'notAvailableRange'
					// 	},
					// 	failure: function() {
					// 		//document.getElementById('script-warning').style.display = 'block'
					// 	}
					// }
				],
				loading: function(bool) {
					//document.getElementById('loading').style.display = bool ? 'block' : 'none';
				},
				eventClick: function(info) {

					info.jsEvent.preventDefault(); // don't let the browser navigate
					//console.log ( info.event.extendedProps.session_formateur_calendrier );
					//console.log ( info.event );
					//
					// if (info.event.url.length > 0){
					//
					// 	$("#calendarModalLabel").html(info.event.title);
					// 	$("#calendarModalIframe").attr("src",info.event.url + "&iframe=1");
					//
					// 	$("#calendarModal").modal();
					//
					// 	// Deactivate original link
					// 	return false;
					// }
				},
                select: function (selectionInfo) {
                    let startTimestamp = Math.floor(selectionInfo.start.getTime()/1000);
                    let endTimestamp = Math.floor(selectionInfo.end.getTime()/1000);
                    $.ajax({
                        url: '<?php echo dol_buildpath('/operationorder/scripts/interface.php', 1); ?>?action=getFormDialogPlanable',
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
                            $('#dialog-add-event').html(data.result);

                            $('#dialog-add-event').dialog({
                                buttons: {
                                    "Create": function() {
                                        $('#dialog-add-event').find("form").submit();
                                    }
                                },
                                close: function( event, ui ) {
                                    // calendar.refetchEvents();
                                }
                            });
                        }
                    });

					// newEventModal(info.startStr, info.endStr);
                },
				dateClick: function(info) {
					//newEventModal(info.startStr);
				},
            });

			// refresh event on modal close
			$("#dialog-add-event").on("hide.bs.modal", function (e) {
				calendar.refetchEvents();
			});

			calendar.render();



			function newEventModal(start, end = 0){
				// console.log(start);
				// $("#dialog-add-event").html("title");
				// $("#dialog-add-event").modal();
			}
        });
    </script>
<?php
print '<div id="calendar"></div>';
print '<div id="dialog-add-event"></div>';

llxFooter();

