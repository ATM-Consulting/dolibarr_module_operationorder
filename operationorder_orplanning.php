<?php

require 'config.php';
dol_include_once('/operationorder/class/operationorder.class.php');
dol_include_once('/operationorder/class/operationorderaction.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";



if(empty($user->rights->operationorder->planning->read)) accessforbidden();

$langs->loadLangs(array('operationorder@operationorder'));

$title = $langs->trans("OperationOrderPlanning");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';

list($langjs, $dummy) = explode('_', $langs->defaultlang);

if($langjs == 'en') $langjs = 'en-gb';

$TIncludeCSS = array(
    '/operationorder/vendor/dailySchedule/css/style.min.css',
    '/operationorder/css/dailySchedule.css.php'
);

$TIncludeJS = array(
    '/operationorder/vendor/dailySchedule/js/jq.schedule.min.js'
);

$langs->loadLangs(array('operationorder@operationorder'));

$hookmanager->initHooks(array('operationorderORplanning'));

$action = GETPOST('action');
$date = GETPOSTISSET('date') ? strtotime(GETPOST('dateyear').'-'.GETPOST('datemonth').'-'.GETPOST('dateday')) : dol_now();
$entity = GETPOST('entity', 'int') ? GETPOST('entity', 'int') : $conf->entity;

$title = $langs->trans("LeftMenuOperationOrderORPlanning");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, $TIncludeJS, $TIncludeCSS);

$TSchedules = initSchedule($entity);

/*------------------- Heures d'ouvertures dynamiques -----------------*/
$oldEntity = $conf->entity;
$conf->entity = $entity;
$conf->setValues($db);

$dow = date("N", $date);
$date_lundi = strtotime("-".($dow -1)."days ", $date);
$date_dimanche = strtotime("+".(7-$dow)."days ", $date);

$DailyPlanning = getOperationOrderUserPlanningSchedule($date_lundi, $date_dimanche);
$fk_groupuser = $conf->global->OPERATION_ORDER_GROUPUSER_DEFAULTPLANNING;
$planningUser = getOperationOrderTUserPlanningFromGroup($fk_groupuser);

$minHour = "07:00";
$maxHour = "20:00";
if (isset($DailyPlanning[$date]))
{
	if ($DailyPlanning[$date][0]['min'] != '00:00')
	{
		$tmpHour = explode(':', $DailyPlanning[$date][0]['min']);
		$minHour = $tmpHour[0].':00';
	}

	if ($DailyPlanning[$date][1]['max'] != '00:00')
	{
		$tmpHour = explode(':', $DailyPlanning[$date][1]['max']);
		$maxHour = ($tmpHour[0]+1).':00';
	}
}

$conf->entity = $oldEntity;
$conf->setValues($db);

/*------------------- END Heures d'ouvertures dynamiques -----------------*/

