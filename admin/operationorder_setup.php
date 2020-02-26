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
$langs->loadLangs(array('operationorder@operationorder', 'admin', 'other'));

// Access control
if (! $user->admin) {
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

if ($action == 'updateMask')
{
    $maskconstorder = GETPOST('maskconstOperationOrder', 'alpha');
    $maskorder = GETPOST('maskOperationOrder', 'alpha');

    if ($maskconstorder) $res = dolibarr_set_const($db, $maskconstorder, $maskorder, 'chaine', 0, '', $conf->entity);

    if (!$res > 0) $error++;

    if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

elseif ($action == 'specimen')
{
    $modele = GETPOST('module', 'alpha');

    $operationorder = new OperationOrder($db);
    $operationorder->initAsSpecimen();

    // Search template files
    $file = ''; $classname = ''; $filefound = 0;
    $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
    foreach ($dirmodels as $reldir)
    {
        $file = dol_buildpath($reldir."core/modules/operationorder/doc/pdf_".$modele.".modules.php", 0);
        if (file_exists($file))
        {
            $filefound = 1;
            $classname = "pdf_".$modele;
            break;
        }
    }

    if ($filefound)
    {
        require_once $file;

        $module = new $classname($db);

        if ($module->write_file($operationorder, $langs) > 0)
        {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=operationorder&file=SPECIMEN.pdf");
            return;
        }
        else
        {
            setEventMessages($module->error, null, 'errors');
            dol_syslog($module->error, LOG_ERR);
        }
    }
    else
    {
        setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

if ($action == 'set')
{
    $ret = addDocumentModel($value, $type, $label, $scandir);
}

elseif ($action == 'del')
{
    $ret = delDocumentModel($value, $type);
    if ($ret > 0)
    {
        if ($conf->global->OPERATIONORDER_ADDON_PDF == "$value") dolibarr_del_const($db, 'OPERATIONORDER_ADDON_PDF', $conf->entity);
    }
}
// Set default model
elseif ($action == 'setdoc')
{
    if (dolibarr_set_const($db, "OPERATIONORDER_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
    {
        // La constante qui a ete lue en avant du nouveau set
        // on passe donc par une variable pour avoir un affichage coherent
        $conf->global->OPERATIONORDER_ADDON_PDF = $value;
    }

    // On active le modele
    $ret = delDocumentModel($value, $type);
    if ($ret > 0)
    {
        $ret = addDocumentModel($value, $type, $label, $scandir);
    }
}
elseif ($action == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "OPERATIONORDER_ADDON", $value, 'chaine', 0, '', $conf->entity);
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
    'settings',
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

//setup_print_title("Parameters");

// Example with a yes / no select
//setup_print_on_off('CONSTNAME', $langs->trans('ParamLabel'), 'ParamDesc');

// Example with imput
//setup_print_input_form_part('CONSTNAME', $langs->trans('ParamLabel'));

// Example with color
//setup_print_input_form_part('CONSTNAME', $langs->trans('ParamLabel'), 'ParamDesc', array('type'=>'color'), 'input', 'ParamHelp');

// Example with placeholder
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array('placeholder'=>'http://'),'input','ParamHelp');

// Example with textarea
//setup_print_input_form_part('CONSTNAME',$langs->trans('ParamLabel'),'ParamDesc',array(),'textarea');


print '</table>';



print load_fiche_titre($langs->trans("OrdersNumberingModules"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
    $dir = dol_buildpath($reldir."core/modules/operationorder/");
    if (is_dir($dir))
    {
        $handle = opendir($dir);
        if (is_resource($handle))
        {
            while (($file = readdir($handle)) !== false)
            {
                if (substr($file, 0, 19) == 'mod_operationorder_' && substr($file, dol_strlen($file) - 3, 3) == 'php')
                {
                    $file = substr($file, 0, dol_strlen($file) - 4);

                    require_once $dir.$file.'.php';

                    $module = new $file($db);

                    // Show modules according to features level
                    if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

                    if ($module->isEnabled())
                    {
                        print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
                        print $module->info();
                        print '</td>';

                        // Show example of numbering model
                        print '<td class="nowrap">';
                        $tmp = $module->getExample();
                        if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
                        elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

                        print '<td class="center">';
                        if ($conf->global->OPERATIONORDER_ADDON == $file)
                        {
                            print img_picto($langs->trans("Activated"), 'switch_on');
                        }
                        else
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'">';
                            print img_picto($langs->trans("Disabled"), 'switch_off');
                            print '</a>';
                        }
                        print '</td>';

                        $operationorder = new OperationOrder($db);
                        $operationorder->initAsSpecimen();

                        // Info
                        $htmltooltip = '';
                        $htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                        $operationorder->type = 0;
                        $nextval = $module->getNextValue($operationorder);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip .= ''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
                                    $nextval = $langs->trans($nextval);
                                $htmltooltip .= $nextval.'<br>';
                            } else {
                                $htmltooltip .= $langs->trans($module->error).'<br>';
                            }
                        }

                        print '<td class="center">';
                        print $form->textwithpicto('', $htmltooltip, 1, 0);
                        print '</td>';

                        print "</tr>\n";
                    }
                }
            }
            closedir($handle);
        }
    }
}
print "</table><br>\n";


/*
 * Document templates generators
 */

print load_fiche_titre($langs->trans("OrdersModelModule"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$type."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows = $db->num_rows($resql);
    while ($i < $num_rows)
    {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
}
else
{
    dol_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="60">'.$langs->trans("Default").'</td>';
print '<td class="center" width="32">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="32">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
$activatedModels = array();

foreach ($dirmodels as $reldir)
{
    foreach (array('','/doc') as $valdir)
    {
        $dir = dol_buildpath($reldir."core/modules/operationorder".$valdir);

        if (is_dir($dir))
        {
            $handle=opendir($dir);
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    $filelist[]=$file;
                }
                closedir($handle);
                arsort($filelist);

                foreach($filelist as $file)
                {
                    if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
                    {
                        if (file_exists($dir.'/'.$file))
                        {
                            $name = substr($file, 4, dol_strlen($file) -16);
                            $classname = substr($file, 0, dol_strlen($file) -12);
                            require_once $dir.'/'.$file;
                            $module = new $classname($db);

                            $modulequalified=1;
                            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
                            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

                            if ($modulequalified)
                            {
                                print '<tr class="oddeven"><td width="100">';
                                print (empty($module->name)?$name:$module->name);
                                print "</td><td>\n";
                                if (method_exists($module, 'info')) print $module->info($langs);
                                else print $module->description;
                                print '</td>';

                                // Active
                                if (in_array($name, $def))
                                {
                                    print '<td class="center">'."\n";
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
                                    print img_picto($langs->trans("Enabled"), 'switch_on');
                                    print '</a>';
                                    print '</td>';
                                }
                                else
                                {
                                    print '<td class="center">'."\n";
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'">'.img_picto($langs->trans("SetAsDefault"), 'switch_off').'</a>';
                                    print "</td>";
                                }

                                // Defaut
                                print '<td class="center">';
                                if ($conf->global->OPERATIONORDER_ADDON_PDF == $name)
                                {
                                    print img_picto($langs->trans("Default"), 'on');
                                }
                                else
                                {
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("SetAsDefault"), 'off').'</a>';
                                }
                                print '</td>';

                                // Info
                                $htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
                                $htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
                                if ($module->type == 'pdf')
                                {
                                    $htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
                                }
                                $htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
                                $htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
                                $htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
                                $htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftInvoices").': '.yn($module->option_draft_watermark, 1, 1);


                                print '<td class="center">';
                                print $form->textwithpicto('', $htmltooltip, 1, 0);
                                print '</td>';

                                // Preview
                                print '<td class="center">';
                                if ($module->type == 'pdf')
                                {
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'bill').'</a>';
                                }
                                else
                                {
                                    print img_object($langs->trans("PreviewNotAvailable"), 'generic');
                                }
                                print '</td>';

                                print "</tr>\n";
                            }
                        }
                    }
                }
            }
        }
    }
}
print '</table>';
print "<br>";


dol_fiche_end(-1);

llxFooter();

$db->close();
