<?php
/* Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('operationorder/class/operationorderstatus.class.php');
dol_include_once('operationorder/class/operationorder.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');

if(empty($user->rights->operationorder->status->read)) accessforbidden();

$langs->load('operationorder@operationorder');

$action = GETPOST('action');
$cancel = GETPOST('cancel');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');


$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'operationordercard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

$object = new Operationorderstatus($db);
$soc = new Societe($db);


$permissiondellink = $user->rights->operationorder->status->write;	// Used by the include of actions_dellink.inc.php


if (!empty($id) || !empty($ref)) {
	$object->fetch($id, true, $ref);
	$object->fetch_thirdparty();
}

// prepare TGroupCan
$TGroupCan = array();
foreach ($object->TGroupRightsType as $field){
	$TGroupCan[$field['code']] = GETPOST('TGroupCan_'.$field['code'],'array');
	$TGroupCan[$field['code']] = array_map('intval', $TGroupCan[$field['code']]);
}


// prepare $TStatusAllowed
$TStatusAllowed = GETPOST('TStatusAllowed','array');
$TStatusAllowed = array_map('intval', $TStatusAllowed);


$hookmanager->initHooks(array('operationordercard', 'globalcard'));


if ($object->isextrafieldmanaged)
{
	$extrafields = new ExtraFields($db);

	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
	$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
}


/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{

	if ($cancel)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action='';
	}

	// For object linked
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

//	// Actions to send emails
//	$trigger_name='OPERATIONORDER_SENTBYMAIL';
//	$autocopy='MAIN_MAIL_AUTOCOPY_OPERATIONORDER_TO';
//	$trackid='operationorder'.$object->id;
//	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


	$error = 0;
	switch ($action) {
		case 'add':
		case 'update':
			$object->edit = 0;
			$object->setValues($_REQUEST); // Set standard attributes
			$object->display_on_planning = GETPOST('display_on_planning', 'int'); // Lorsque la checkbox est décochée, le $_REQUEST ne contient pas l'élément ce qui fait la value n'est pas setté
			$object->clean_event = GETPOST('clean_event', 'int'); // Lorsque la checkbox est décochée, le $_REQUEST ne contient pas l'élément ce qui fait la value n'est pas setté
			$object->planable = GETPOST('planable', 'int'); // Lorsque la checkbox est décochée, le $_REQUEST ne contient pas l'élément ce qui fait la value n'est pas setté


			$object->TGroupCan = $TGroupCan;
			$object->TStatusAllowed =$TStatusAllowed;

			if ($object->isextrafieldmanaged)
			{
				$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
				if ($ret < 0) $error++;
			}

//			$object->date_other = dol_mktime(GETPOST('starthour'), GETPOST('startmin'), 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));

			// Check parameters
//			if (empty($object->date_other))
//			{
//				$error++;
//				setEventMessages($langs->trans('warning_date_must_be_fill'), array(), 'warnings');
//			}

			// ...

			if ($error > 0)
			{
				if (empty($object->id)) $action = 'create';
				else $action = 'edit';
				break;
			}

			$res = $object->save($user);

			if ($res <= 0)
			{
				setEventMessage($object->errors, 'errors');
				if (empty($object->id)) $action = 'create';
				else $action = 'edit';
			}
			else
			{
				header('Location: '.dol_buildpath('/operationorder/operationorderstatus_card.php', 1).'?id='.$object->id);
				exit;
			}
		case 'update_extras':

			$object->oldcopy = dol_clone($object);

			// Fill array 'array_options' with data from update form
			$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute', 'none'));
			if ($ret < 0) $error++;

			if (! $error)
			{
				$result = $object->insertExtraFields('OPERATIONORDER_MODIFY');
				if ($result < 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}

			if ($error) $action = 'edit_extras';
			else
			{
				header('Location: '.dol_buildpath('/operationorder/operationorderstatus_card.php', 1).'?id='.$object->id);
				exit;
			}
			break;
		case 'confirm_clone':
			$object->cloneObject($user);

			header('Location: '.dol_buildpath('/operationorder/operationorderstatus_card.php', 1).'?id='.$object->id);
			exit;

		case 'modif':
		case 'activate':
			if (!empty($user->rights->operationorder->status->write)) $object->setActive($user);

			break;
		case 'disable':
			if (!empty($user->rights->operationorder->status->write)) $object->setDisabled($user);

			break;
		case 'confirm_cancel':
			if (!empty($user->rights->operationorder->status->write)) $object->setCancel($user);

			header('Location: '.dol_buildpath('/operationorder/operationorderstatus_card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_delete':
			if (!empty($user->rights->operationorder->delete)) $object->delete($user);

			header('Location: '.dol_buildpath('/operationorder/operationorderstatus_list.php', 1));
			exit;

		// link from llx_element_element
		case 'dellink':
			$object->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/operationorder/operationorderstatus_card.php', 1).'?id='.$object->id);
			exit;

	}
}


/**
 * View
 */