// affichages des plages d'indispos
if (!empty($TSchedules))
{
	$joursDeLaSemaine = array(1 => "lundi", 2 => "mardi", 3 => "mercredi", 4 => "jeudi", 5 => "vendredi", 6 => "samedi", 7 => "dimanche");

	foreach ($TSchedules as $id_user => &$data)
	{
		if (empty($planningUser[$id_user]))
		{
			$data->title = img_warning($langs->trans('errNoPlanning')) . $data->title;
			continue;
		}

		$date_min = strtotime(date("Y-m-d ".$minHour.":00", $date));
		$date_max = strtotime(date("Y-m-d ".$maxHour.":00", $date));
		$dated_matin = strtotime(date("Y-m-d ".$planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heuredam"}.":00", $date));
		$datef_matin = strtotime(date("Y-m-d ".$planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefam"}.":00", $date));
		$dated_aprem = strtotime(date("Y-m-d ".$planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heuredpm"}.":00", $date));
		$datef_aprem = strtotime(date("Y-m-d ".$planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefpm"}.":00", $date));

		// planning journalier débute après l'heure début affichée
		if ($dated_matin > $date_min)
		{
			$tempTT = new stdClass;
			$tempTT->userid = $id_user;
			$tempTT->start = $minHour;
			$tempTT->end = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heuredam"};
			$tempTT->text = "indispo";
			$tempTT->data = new stdClass;
			$tempTT->data->title = "plage non-travaillé";
			$tempTT->data->style = 'background-color:#d7d7d7;color:black;';
			$tempTT->data->fk_user = $id_user;

			$TSchedules[$id_user]->schedule[] = $tempTT;
		}

		if ($datef_matin < $dated_aprem)
		{

			$tempTT = new stdClass;
			$tempTT->start = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefam"};
			$tempTT->end = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heuredpm"};
			$tempTT->text = "indispo";
			$tempTT->data = new stdClass;
			$tempTT->data->title = "plage non-travaillé";
			$tempTT->data->style = 'background-color:#d7d7d7;color:black;';
			$tempTT->data->fk_user = $id_user;

			$TSchedules[$id_user]->schedule[] = $tempTT;
		}
		else // fin de matinée > début aprem = ne travaille pas l'aprem...
		{
			$tempTT = new stdClass;
			$tempTT->start = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefam"};
			$tempTT->end = $maxHour;
			$tempTT->text = "indispo";
			$tempTT->data = new stdClass;
			$tempTT->data->title = "plage non-travaillé";
			$tempTT->data->style = 'background-color:#d7d7d7;color:black;';
			$tempTT->data->fk_user = $id_user;

			$TSchedules[$id_user]->schedule[] = $tempTT;
			continue;
		}

		if ($datef_aprem < $date_max)
		{
			$tempTT = new stdClass;
			$tempTT->start = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefpm"};
			$tempTT->end = $maxHour;
			$tempTT->text = "indispo";
			$tempTT->data = new stdClass;
			$tempTT->data->title = "plage non-travaillé";
			$tempTT->data->style = 'background-color:#d7d7d7;color:black;';
			$tempTT->data->fk_user = $id_user;

			$TSchedules[$id_user]->schedule[] = $tempTT;
		}

	}
}

$TSchedules = getCountersForPlanning($TSchedules, $date, $entity);


// Hook d'ajout d'événements supplémentaires
$parameters = array(
	'date' 			=> $date,
	'entity' 		=> $entity,
	'minHour'		=> $minHour,
	'maxHour'		=> $maxHour,
	'fk_groupuser' 	=> $fk_groupuser,
	'planningUser'	=> $planningUser,
	'TSchedules' 	=> $TSchedules
);

$reshook = $hookmanager->executeHooks('oOrderORPlanningAddMoreSchedules', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
else if ($reshook > 1) $TSchedules = $hookmanager->resArray;

//print '<pre>'; print_r($TSchedules); print '</pre>';

$form = new Form($db);

print load_fiche_titre($langs->trans("Filter").'s', '', 'search');
?>

	<form name="filters" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<table width="100%">
			<tr>
				<td>Date</td>
				<td><?php print $form->selectDate($date, 'date'); ?></td>
			</tr>

			<?php if ($conf->multicompany->enabled) {
				$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."entity WHERE active = 1";
				if ($conf->entity != 1) $sql.= " AND rowid = ".$conf->entity;

				$resql = $db->query($sql);
				if ($resql && $db->num_rows($resql))
				{
					$TEntity = array();
					while ($obj = $db->fetch_object($resql))
					{
						$TEntity[$obj->rowid] = $obj->label;
					}

					$selected = (empty($entity)) ? $conf->entity : $entity;

					print "<tr>";
					print "<td>".$langs->trans('Entity')."</td>";
					print "<td>".(($conf->entity == 1) ? $form->selectarray("entity", $TEntity, $selected) : $TEntity[$conf->entity])."</td>";
					print "</tr>";
				}
			} ?>
		</table>
		<div class="tabsAction">
			<input type="submit" value="<?php echo $langs->trans('ToFilter'); ?>" class="butAction">
		</div>
	</form>

<?php
if (!empty($TSchedules))
{
	print load_fiche_titre($langs->trans("Planning"), '', 'calendar');

?>
	<div id="schedule"></div>
<!--	<div id="logs"></div>-->

	<script type="text/javascript">
		// function addLog(type, message){
		// 	var $log = $('<tr />');
		// 	$log.append($('<th />').text(type));
		// 	$log.append($('<td />').text(message ? JSON.stringify(message) : ''));
		// 	$("#logs table").prepend($log);
		// }

		/* plugin jquery https://ateliee.github.io/jquery.schedule/demo/
		* trouvé ici : https://www.jqueryscript.net/time-clock/Simple-Daily-Schedule-Plugin-with-jQuery-and-jQuery-UI-Schedule.html */

		$(function(){
			// $("#logs").append('<table class="table">');
			var isDraggable = false;
			var isResizable = false;
			var popin = $("#schedulePopin");
			popin.dialog({
				autoOpen: false,
				autoResize:true,
				close: function( event, ui ) {
					// $('form[name="filters"]').submit();
				}
			});

			var $sc = $("#schedule").timeSchedule({
				startTime: "<?php echo $minHour; ?>", // schedule start time(HH:ii)
				endTime: "<?php echo $maxHour; ?>",   // schedule end time(HH:ii)
				widthTime: 60 * 5,  // cell timestamp example 10 minutes
				widthTimeX: 10,
				timeLineY: 60,       // height(px)
				verticalScrollbar: 20,   // scrollbar (px)
				timeLineBorder: 2,   // border(top and bottom)
				bundleMoveWidth: 6,  // width to move all schedules to the right of the clicked time line cell
				draggable: isDraggable,
				resizable: isResizable,
				rows : <?php print json_encode($TSchedules) ?>,
				onChange: function(node, data){
					// addLog('onChange', data);
				},
				onInitRow: function(node, data){
					// addLog('onInitRow', data);
					// console.log("init", node, data);
					node.attr('data-userId', data.userId);
				},
				onClick: function(node, data){ // quand on clique sur un événement
					// addLog('onClick', data);
					// console.log(data);
					// console.log($sc.timeSchedule('scheduleData'));
					let counterID 	= data.data.counterID;
					let fk_ordet 	= data.data.fk_orDet;
					let fk_or		= data.data.fk_or;
					let fk_user		= data.data.fk_user;

					// récupération des emplois du temps par user
					let schedulesByUser = _getScheduleByUser();

					if (counterID)
					{
						// limites horaires à respecter
						let hourRange = _getHoursLimitRange(schedulesByUser[fk_user].schedule, data.startTime, data.endTime);
						let minHour = hourRange.min;
						let maxHour = hourRange.max;

						$.ajax({
							url: '<?php echo dol_buildpath('/operationorder/scripts/interface.php', 1); ?>?action=getScheduleInfos',
							method: 'POST',
							data: {
								scheduleId: counterID,
								det: fk_ordet,
								oOrder: fk_or,
								minHour: minHour,
								maxHour: maxHour
							},
							dataType: 'json',
							// La fonction à apeller si la requête aboutie
							success: function (response) {
								// console.log(response);
								popin.html(response.result);
								popin.dialog("open");
								popin.dialog({height: 'auto', width: 'auto'}); // resize to content
								popin.parent().css({"top":"20%", "min-height":"150px", "min-width":"200px"});
							}
						});
					}

				},
				onScheduleClick: function(node, data){ // quand on clique sur un endroit vide
					// console.log(node, data);

					// récupération de l'id user au premier clic sur un espace vide
					if ($(node).parent().data('userid') == undefined)
					{
						var scroll = $('.sc_data_scroll .timeline');
						var main = $(".sc_main .timeline");
						// console.log(scroll);
						scroll.each(function (index, el) {
							$(main[index]).data('userid', $(el).data('userid'))
						});
						// console.log(main);
					}

					let userid = $(node).parent().data('userid');
					if (userid != undefined)
					{
						let schedulesByUser = _getScheduleByUser();

						let start = calcStringTime(data);
						let end = start + 600;

						// limites horaires à respecter
						let hourRange = _getHoursLimitRange(schedulesByUser[userid].schedule, start, end);
						let minHour = hourRange.min;
						let maxHour = hourRange.max;
						let entity = $('#entity').val();
						let date = $('#date').val();

						// appel pour aller chercher le formulaire à foutre dans la popin
						$.ajax({
							url: '<?php echo dol_buildpath('/operationorder/scripts/interface.php', 1); ?>?action=getCreateScheduleForm',
							method: 'POST',
							data: {
								userid: userid,
								date: date,
								minHour: minHour,
								maxHour: maxHour,
								selectedHour: data,
								entity: entity
							},
							dataType: 'json',
							// La fonction à apeller si la requête aboutie
							success: function (response) {
								// console.log(response);
								popin.html(response.result);
								popin.dialog("open");
								popin.dialog({height: 'auto', width: 'auto'}); // resize to content
								popin.parent().css({"top":"20%", "min-height":"150px", "min-width":"400px"});
							}
						});
						// création de la popin
						// input liste des OR affiché sur la planification
						// input lignes de l'OR séléctionné
						// heure début / heure fin avec heure de début prérempli par la donné dans la variable "data"

						// retour d'appel => afficher la popin

						// à la fermeture de la popin reload la page
					}
					else console.log("userid not found on node")



				},
				onAppendRow: function(node, data){
					// addLog('onAppendRow', data);
				},
				onAppendSchedule: function(node, data){
					// addLog('onAppendSchedule', data);
					// console.log(data)
					if(data.data.class){
						node.addClass(data.data.class);
					}
					if(data.data.style){
						node.attr('style', node.attr('style')+data.data.style);
					}
					if(data.data.image){
						var $img = $('<div class="photo"><img></div>');
						$img.find('img').attr('src', data.data.image);
						node.prepend($img);
						node.addClass('sc_bar_photo');
					}
					if(data.data.title){
						node.attr('title', data.data.title);
						node.tooltip({
							track: true,
							show: {
								collision: "flipfit",
								effect: 'toggle',
								delay: 50
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
					}
				},
			});

			function _getScheduleByUser()
			{
				let scheduleData = $sc.timeSchedule('scheduleData').slice();
				let schedulesByUser = $sc.timeSchedule('timelineData').slice();

				oldTL = 0;
				while (scheduleData.length)
				{
					if (scheduleData[0].data.fk_user != undefined)
					{
						schedulesByUser[scheduleData[0].data.fk_user].schedule.push(scheduleData.shift())
					}
					else scheduleData.shift();
				}

				return schedulesByUser;
			}

			function _getHoursLimitRange(schedules = [], start, end)
			{
				var minHour = '';
				var minDateTime = 0;
				var maxHour = '';
				var maxDateTime = 86400;

				if (schedules.length > 0)
				{
					for (i in schedules)
					{
						let temptt = schedules[i];

						if (temptt.endTime <= start && temptt.endTime > minDateTime)
						{
							minHour = temptt.end;
							minDateTime = temptt.endTime;
						}
						if (temptt.startTime >= end && temptt.startTime < maxDateTime)
						{
							maxHour = temptt.start;
							maxDateTime = temptt.startTime;
						}
					}
				}

				if (minHour == '') minHour = '<?php echo $minHour; ?>';
				if (maxHour == '') maxHour = '<?php echo $maxHour; ?>';

				return {min:minHour, max:maxHour};
			}

			function calcStringTime(str) {
				var slice = str.split(':');
				var h = Number(slice[0]) * 60 * 60;
				var i = Number(slice[1]) * 60;
				return h + i;
			}

			// je garde ces trucs venant de la doc de base pour infos si on a besoin d'interair avec le schedule
			/*$('#event_timelineData').on('click', function(){
				// addLog('timelineData', $sc.timeSchedule('timelineData'));
			});
			$('#event_scheduleData').on('click', function(){
				// addLog('scheduleData', $sc.timeSchedule('scheduleData'));
			});
			$('#event_resetData').on('click', function(){
				$sc.timeSchedule('resetData');
				// addLog('resetData');
			});
			$('#event_resetRowData').on('click', function(){
				$sc.timeSchedule('resetRowData');
				// addLog('resetRowData');
			});
			$('#event_setDraggable').on('click', function(){
				isDraggable = !isDraggable;
				$sc.timeSchedule('setDraggable', isDraggable);
				// addLog('setDraggable', isDraggable ? 'enable' : 'disable');
			});
			$('#event_setResizable').on('click', function(){
				isResizable = !isResizable;
				$sc.timeSchedule('setResizable', isResizable);
				// addLog('setResizable', isResizable ? 'enable' : 'disable');
			});
			$('.ajax-data').on('click', function(){
				$.ajax({url: './data/'+$(this).attr('data-target')})
					.done( (data) => {
						// addLog('Ajax GetData', data);
						$sc.timeSchedule('setRows', data);
					});
			});*/
		});
	</script>

<?php
	print '<div id="schedulePopin" title="'.$langs->trans('UpdateTasktime').'"></div>';
}
else
{
	print $langs->trans('ErrorNoUserInGroupOrNoGroup');
}
llxFooter();

