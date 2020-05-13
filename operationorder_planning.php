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

$title = $langs->trans("OperationOrderPlanning");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, $TIncludeJS, $TIncludeCSS);

// test user event création right
$fk_status = $conf->global->OPODER_STATUS_ON_PLANNED;
$statusAllowed = new OperationOrderStatus($db);
$res = $statusAllowed->fetch($fk_status);
$userCanCreateEvent = 0;
if($res>0 && $statusAllowed->userCan($user, 'changeToThisStatus')){
	$userCanCreateEvent = 1;
}

?>
    <script>

		operationOrderInterfaceUrl = "<?php print dol_buildpath('/operationorder/scripts/interface.php', 1); ?>?action=getPlannedOperationOrder";
		fullcalendarscheduler_initialLangCode = "<?php print !empty($conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG) ? $conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG : $langjs; ?>";
		fullcalendarscheduler_snapDuration = "<?php print !empty($conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION) ? $conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION : '00:15:00'; ?>";
		fullcalendarscheduler_minTime = "<?php print !empty($conf->global->FULLCALENDARSCHEDULER_MIN_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MIN_TIME : '00:00'; ?>";
		fullcalendarscheduler_maxTime = "<?php print !empty($conf->global->FULLCALENDARSCHEDULER_MAX_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MAX_TIME : '23:00'; ?>";

		fullcalendar_scheduler_businessHours_week_start = "<?php print (!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START : '08:00'); ?>";
		fullcalendar_scheduler_businessHours_week_end = "<?php print (!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END : '18:00'); ?>";

		fullcalendar_scheduler_businessHours_weekend_start = "<?php print (!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START : '10:00'); ?>";
		fullcalendar_scheduler_businessHours_weekend_end = "<?php print (!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END : '16:00'); ?>";

		userCanCreateEvent = <?php print $userCanCreateEvent; ?>;

		document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {

				plugins: [ 'interaction', 'dayGrid', 'timeGrid'], // , 'list', 'rrule'
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
				selectable: userCanCreateEvent,
				minTime: fullcalendarscheduler_minTime,
				maxTime: fullcalendarscheduler_maxTime,
				scrollTime: '10:00:00',
				height: 'auto',
				selectMirror: true,
				locale: fullcalendarscheduler_initialLangCode,
				eventLimit: true, // allow "more" link when too many events
                editable:true,
                businessHours: {
                    // days of week. an array of zero-based day of week integers (0=Sunday)
                    daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday

                    startTime: fullcalendar_scheduler_businessHours_week_start, // a start time (10am in this example)
                    endTime: fullcalendar_scheduler_businessHours_week_end, // an end time (6pm in this example)
                },
                eventConstraint:'businessHours',
                selectConstraint:'businessHours',

				eventRender: function(info) {

					$(info.el).attr('title', info.event.extendedProps.msg);
					$(info.el).attr('data-operationorderid', info.event.extendedProps.operationOrderId);
					$(info.el).attr('data-operationorderactionid', info.event.extendedProps.operationOrderActionId);

					$(info.el).tooltip({
						track: true,
						show: {
							collision: "flipfit",
							effect:'toggle',
							delay:50
						},
						hide: {
							delay: 0
						},
						container: "body",
						tooltipClass: "operationOrderTooltip",
						content: function () {
							return this.getAttribute("title");
						}
					});
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
					},
					{
						url: operationOrderInterfaceUrl,
						extraParams: {
							eventsType: 'dayOff'
						},
						failure: function() {
							//document.getElementById('script-warning').style.display = 'block'
						}
					}
				],
				loading: function(bool) {
					//document.getElementById('loading').style.display = bool ? 'block' : 'none';
				},
				eventClick: function(info) {
					// force open link into new url
					// info.jsEvent.preventDefault(); // don't let the browser navigate
					// if (info.event.url) {
					// 		window.open(info.event.url, "_blank");
					// 		return false;
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
                            operationorderneweventmodal.dialog("open");
							operationorderneweventmodal.dialog({height:'auto', width:'auto'}); // resize to content
                        }
                    });
                },
				dateClick: function(info) {
					//newEventModal(info.startStr);
				},
                eventResizeStop: function(info) {
				    $('.operationOrderTooltip').hide();
                },
                eventDrop: function(eventDropInfo) {
                    $('.operationOrderTooltip').hide(); // Parfois la tooltip ne se cache pas correctement
                    let endTms = Math.round((eventDropInfo.event._instance.range.end.getTime() + (eventDropInfo.event._instance.range.start.getTimezoneOffset() * 60000)) / 1000);
                    let startTms = Math.round((eventDropInfo.event._instance.range.start.getTime() + (eventDropInfo.event._instance.range.start.getTimezoneOffset() * 60000)) / 1000);
                    let fk_action = eventDropInfo.event.extendedProps.operationOrderActionId;

                    $.ajax({
                        url: '<?php echo dol_buildpath('/operationorder/scripts/interface.php', 1); ?>?action=updateOperationOrderAction',
                        method: 'POST',
                        data: {
                            'url' : window.location.href,
                            'data' : {
                                startTime: startTms,
                                endTime: endTms,
                                fk_action: fk_action,
                                allDay: eventDropInfo.event.allDay
                            }
                        },
                        dataType: 'json',
                        // La fonction à apeller si la requête aboutie
                        success: function (data) {
                            calendar.refetchEvents();
                        }
                    });

                }
            });

			// refresh event on modal close
			$("#dialog-add-event").on("hide.bs.modal", function (e) {
				calendar.refetchEvents();
			});

			calendar.render();

			// function newEventModal(start, end = 0){
			// 	// console.log(start);
			// 	// $("#dialog-add-event").html("title");
			// 	// $("#dialog-add-event").modal();
			// }

            //Définition de la boite de dialog "Créer un nouvel événement OR"
            var operationorderneweventmodal = $('#dialog-add-event');

			operationorderneweventmodal.dialog({
                autoOpen: false,
				autoResize:true,
				buttons: {
                    "<?php echo $langs->transnoentitiesnoconv('Create')?>": function() {
                        $('#dialog-add-event').find("form").submit();
                    }
                },
                close: function( event, ui ) {
                    calendar.refetchEvents();
                }
            });

			//Action ajax d'ajout d'un événement lors de la soumission du formulaire
            $(document).on("submit", "#create-operation-order-action", function(e) {

                e.preventDefault();

                var formData = {
                    'startTime' : $('input[name=startTime]').val(),
                    'endTime'   : $('input[name=endTime]').val(),
                    'allDay'    : $('input[name=allDay]').val(),
                    'operationorder' : $('select[name=operationorder]').val()
                };

                $.ajax({
                    url: '<?php echo dol_buildpath('/operationorder/scripts/interface.php', 1); ?>?action=createOperationOrderAction',
                    method: 'POST',
                    data: {
                        'url' : window.location.href,
                        'data' : formData
                    },
                    dataType: 'json',
                    // La fonction à apeller si la requête aboutie
                    success: function (data) {
                        operationorderneweventmodal.dialog('close');
                        calendar.refetchEvents();
                    }
                });

            });

        });
    </script>
<?php
print '<div id="calendar"></div>';
print '<div id="dialog-add-event" title="'.$langs->trans('CreateNewORAction').'"></div>';

llxFooter();

