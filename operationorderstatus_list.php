<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require 'config.php';
dol_include_once('operationorder/class/operationorderstatus.class.php');

if(empty($user->rights->operationorder->status->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('operationorder@operationorder');


$massaction = GETPOST('massaction', 'alpha');
$confirmmassaction = GETPOST('confirmmassaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$object = new OperationOrderStatus($db);

$hookmanager->initHooks(array('operationorderstatuslist'));

if ($object->isextrafieldmanaged)
{
    $extrafields = new ExtraFields($db);
    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
}

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (!empty($confirmmassaction) && $massaction != 'presend' && $massaction != 'confirm_presend')
{
	if($massaction == 'delete' && !empty($toselect)){
		foreach ($toselect as $deleteId){
			$objectToDelete = new OperationOrderStatus($db);
			$res = $objectToDelete->fetch($deleteId);
			if($res>0){
				if($objectToDelete->delete($user)<0)
				{
					setEventMessage($langs->trans('OperationOrderStatusDeleteError', $objectToDelete->ref), 'errors');
				}
			}
			else{
				setEventMessage($langs->trans('OperationOrderStatusNotFound'), 'warnings');
			}
		}

		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}

    $massaction = '';
}


if (empty($reshook))
{
	// do action from GETPOST ...
}


/*
 * View
 */

llxHeader('', $langs->trans('OperationOrderStatusList'), '', '');

//$type = GETPOST('type');
//if (empty($user->rights->operationorder->all->read)) $type = 'mine';

// TODO ajouter les champs de son objet que l'on souhaite afficher
$keys = array_keys($object->fields);
$fieldList = 't.'.implode(', t.', $keys);
if (!empty($object->isextrafieldmanaged))
{
    $keys = array_keys($extralabels);
	if(!empty($keys)) {
		$fieldList .= ', et.' . implode(', et.', $keys);
	}
}

$sql = 'SELECT '.$fieldList;

// Add fields from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= ' FROM '.MAIN_DB_PREFIX.$object->table_element.' t ';

if (!empty($object->isextrafieldmanaged))
{
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields et ON (et.fk_object = t.rowid)';
}

$newcardbutton = dolGetButtonTitle($langs->trans('NewStatus'), '', 'fa fa-plus-circle', dol_buildpath('operationorder/operationorderstatus_card.php?action=create&idmenu=540&mainmenu=operationorder', 1), '', $user->rights->operationorder->status->write);
print load_fiche_titre($langs->trans('OperationOrderStatusList'), $newcardbutton);


$Tlist = $object->fetchAll();
/**
 * @var $Tlist OperationOrderStatus[]
 */

print '<table id="operation-order-status-list" class="liste">';
print '<thead>';
print '<tr class="liste_titre">';
foreach ($object->fields as $field){

	if($field['visible'] == 1 || $field['visible'] == 2)
	{
		print '<th>';
		print $langs->trans($field['label']);
		print '</th>';
	}
}
print '<th></th>'; // for move plugin

print '</tr>';

print '</thead>';
print '<tbody>';

if(!empty($Tlist)){
	foreach ($Tlist as $oOStatus){
		print '<tr  data-lineid="'.$oOStatus->id.'">';
		foreach ($object->fields as $fieldKey => $field) {
			if($field['visible'] == 1 || $field['visible'] == 2) {
				print '<td>';
				if($fieldKey == 'code'){
					print '<a href="'.$oOStatus->getCardUrl().'" >'.$oOStatus->code.'</a>';
				}
				elseif($fieldKey == 'label'){
					print '<a href="'.$oOStatus->getCardUrl().'" >'.$oOStatus->label.'</a>';
				}
				else{
					print $oOStatus->showOutputFieldQuick($fieldKey);
				}
				print '</td>';
			}
		}
		print '<td class="linecolmove" ></td>';

		print '</tr>';
	}

	?>
	<script type="text/javascript">
		$(document).ready(function(){

			// target some elements
			var moveBlockCol= $('td.linecolmove');


			moveBlockCol.disableSelection(); // prevent selection

			// apply some graphical stuff
			moveBlockCol.css("background-image",'url(<?php echo dol_buildpath('theme/eldy/img/grip.png',2);  ?>)');
			moveBlockCol.css("background-repeat","no-repeat");
			moveBlockCol.css("background-position","center center");
			moveBlockCol.css("cursor","move");
			moveBlockCol.attr('title', '<?php echo html_entity_decode($langs->trans('MoveTitleBlock')); ?>');


			$( "#operation-order-status-list" ).sortable({
				cursor: "move",
				handle: ".linecolmove",
				items: 'tr:not(.liste_titre)',
				delay: 150, //Needed to prevent accidental drag when trying to select
				opacity: 0.8,
				axis: "y", // limit y axis
				placeholder: "ui-state-highlight",
				start: function( event, ui ) {
					//console.log('X:' + e.screenX, 'Y:' + e.screenY);
					//console.log(ui.item);
					var colCount = ui.item.children().length;
					ui.placeholder.html('<td colspan="'+colCount+'">&nbsp;</td>');

				},
				update: function (event, ui) {

					var TRowOrder = $(this).sortable('toArray', { attribute: 'data-lineid' });

					// POST to server using $.post or $.ajax
					$.ajax({
						data: {
							action: 'statusRank',
							TRowOrder: TRowOrder
						},
						type: 'POST',
						url: '<?php echo dol_buildpath('/operationorder/scripts/interface.php', 1) ; ?>',
						success: function(data) {
							console.log(data);
						},
					});
				}
			});

		});
	</script>
	<style type="text/css" >

		tr.ui-state-highlight td{
			border: 1px solid #dad55e;
			background: #fffa90;
			color: #777620;
		}
	</style>
	<?php
}

print '</tbody>';
print '</table>';


llxFooter('');
$db->close();

