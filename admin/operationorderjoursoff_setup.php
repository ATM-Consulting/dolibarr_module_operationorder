<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2012-2013  Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2014		Teddy Andreotti				<125155@supinfo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/admin/facture.php
 *		\ingroup    facture
 *		\brief      Page to setup invoice module
 */

// Dolibarr environment
$res = @include '../../main.inc.php'; // From htdocs directory
if (! $res) {
	$res = @include '../../../main.inc.php'; // From "custom" directory
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/operationorder.lib.php';
dol_include_once('operationorder/class/operationorderjoursoff.class.php');

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'bills'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$type='invoice';
$inputCount = empty($inputCount)?1:($inputCount+1);

$staticJoursOff = new OperationOrderJoursOff($db);

/*
 * Actions
 */
if (!empty($action))
{
	switch ($action)
	{
		case 'sync':

			if(!empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY)) {
				list($id_country, $code_country) = explode(':', $conf->global->MAIN_INFO_SOCIETE_COUNTRY);

				if($code_country=='FR') {
					$url='https://calendar.google.com/calendar/ical/fr.french%23holiday%40group.v.calendar.google.com/public/basic.ics';
				}
				else{
					$url = '';
				}

			}

			if(empty($url)) {
				setEventMessage($langs->trans('ErrCalendarURLNotFound'), 'errors');
			}
			else{
				$staticJoursOff->synchronizeFromURL($url);
			}
			break;

		case 'delete':
			$ret = $staticJoursOff->fetch($id);
			if ($ret > 0)
			{
				$res = $staticJoursOff->delete($user);
			}
			break;

		default:
			break;
	}
}
/*
 * View
 */


llxHeader(
	"", $langs->trans("OperationOrderStatusAdmin")
);

$form=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("OperationOrderStatusAdmin"), $linkback, 'title_setup');

$head = operationorderAdminPrepareHead();
dol_fiche_head($head, 'oojoursOff', $langs->trans("OperationOrderJoursOff"), -1, 'operationorder@operationorder');

/*
 *  Settings
 */
print_barre_liste($langs->trans('PublicHolidayNonWorkedDaysList'), 0, $_SERVER['PHP_SELF']);
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';


print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
$excludedFields = array('date_creation', 'tms', 'entity');

$staticJoursOff->fields['rowid']['label'] = '';
$staticJoursOff->fields['rowid']['visible'] = 1;
$staticJoursOff->fields['rowid']['position'] = 9000;

$staticJoursOff->fields = dol_sort_array($staticJoursOff->fields, 'position');

$nbFields = 0;
foreach ($staticJoursOff->fields as $key => $field)
{
	if (!in_array($key, $excludedFields) && $field['visible'])
	{
		print '<td>'.$langs->trans($field['label']).'</td>';
		$nbFields++;
	}
}

print "</tr>\n";

$TJOff = $staticJoursOff->fetchAll();
//var_dump($TJOff);

if (empty($TJOff)) print '<tr><td colspan="' . $nbFields . '" align="center">'.$langs->trans('NoOperationOrder').'</td></tr>';
else
{
	/** @var OperationOrderJoursOff $jourOff
	 */
	foreach ($TJOff as $jourOff)
	{
		print '<tr>';
		foreach ($staticJoursOff->fields as $key => $field)
		{
//			var_dump($jourOff);
			if (!in_array($key, $excludedFields) && $field['visible'])
			{
				if ($key != 'rowid') print '<td>'.$jourOff->showOutputField($field,$key,$jourOff->{$key}).'</td>';
				else
				{
					print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$jourOff->id.'">'.img_delete().'</a></td>';
				}
				$nbFields++;
			}
		}
		print '</tr>';
	}
}

print '</table>';
print '</div>';

print '<br>';

//_updateBtn();
_syncJOff();

print '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();

/**
 * Print an update button
 *
 * @return void
 */
function _updateBtn()
{
	global $langs;
	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '</div>';
}

function _syncJOff()
{
	global $langs;

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=sync">'.$langs->trans("OnlineSync").'</a>';
	print '</div>';
}

/**
 * Print a On/Off button
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description
 *
 * @return void
 */
function _printOnOff($confkey, $title = false, $desc = '')
{
	global $langs;

	print '<tr class="oddeven">';
	print '<td>'.($title?$title:$langs->trans($confkey));
	if (!empty($desc)) {
		print '<br><small>'.$langs->trans($desc).'</small>';
	}
	print '</td>';
	print '<td class="center" width="20">&nbsp;</td>';
	print '<td class="right" width="300">';
	print ajax_constantonoff($confkey);
	print '</td></tr>';
}


/**
 * Print a form part
 *
 * @param string $confkey the conf key
 * @param string $inputHtml the input in html
 * @param bool   $title   Title of conf
 * @param string $desc    Description of
 * @param bool   $help    help description
 *
 * @return void
 */
function _printFormPart($confkey, $inputHtml, $title = false, $desc = '', $help = false)
{
	global $langs, $conf, $db, $inputCount;

	$form=new Form($db);

	$defaultMetas = array(
		'name' => 'value'.$inputCount
	);


	print '<tr class="oddeven">';
	print '<td>';

	if (!empty($help)) {
		print $form->textwithtooltip(($title?$title:$langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));
	} else {
		print $title?$title:$langs->trans($confkey);
	}

	if (!empty($desc)) {
		print '<br><small>'.$langs->trans($desc).'</small>';
	}

	print '</td>';
	print '<td class="center" width="20">&nbsp;</td>';
	print '<td class="right" width="300">';
	print '<input type="hidden" name="param'.$inputCount.'" value="'.$confkey.'">';

	print '<input type="hidden" name="action" value="setModuleOptions">';
	print $inputHtml;
	print '</td></tr>';

	$inputCount = empty($inputCount)?1:($inputCount+1);
}

/**
 * Print a form part
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description of
 * @param array  $metas   html meta
 * @param string $type    type of input textarea or input
 * @param bool   $help    help description
 *
 * @return void
 */
function _printInputFormPart($confkey, $title = false, $desc = '', $metas = array(), $type = 'input', $help = false)
{
	global $langs, $conf, $db, $inputCount;

	$form=new Form($db);

	$defaultMetas = array(
		'name' => 'value'.$inputCount
	);

	if ($type!='textarea') {
		$defaultMetas['type']   = 'text';
		$defaultMetas['value']  = $conf->global->{$confkey};
	}


	$metas = array_merge($defaultMetas, $metas);
	$metascompil = '';
	foreach ($metas as $key => $values) {
		$metascompil .= ' '.$key.'="'.$values.'" ';
	}

	print '<tr class="oddeven">';
	print '<td>';

	if (!empty($help)) {
		print $form->textwithtooltip(($title?$title:$langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));
	} else {
		print $title?$title:$langs->trans($confkey);
	}

	if (!empty($desc)) {
		print '<br><small>'.$langs->trans($desc).'</small>';
	}

	print '</td>';
	print '<td class="center" width="20">&nbsp;</td>';
	print '<td class="right" width="300">';
	print '<input type="hidden" name="param'.$inputCount.'" value="'.$confkey.'">';

	print '<input type="hidden" name="action" value="setModuleOptions">';
	if ($type=='textarea') {
		print '<textarea '.$metascompil.'  >'.dol_htmlentities($conf->global->{$confkey}).'</textarea>';
	} else {
		print '<input '.$metascompil.'  />';
	}
	print '</td></tr>';

	$inputCount = empty($inputCount)?1:($inputCount+1);
}
