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
    '/operationorder/css/dailySchedule.css'
);

$TIncludeJS = array(
    '/operationorder/vendor/dailySchedule/js/jq.schedule.min.js'
);

$langs->loadLangs(array('operationorder@operationorder'));

$hookmanager->initHooks(array('operationorderORplanning'));

$action = GETPOST('action');
$date = GETPOSTISSET('date') ? strtotime(GETPOST('dateyear').'-'.GETPOST('datemonth').'-'.GETPOST('dateday')) : dol_now();
$entity = GETPOSTISSET('entity') ? GETPOST('entity', 'int') : $conf->entity;

$title = $langs->trans("OperationOrderPlanning");
//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, $TIncludeJS, $TIncludeCSS);

$TSchedules = getCountersForPlanning($date, $entity);
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
					print "<td>".$form->selectarray("entity", $TEntity, $selected)."</td>";
					print "</tr>";
				}
			} ?>
		</table>
		<div class="tabsAction">
			<input type="submit" value="<?php echo $langs->trans('ToFilter'); ?>" class="butAction">
		</div>
	</form>

<?php print load_fiche_titre($langs->trans("Planning"), '', 'calendar'); ?>
	<div id="schedule"></div>

	<script type="text/javascript">
		// function addLog(type, message){
		// 	var $log = $('<tr />');
		// 	$log.append($('<th />').text(type));
		// 	$log.append($('<td />').text(message ? JSON.stringify(message) : ''));
		// 	$("#logs table").prepend($log);
		// }
		$(function(){
			// $("#logs").append('<table class="table">');
			var isDraggable = false;
			var isResizable = false;
			var $sc = $("#schedule").timeSchedule({
				startTime: "07:00", // schedule start time(HH:ii)
				endTime: "20:00",   // schedule end time(HH:ii)
				widthTime: 60 * 10,  // cell timestamp example 10 minutes
				timeLineY: 60,       // height(px)
				verticalScrollbar: 20,   // scrollbar (px)
				timeLineBorder: 2,   // border(top and bottom)
				bundleMoveWidth: 6,  // width to move all schedules to the right of the clicked time line cell
				draggable: isDraggable,
				resizable: isResizable,
				rows : <?php print json_encode($TSchedules) ?>/*{
					'0' : {
						title : 'Title Area1',
						schedule:[
							{
								start: '09:00',
								end: '10:00',
								text: 'Text Area',
								data: {
									"title":"lalala"
								}
							},
							{
								start: '10:00',
								end: '13:00',
								text: 'Text Area',
								data: {
								}
							}
						]
					},
					'1' : {
						title : 'Title Area2',
						schedule:[
							{
								start: '16:00',
								end: '17:00',
								text: 'Text Area',
								data: {
								}
							}
						]
					}
				}*/,
				onChange: function(node, data){
					// addLog('onChange', data);
				},
				onInitRow: function(node, data){
					// addLog('onInitRow', data);
				},
				onClick: function(node, data){
					// addLog('onClick', data);
				},
				onAppendRow: function(node, data){
					// addLog('onAppendRow', data);
				},
				onAppendSchedule: function(node, data){
					// addLog('onAppendSchedule', data);
					console.log(data)
					if(data.data.class){
						node.addClass(data.data.class);
					}
					if(data.data.image){
						var $img = $('<div class="photo"><img></div>');
						$img.find('img').attr('src', data.data.image);
						node.prepend($img);
						node.addClass('sc_bar_photo');
					}
					if(data.data.title){
						node.attr('title', data.data.title);
						node.tooltip();
					}
				},
			});
			$('#event_timelineData').on('click', function(){
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
			});
		});
	</script>

<?php

llxFooter();

