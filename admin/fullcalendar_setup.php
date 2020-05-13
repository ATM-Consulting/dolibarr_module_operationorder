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

/**
 * 	\file		admin/operationorder.php
 * 	\ingroup	operationorder
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include '../../main.inc.php'; // From htdocs directory
if (! $res) {
	$res = @include '../../../main.inc.php'; // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once '../lib/operationorder.lib.php';
dol_include_once('abricot/includes/lib/admin.lib.php');
dol_include_once('operationorder/class/operationorder.class.php');

// Translations
$langs->loadLangs(array('operationorder@operationorder','fullcalendar@operationorder', 'admin', 'other'));

// Access control
if (! $user->admin && empty($user->rights->operationorder->status->write)) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'operationorder';

/*
 * Actions
 */


if (preg_match('/set_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$page_name = "OperationOrderSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = operationorderAdminPrepareHead();
dol_fiche_head(
	$head,
	'fullcalendar',
	$langs->trans("Module104088Name"),
	-1,
	"operationorder@operationorder"
);

// Setup page goes here
$form=new Form($db);
$var=false;

if(!function_exists('setup_print_title')){
	print '<div class="error" >'.$langs->trans('AbricotNeedUpdate').' : <a href="http://wiki.atm-consulting.fr/index.php/Accueil#Abricot" target="_blank"><i class="fa fa-info"></i> Wiki</a></div>';
	exit;
}

print '<table class="noborder" width="100%">';

setup_print_title("Parameters");


// Example with imput
//setup_print_input_form_part('CONSTNAME', $langs->trans('ParamLabel'));

// Example with color
//setup_print_input_form_part('CONSTNAME', $langs->trans('ParamLabel'), 'ParamDesc', array('type'=>'color'), 'input', 'ParamHelp');

// Example with placeholder
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array('placeholder'=>'http://'),'input','ParamHelp');

// Example with textarea
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array(),'textarea');

$metas = array('placeholder' => "fr", 'pattern' => "[a-z]{2}");
setup_print_input_form_part('FULLCALENDARSCHEDULER_LOCALE_LANG', false, '', $metas );

$metas = array('placeholder' => "08:00", 'pattern' => "[0-2]{1}[0-9]{1}:[0-5]{1}[0-9]{1}");
setup_print_input_form_part('FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START', false, '', $metas );

$metas = array('placeholder' => "18:00", 'pattern' => "[0-2]{1}[0-9]{1}:[0-5]{1}[0-9]{1}");
setup_print_input_form_part('FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END', false, '', $metas );

$metas = array('placeholder' => "10:00", 'pattern' => "[0-2]{1}[0-9]{1}:[0-5]{1}[0-9]{1}");
setup_print_input_form_part('FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START', false, '', $metas );

$metas = array('placeholder' => "16:00", 'pattern' => "[0-2]{1}[0-9]{1}:[0-5]{1}[0-9]{1}");
setup_print_input_form_part('FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END', false, '', $metas );

$metas = array('placeholder' => "00:30:00", 'pattern' => "[0-2]{1}[0-9]{1}:[0-5]{1}[0-9]{1}:[0-5]{1}[0-9]{1}");
setup_print_input_form_part('FULLCALENDARSCHEDULER_SNAP_DURATION', false, '', $metas );

$metas = array('placeholder' => "00:00", 'pattern' => "[0-2]{1}[0-9]{1}:[0-5]{1}[0-9]{1}");
setup_print_input_form_part('FULLCALENDARSCHEDULER_MIN_TIME', false, '', $metas );

$metas = array('placeholder' => "23:00", 'pattern' => "[0-2]{1}[0-9]{1}:[0-5]{1}[0-9]{1}");
setup_print_input_form_part('FULLCALENDARSCHEDULER_MAX_TIME', false, '', $metas );




print '</table>';




dol_fiche_end(-1);

llxFooter();

$db->close();
