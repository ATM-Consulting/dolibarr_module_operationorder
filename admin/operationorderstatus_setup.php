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
dol_include_once('operationorder/class/operationorderstatus.class.php');

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'bills'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$type='invoice';
$inputCount = empty($inputCount)?1:($inputCount+1);

$staticStatus = new OperationOrderStatus($db);

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';



/*
 * View
 */


llxHeader(
	"", $langs->trans("OperationOrderStatusAdmin")
);

$form=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("OperationOrderStatusAdmin"), $linkback, 'title_setup');

$head = operationorderStatusAdminPrepareHead();
dol_fiche_head($head, 'settings', $langs->trans("OperationOrderStatus"), -1, 'operationorder@operationorder');




/*
 *  Settings
 */

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';


print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

// _printOnOff('CONF', $langs->trans('ConfTradKey'));

/*$metas = array(
	'type' => 'number',
	'step' => '0.01',
	'min' => 0,
	'max' => 100
);
_printInputFormPart('CONF', $langs->trans('ConfTradKey'), '', $metas);*/


$staticStatus = new OperationOrderStatus($db);
$Tstatus = $staticStatus->fetchAll(0, false, array('status' => 1, 'entity' => $conf->entity));
$TstatusAvailable = array();
if(!empty($Tstatus)){
	foreach ($Tstatus as $status){
		$TstatusAvailable[$status->id] = $status->label;
	}
}

$confkey = 'OPODER_STATUS_ON_CLONE';
$inputHtml = $form->selectarray('value'.$inputCount, $TstatusAvailable, $conf->global->{$confkey}, 0, 0, 0, '', 0, 0, 0, '', '', 1);
_printFormPart($confkey, $inputHtml);

/*$inputHtml = $form->multiselectarray('TStatusAllowed', $TAvailableStatus, $TStatusAllowed, $key_in_label = 0, $value_as_key = 0, '', 0, '100%', '', '', '', 1);
$confkey = 'OPODER_STATUS_';
_printFormPart($confkey, $inputHtml);*/

$confkey = 'OPODER_STATUS_ON_PLANNED';
$inputHtml = $form->selectarray('value'.$inputCount, $TstatusAvailable, $conf->global->{$confkey}, 0, 0, 0, '', 0, 0, 0, '', '', 1);
_printFormPart($confkey, $inputHtml);

_printOnOff('OPORDER_CHANGE_OR_STATUS_ON_START');

$confkey = 'OPODER_STATUS_ON_START';
$inputHtml = $form->selectarray('value'.$inputCount, $TstatusAvailable, $conf->global->{$confkey}, 0, 0, 0, '', 0, 0, 0, '', '', 1);
_printFormPart($confkey, $inputHtml, false, "", $confkey."_help");

_printOnOff('OPORDER_CHANGE_OR_STATUS_ON_STOP');

$confkey = 'OPODER_STATUS_ON_STOP';
$inputHtml = $form->selectarray('value'.$inputCount, $TstatusAvailable, $conf->global->{$confkey}, 0, 0, 0, '', 0, 0, 0, '', '', 1);
_printFormPart($confkey, $inputHtml, false, "", $confkey."_help");



print '</table>';
print '</div>';

print '<br>';

_updateBtn();

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
