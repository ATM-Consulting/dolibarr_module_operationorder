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
$entity = GETPOSTISSET('entity') ? GETPOST('entity', 'int') : $conf->entity;

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

	foreach ($TSchedules as $id_user => $data)
	{
		// planning journalier débute après l'heure début affichée
		if ($planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heuredam"} > $minHour)
		{
			$tempTT = new stdClass;
			$tempTT->start = $minHour;
			$tempTT->end = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heuredam"};
			$tempTT->text = "indispo";
			$tempTT->data = new stdClass;
			$tempTT->data->title = "plage non-travaillé";
			$tempTT->data->style = 'background-color:#d7d7d7;color:black;';

			$TSchedules[$id_user]->schedule[] = $tempTT;
		}

		if ($planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefam"} < $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heuredpm"})
		{
			$tempTT = new stdClass;
			$tempTT->start = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefam"};
			$tempTT->end = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heuredpm"};
			$tempTT->text = "indispo";
			$tempTT->data = new stdClass;
			$tempTT->data->title = "plage non-travaillé";
			$tempTT->data->style = 'background-color:#d7d7d7;color:black;';

			$TSchedules[$id_user]->schedule[] = $tempTT;
		}

		if ($planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefpm"} < $maxHour)
		{
			$tempTT = new stdClass;
			$tempTT->start = $planningUser[$id_user]->{$joursDeLaSemaine[$dow]."_heurefpm"};
			$tempTT->end = $maxHour;
			$tempTT->text = "indispo";
			$tempTT->data = new stdClass;
			$tempTT->data->title = "plage non-travaillé";
			$tempTT->data->style = 'background-color:#d7d7d7;color:black;';

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
				},
				onClick: function(node, data){ // quand on clique sur un événement
					// addLog('onClick', data);
					console.log(node, data); // pour plus tard redirection vers la card de l'OR si possible
				},
				onScheduleClick: function(node, data){ // quand on clique sur un endroit vide
					console.log(node, data);
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
}
else
{
	print $langs->trans('ErrorNoUserInGroupOrNoGroup');
}
llxFooter();

