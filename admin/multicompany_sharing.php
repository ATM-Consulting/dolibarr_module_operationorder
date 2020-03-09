<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$res = @include ("../../main.inc.php"); // For root directory
if (!$res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (!$res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once('/operationorder/lib/operationorder.lib.php');

$langs->load("admin");
$langs->load("operationorder@operationorder");

$element = 'operationorder';

$action = GETPOST("action");
if ($action == 'save_multicompany_shared_conf')
{
	$dao = new DaoMulticompany($db);
	$dao->getEntities();
	$multicompanypriceshare=array();
	foreach ($dao->entities as $entity)
	{
		$multicompanypriceshare[$entity->id] = GETPOST('multicompany-operationorder_'.$entity->id, 'array');

		$entity->options['sharings'][$element] = array();
		$entity->update($entity->id, $user);
	}

	if (!empty($multicompanypriceshare))
	{

		foreach ($multicompanypriceshare as $entityId => $shared)
		{

			//'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED
			if (is_array($shared))
			{
				$shared = array_map('intval', $shared);


				if ($dao->fetch($entityId) > 0)
				{
					$dao->options['sharings'][$element] = $shared;
					if ($dao->update($entityId, $user) < 1)
					{
						setEventMessage('Error');
					}
				}
			}
		}
	}
}


$extrajs = $extracss = array();


llxHeader('', $langs->trans('multicompanySharing'), '', '', '', '', $extrajs, $extracss);



$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("multicompanySharing"), $linkback, 'setup');

// Configuration header
$head = operationorderAdminPrepareHead();
dol_fiche_head($head, 'multicompanySharing', $langs->trans("Module104088Name"), -1, "operationorder@operationorder");



if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
{

	print '<br><br>';

	//var_dump($mc);
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="save_multicompany_shared_conf">';


	print load_fiche_titre($langs->trans("Multicompany"), '', '');

	print '<table class="noborder centpercent" width="100%">';

	$moduleSharingEnabled = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';

	print '<tr class="oddeven" >';
	print '<td align="left" >';
	print $langs->trans("ActivateOperationorderSharing");
	print '</td>';
	print '<td align="center" >';
	print ajax_constantonoff($moduleSharingEnabled, array(), 0);
	print '</td>';
	print '</tr>';


	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("MulticompanyConfiguration").'</td>'."\n";
	print '<td align="center" >'.$langs->trans("ShareWith").'</td>';
	print '</tr>';

	$m = new ActionsMulticompany($db);

	$dao = new DaoMulticompany($db);
	$dao->getEntities();

	if (is_array($dao->entities))
	{
		foreach ($dao->entities as $entitie)
		{
			if (intval($conf->entity) === 1 || intval($conf->entity) === intval($entitie->id))
			{

				print '<tr class="oddeven" >';
				print '<td align="left" >';
				print $entitie->name.' <em>('.$entitie->label.')</em> ';
				//
				print '</td>';
				print '<td align="center" >';
				print _multiselect_entities('multicompany-operationorder_'.$entitie->id, $entitie, '', $element);
				print '</td>';
				print '</tr>';
			}
		}

		print '<tr>';
		print '<td colspan="2" style="text-align:right;" >';
		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
		print '</td>';
		print '</tr>';
	}
	print '</table>';

	print '</form>';

	$langs->loadLangs(array('languages', 'multicompany@multicompany'));

	print '<script type="text/javascript">';
	print '$(document).ready(function () {';

	print '     $.extend($.ui.multiselect.locale, {';
	print '         addAll:\''.$langs->transnoentities("AddAll").'\',';
	print '         removeAll:\''.$langs->transnoentities("RemoveAll").'\',';
	print '         itemsCount:\''.$langs->transnoentities("ItemsCount").'\'';
	print '    });';


	print '    $(function(){';
	print '        $(".multiselect").multiselect({sortable: false, searchable: false});';
	print '    });';
	print '});';
	print '</script>';
}

dol_fiche_end(-1);

llxFooter();

/**
 * 	Return multiselect list of entities.
 *
 * 	@param	string	$htmlname	Name of select
 * 	@param	DaoMulticompany	$current	Current entity to manage
 * 	@param	string	$option		Option
 * 	@return	string
 */
function _multiselect_entities($htmlname, $current, $option = '', $sharingElement = '', $multiselect = 1)
{
	global $conf, $langs, $db, $form;

	$dao = new DaoMulticompany($db);
	$dao->getEntities();
	$sharingElement = !empty($sharingElement) ? $sharingElement : $htmlname;




	if (is_array($dao->entities))
	{
		$TAvailableEntity = array();
		foreach ($dao->entities as $entity){
			if($current->id == $entity->id){
				continue;
			}
			$TAvailableEntity[$entity->id] = $entity->label;
			if (empty($entity->visible)){
				$TAvailableEntity[$entity->id] .= ' ('.$langs->trans('Hidden').')';
			}
		}

		$return = $form->multiselectarray($htmlname, $TAvailableEntity, $current->options['sharings'][$sharingElement], 0, 0, '', 0, '100%');
	}

	return $return;
}