$form = new Form($db);

$title=$langs->trans('Operationorderstatus');
llxHeader('', $title);

if ($action == 'create')
{
	print load_fiche_titre($langs->trans('NewOperationOrderStatus'), '', 'operationOrderStatus-title@operationorder');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';



	dol_fiche_head(array(), '');

	print '<table class="border centpercent">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

	// Droits de groupes
	foreach ($object->TGroupRightsType as $field){

		print '<tr class="oddeven" id="status-group-'.$field['code'].'" >';
		print '<td  id="coltitle-status-group-'.$field['code'].'" >'.$langs->trans($field['label']).'</td>';
		print '<td  id="colval-status-group-'.$field['code'].'" >';
		//print $form->select_dolgroups($object->TGroupCan[$field['code']], 'TGroupCan['.$field['code'].']', 1, '', 0, $include = '', 1, $conf->entity, true);


		print $form->select_dolgroups($TGroupCan[$field['code']], 'TGroupCan_'.$field['code'], 1, '', 0, $include = '', 1, $conf->entity, true);

		print "</td></tr>";
	}

	print '<tr class="oddeven" id="status-allowed" >';
	print '<td  id="coltitle-status-allowed" >'.$langs->trans('TargetableStatus').'</td>';
	print '<td  id="colval-status-allowed" >';
	$TStatus = $object->fetchAll(0,false, array('entity'=> $conf->entity ));
	if(!empty($TStatus)){
		$TAvailableStatus = array();
		foreach ($TStatus as $key => $status){
			if($status->id != $object->status){
				$TAvailableStatus[$status->id] = $status->label;
			}
		}

		// il faut reverifier vu que l'on a supprimer...
		if(!empty($TStatus)){
			print $form->multiselectarray('TStatusAllowed', $TAvailableStatus, $TStatusAllowed, $key_in_label = 0, $value_as_key = 0, '', 0, '100%', '', '', '', 1);
		}
	}
	print "</td></tr>";


	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans('Create')).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans('Cancel')).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}
else
{
	if (empty($object->id))
	{
		$langs->load('errors');
		print $langs->trans('ErrorRecordNotFound');
	}
	else
	{
		if (!empty($object->id) && $action === 'edit')
		{
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			$head = operationOrderStatusPrepareHead($object);
			$picto = 'operationOrderStatus@operationorder';
			dol_fiche_head($head, 'card', $langs->trans('OperationOrderStatus'), 0, $picto);

			print '<table class="border centpercent">'."\n";

			// Common attributes
			include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

			//$TUserGroups = n

			// Droits de groupes
			foreach ($object->TGroupRightsType as $field){

				print '<tr class="oddeven" id="status-group-'.$field['code'].'" >';
				print '<td  id="coltitle-status-group-'.$field['code'].'" >'.$langs->trans($field['label']).'</td>';
				print '<td  id="colval-status-group-'.$field['code'].'" >';
				//print $form->select_dolgroups($object->TGroupCan[$field['code']], 'TGroupCan['.$field['code'].']', 1, '', 0, $include = '', 1, $conf->entity, true);


				print $form->select_dolgroups($object->TGroupCan[$field['code']], 'TGroupCan_'.$field['code'], 1, '', 0, $include = '', 1, $conf->entity, true);

				print "</td></tr>";
			}

			print '<tr class="oddeven" id="status-allowed" >';
			print '<td  id="coltitle-status-allowed" >'.$langs->trans('TargetableStatus').'</td>';
			print '<td  id="colval-status-allowed" >';
			$TStatus = $object->fetchAll(0,false, array('entity'=> $conf->entity ));
			if(!empty($TStatus)){
				$TAvailableStatus = array();
				foreach ($TStatus as $key => $status){
					if($status->id != $object->status){
						if($status->id == $object->id){
							continue;
						}
						$TAvailableStatus[$status->id] = $status->label;
					}
				}

				// il faut reverifier vu que l'on a supprimer...
				if(!empty($TStatus)){
					print $form->multiselectarray('TStatusAllowed', $TAvailableStatus, $object->TStatusAllowed, $key_in_label = 0, $value_as_key = 0, '', 0, '100%', '', '', '', 1);
				}
			}
			print "</td></tr>";

			// Other attributes
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

			print '</table>';

			dol_fiche_end();

			print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
			print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
			print '</div>';

			print '</form>';
		}
		elseif ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
		{
			$head = operationOrderStatusPrepareHead($object);
			$picto = 'operationOrderStatus@operationorder';
			dol_fiche_head($head, 'card', $langs->trans('OperationOrderStatus'), -1, $picto);

			$formconfirm = getFormConfirmOperationOrderStatus($form, $object, $action);
			if (!empty($formconfirm)) print $formconfirm;

			$linkback = '<a href="' .dol_buildpath('/operationorder/operationorderstatus_list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

			$morehtmlref='<div class="refidno">';
			// Ref bis
			$morehtmlref.=$form->editfieldval("Label", 'label', $object->label, $object, $object->userCan($user, 'edit'), 'string', '', null, null, '', 1);

			$morehtmlref.='</div>';

			$morehtmlstatus.=''; //$object->getLibStatut(2); // pas besoin fait doublon
			dol_banner_tab($object, 'rowid', $linkback, 0, 'rowid', 'code', $morehtmlref, '', 0, '', $morehtmlstatus);


			//operationOrderStatusBannerTab($object);

			print '<div class="fichecenter">';

			print '<div class="fichehalfleft">'; // Auto close by commonfields_view.tpl.php
			print '<div class="underbanner clearboth"></div>';
			print '<table class="border tableforfield" width="100%">'."\n";

			// Common attributes
			//$keyforbreak='fieldkeytoswithonsecondcolumn';
			include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

			// Droits de groupes
			foreach ($object->TGroupRightsType as $field){

				print '<tr class="oddeven" id="status-group-'.$field['code'].'" >';
				print '<td  id="coltitle-status-group-'.$field['code'].'" >'.$langs->trans($field['label']).'</td>';
				print '<td  id="colval-status-group-'.$field['code'].'" >';

				if(!empty($object->TGroupCan[$field['code']])){
					foreach ($object->TGroupCan[$field['code']] as $groupId){
						$group = new UserGroup($db);
						$res = $group->fetch($groupId);
						if($res>0){
								print dolGetBadge($group->name, '', 'secondary');
						}
					}
				}
				print "</td></tr>";
			}

			print '<tr class="oddeven" id="status-allowed" >';
			print '<td  id="coltitle-status-allowed" >'.$langs->trans('TargetableStatus').'</td>';
			print '<td  id="colval-status-allowed" >';
			if(!empty($object->TStatusAllowed)){
				foreach ($object->TStatusAllowed as $fk_status){
					$status = new OperationOrderStatus($db);
					$res = $status->fetch($fk_status);
					if($res>0){
						print $status->getNomUrl(2);
					}
				}
			}
			print "</td></tr>";

			// Other attributes
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

			print '</table>';

			print '</div></div>'; // Fin fichehalfright & ficheaddleft
			print '</div>'; // Fin fichecenter

			print '<div class="clearboth"></div><br />';

			print '<div class="tabsAction">'."\n";
			$parameters=array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
			if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

			if (empty($reshook))
			{

				$actionUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=';

				print dolGetButtonAction($langs->trans("OperationOrderStatusEdit"), '', 'default', $actionUrl . 'edit', '', $user->rights->operationorder->status->write);


				if ($object->status != OperationOrderStatus::STATUS_ACTIVE) {
					print dolGetButtonAction($langs->trans("OperationOrderStatusActivate"), '', 'default', $actionUrl . 'activate', '', $user->rights->operationorder->status->write);
				}
				if ($object->status != OperationOrderStatus::STATUS_DISABLED) {
					print dolGetButtonAction($langs->trans("OperationOrderStatusDisable"), '', 'default', $actionUrl . 'disable', '', $user->rights->operationorder->status->write);
				}


				print dolGetButtonAction($langs->trans("OperationOrderStatusDelete"), '', 'danger', $actionUrl . 'delete', '', $user->rights->operationorder->delete);


			}
			print '</div>'."\n";



			dol_fiche_end(-1);
		}
	}
}


llxFooter();
$db->close();
