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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcontract.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
dol_include_once('operationorder/class/operationorder.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');

if(empty($user->rights->operationorder->read)) accessforbidden();

$langs->load('operationorder@operationorder');
$langs->load('bills');

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$lineid = GETPOST('lineid');
$confirm = GETPOST('confirm', 'alpha');

$time_plannedhour 	= intval(GETPOST('time_plannedhour', 'int'));
$time_plannedmin 	= intval(GETPOST('time_plannedmin', 'int'));
$time_spenthour 	= intval(GETPOST('time_spenthour', 'int'));
$time_spentmin 		= intval(GETPOST('time_spentmin', 'int'));


$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'operationordercard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');


$object = new OperationOrder($db);

if (!empty($id) || !empty($ref)) $object->fetch($id, true, $ref);

$hookmanager->initHooks(array($contextpage, 'globalcard'));


if ($object->isextrafieldmanaged)
{
    $extrafields = new ExtraFields($db);

    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
    $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
}

// Initialize array of search criterias
//$search_all=trim(GETPOST("search_all",'alpha'));
//$search=array();
//foreach($object->fields as $key => $val)
//{
//    if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
//}
$usercanread = $user->rights->operationorder->read;
$usercancreate = $user->rights->operationorder->write;

$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $usercancreate; // Used by the include of actions_dellink.inc.php
$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdonw.inc.php
$permissiontoadd = $usercancreate; // Used by the include of actions_addupdatedelete.inc.php

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




    $error = 0;
	switch ($action) {
        case 'update_attribute':
            if (!empty($user->rights->operationorder->write))
            {
                $values = array();
                $attribute = GETPOST('attribute');

                if ($attribute == 'date_operation_order')
                {
                    $object->date_operation_order = dol_mktime(GETPOST('date_operation_orderhour'), GETPOST('date_operation_ordermin'), 0, GETPOST('date_operation_ordermonth'), GETPOST('date_operation_orderday'), GETPOST('date_operation_orderyear'));
                }
                else
                {
                    $value = GETPOST($attribute);
                    $values[$attribute] = $value;
                    $object->setValues($values);
                }

                $object->save($user);
            }

            break;
		case 'add':
		    if (!empty($conf->multicurrency->enabled))
            {
                require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
                $object->fk_multicurrency = MultiCurrency::getIdFromCode($object->db, $conf->currency);
                $object->multicurrency_code = $conf->currency;
            }
		case 'update':
			$object->setValues($_REQUEST); // Set standard attributes

//    var_dump($_REQUEST);exit;

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
				$action = 'edit';
				break;
			}

//        $object->fk_project = '';
			$res = $object->save($user);
//			var_dump($res, $object->db);exit;
            if ($res < 0)
            {
                setEventMessage($object->errors, 'errors');
                if (empty($object->id)) $action = 'create';
                else $action = 'edit';
            }
            else
            {
                header('Location: '.dol_buildpath('/operationorder/card.php', 1).'?id='.$object->id);
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
                header('Location: '.dol_buildpath('/operationorder/card.php', 1).'?id='.$object->id);
                exit;
            }
            break;
		case 'confirm_clone':
            if (!empty($user->rights->operationorder->write))
            {
                $object->cloneObject($user);
                header('Location: '.dol_buildpath('/operationorder/card.php', 1).'?id='.$object->id);
                exit;
            }

        case 'confirm_modify':
			if (!empty($user->rights->operationorder->write)) $object->setDraft($user);

			break;

        case 'reopen':
            if (!empty($user->rights->operationorder->write)) $object->setReopen($user);

            break;
		case 'confirm_close':
			if (!empty($user->rights->operationorder->write)) $object->setClosed($user);

			header('Location: '.dol_buildpath('/operationorder/card.php', 1).'?id='.$object->id);
			exit;

		case 'confirm_delete':
			if (!empty($user->rights->operationorder->delete)) $object->delete($user);

			header('Location: '.dol_buildpath('/operationorder/list.php', 1));
			exit;

		// link from llx_element_element
		case 'dellink':
			$object->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/operationorder/card.php', 1).'?id='.$object->id);
			exit;

        case 'addline':
            if ($usercancreate)
            {
                $langs->load('errors');
                $error = 0;

                // Set if we used free entry or predefined product
                $predef = '';
                $product_desc = (GETPOST('description') ?GETPOST('dp_desc') : '');
                $prod_entry_mode = GETPOST('prod_entry_mode');
                if ($prod_entry_mode == 'free') $idprod = 0;
                else $idprod = GETPOST('fk_product', 'int');

                $qty = GETPOST('qty'.$predef);
                $price = GETPOST('price'.$predef);
                $fk_warehouse = GETPOST('fk_warehouse');
                $pc = GETPOST('pc'.$predef);
                $time_planned = $time_plannedhour * 60 * 60 + $time_plannedmin * 60; // store in seconds
                $time_spent = $time_spenthour * 60 * 60 + $time_spentmin * 60;

                // Extrafields
                $extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
                $array_options = $extrafields->getOptionalsFromPost($object->table_element_line, $predef);
                // Unset extrafield
                if (is_array($extralabelsline)) {
                    // Get extra fields
                    foreach ($extralabelsline as $key => $value) {
                        unset($_POST["options_".$key]);
                    }
                }


                if ($prod_entry_mode == 'free' && empty($idprod) && GETPOST('type') < 0) {
                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
                    $error++;
                }
                if ($qty == '') {
                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
                    $error++;
                }
                if ($prod_entry_mode == 'free' && empty($idprod) && empty($product_desc)) {
                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
                    $error++;
                }

                if (!$error && ($qty >= 0) && (!empty($product_desc) || !empty($idprod))) {
                    // Clean parameters
                    $date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
                    $date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));

                    if (!empty($idprod)) {
                        $prod = new Product($db);
                        $prod->fetch($idprod);

                        $label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

                        $desc = '';

                        // Define output language
                        if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
                            $outputlangs = $langs;
                            $newlang = '';
                            if (empty($newlang) && GETPOST('lang_id', 'aZ09'))
                                $newlang = GETPOST('lang_id', 'aZ09');
                            if (empty($newlang))
                                $newlang = $object->thirdparty->default_lang;
                            if (!empty($newlang)) {
                                $outputlangs = new Translate("", $conf);
                                $outputlangs->setDefaultLang($newlang);
                            }

                            $desc = (!empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
                        } else {
                            $desc = $prod->description;
                        }

                        if (!empty($product_desc) && !empty($conf->global->MAIN_NO_CONCAT_DESCRIPTION)) $desc = $product_desc;
                        else $desc = dol_concatdesc($desc, $product_desc, '', !empty($conf->global->MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION));

                        $type = $prod->type;
                    } else {
                        $label = (GETPOST('product_label') ? GETPOST('product_label') : '');
                        $desc = $product_desc;
                        $type = GETPOST('type');
                    }

                    $desc = dol_htmlcleanlastbr($desc);

                    $info_bits = 0;

                    // Insert line
                    $result = $object->addline($desc, $qty, $price, $fk_warehouse, $pc, $time_planned, $time_spent, $idprod, $info_bits, $date_start, $date_end, $type, -1, 0, GETPOST('fk_parent_line'), $label, $array_options, '', 0);

                    if ($result > 0) {

                        if (!empty($conf->global->PRODUIT_SOUSPRODUITS) && !empty($idprod))
                        {
                            $product = new Product($db);
                            $product->fetch($idprod);

                            $product->get_sousproduits_arbo();
                            $arbo = $product->get_arbo_each_prod();
                            if (!empty($arbo))
                            {
                                foreach ($arbo as $product_info)
                                {
                                    $object->addline('', $product_info['nb_total']*$qty, '', $fk_warehouse, $pc, 0, 0, $product_info['id'], 0, '', '', $product_info['type'], -1, 0, $result, '', array(), '', 0);
                                }
                            }
                        }

                        $ret = $object->fetch($object->id); // Reload to get new records

                        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                            // Define output language
                            $outputlangs = $langs;
                            $newlang = GETPOST('lang_id', 'alpha');
                            if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang))
                                $newlang = $object->thirdparty->default_lang;
                            if (!empty($newlang)) {
                                $outputlangs = new Translate("", $conf);
                                $outputlangs->setDefaultLang($newlang);
                            }

                            $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
                        }

                        unset($_POST['prod_entry_mode']);

                        unset($_POST['qty']);
                        unset($_POST['type']);
                        unset($_POST['product_ref']);
                        unset($_POST['product_label']);
                        unset($_POST['product_desc']);
                        unset($_POST['dp_desc']);
                        unset($_POST['idprod']);

                        unset($_POST['date_starthour']);
                        unset($_POST['date_startmin']);
                        unset($_POST['date_startsec']);
                        unset($_POST['date_startday']);
                        unset($_POST['date_startmonth']);
                        unset($_POST['date_startyear']);
                        unset($_POST['date_endhour']);
                        unset($_POST['date_endmin']);
                        unset($_POST['date_endsec']);
                        unset($_POST['date_endday']);
                        unset($_POST['date_endmonth']);
                        unset($_POST['date_endyear']);

                        setEventMessage($langs->trans('OperationOrderLineAdded'));
						header('Location: '.dol_buildpath('/operationorder/card.php', 1).'?id='.$object->id)."#addline";
						exit;

                    } else {
                        setEventMessages($object->error, $object->errors, 'errors');
                    }
                }

            }

            break;
        case 'confirm_deleteline':
            // Remove a product line
            if ($confirm == 'yes' && $usercancreate)
            {
                $result = $object->removeChild($user, 'OperationOrderDet', $lineid);
                if ($result)
                {
                    // Define output language
                    $outputlangs = $langs;
                    $newlang = '';
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09'))
                        $newlang = GETPOST('lang_id', 'aZ09');
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                        $newlang = $object->thirdparty->default_lang;
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                    }
                    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                        $ret = $object->fetch($object->id); // Reload to get new records
                        $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
                    }

                    header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
                    exit;
                }
                else
                {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }
            break;
	}

    /*
     *  Update a line
     */

    if ($action == 'updateline' && $usercancreate && GETPOSTISSET('save'))
    {
    	$updateLineResult = false;

        // Clean parameters
        $date_start = '';
        $date_end = '';
        $date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
        $date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
        $description = dol_htmlcleanlastbr(GETPOST('product_desc', 'none'));

        $fk_warehouse = GETPOST('fk_warehouse');
        $pc = GETPOST('pc'.$predef);

		$price = GETPOST('price'.$predef);

		$time_plannedhour = GETPOST('time_plannedhour', 'int');
		$time_plannedmin = GETPOST('time_plannedmin', 'int');
		$time_spenthour = GETPOST('time_spenthour', 'int');
		$time_spentmin = GETPOST('time_spentmin', 'int');

        $time_planned = doubleval($time_plannedhour) * 60 * 60 + doubleval($time_plannedmin) * 60; // store in seconds
        $time_spent = doubleval($time_spenthour) * 60 * 60 + doubleval($time_spentmin) * 60;

        // Define info_bits
        $info_bits = 0;

        // Extrafields Lines
        $extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
        $array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
        // Unset extrafield POST Data
        if (is_array($extralabelsline)) {
            foreach ($extralabelsline as $key => $value) {
                unset($_POST["options_".$key]);
            }
        }

        // Define special_code for special lines
        $special_code = GETPOST('special_code');
        if (!GETPOST('qty')) $special_code = 3;

        // Check minimum price
        $productid = GETPOST('fk_product', 'int');
        if (!empty($productid)) {
            $product = new Product($db);
            $product->fetch($productid);

            $type = $product->type;

            $label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

        } else {
            $type = GETPOST('type');
            $label = (GETPOST('product_label') ? GETPOST('product_label') : '');

            // Check parameters
            if (GETPOST('type') < 0) {
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
                $error++;
            }
        }

        if (!$error) {

			$result = $object->updateline(GETPOST('lineid'), $description, GETPOST('qty'), $price, $fk_warehouse, $pc, $time_planned, $time_spent,$productid, $info_bits, $date_start, $date_end, $type, GETPOST('fk_parent_line'), $label, $special_code, $array_options);

            if ($result >= 0) {

				$updateLineResult = true;

                if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                    // Define output language
                    $outputlangs = $langs;
                    $newlang = '';
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09'))
                        $newlang = GETPOST('lang_id', 'aZ09');
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                        $newlang = $object->thirdparty->default_lang;
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                    }

                    $ret = $object->fetch($object->id); // Reload to get new records
                    $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
                }

                unset($_POST['qty']);
                unset($_POST['type']);
                unset($_POST['productid']);
                unset($_POST['product_ref']);
                unset($_POST['product_label']);
                unset($_POST['product_desc']);

                unset($_POST['date_starthour']);
                unset($_POST['date_startmin']);
                unset($_POST['date_startsec']);
                unset($_POST['date_startday']);
                unset($_POST['date_startmonth']);
                unset($_POST['date_startyear']);
                unset($_POST['date_endhour']);
                unset($_POST['date_endmin']);
                unset($_POST['date_endsec']);
                unset($_POST['date_endday']);
                unset($_POST['date_endmonth']);
                unset($_POST['date_endyear']);


				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#item_'.GETPOST('lineid', 'int'));
				exit;
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }


        if(!$updateLineResult){
			// Pour reaffichage de la fiche en cours d'edition
			header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=editline&lineid=5'.GETPOST('lineid'));
			exit();
		}


    } elseif ($action == 'updateline' && $usercancreate && GETPOST('cancel', 'alpha') == $langs->trans('Cancel')) {
        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // Pour reaffichage de la fiche en cours d'edition
		exit;
    }
    // Link to a project
    elseif ($action == 'classin' && $usercancreate)
    {
        $object->setProject(GETPOST('projectid', 'int'));
    }
    // Positionne ref commande client
    elseif ($action == 'setref_client' && $usercancreate) {
        $object->ref_client = GETPOST('ref_client');
        $result = $object->update($user);
        if ($result < 0)
        {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    // Link to a project
    elseif ($action == 'setcontratin' && $usercancreate)
    {
        $object->fk_contrat = GETPOST('fk_contrat');
        $result = $object->update($user);
        if ($result < 0)
        {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

    // Actions when printing a doc from card
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Actions to build doc
    $upload_dir = $conf->operationorder->multidir_output[$object->entity];
    $permissiontoadd = $usercancreate;
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

}



/**
 * View
 */
$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$formcontrat = new FormContract($db);

$title=$langs->trans('OperationOrder');
$arrayofjs = '';
$arrayofcss = array(
	'/operationorder/css/operation-order-card.css',
	'/operationorder/css/animate.css'
);
llxHeader('', $title , '', '', 0, 0, $arrayofjs, $arrayofcss);

if ($action == 'create')
{
    print load_fiche_titre($langs->trans('NewOperationOrder'), '', 'operationorder@operationorder');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

    dol_fiche_head(array(), '');

    print '<table class="border centpercent">'."\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

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
        $object->fields['ref_client']['visible'] = 2;
        $object->fields['fk_soc']['visible'] = 2;
        $object->fields['fk_project']['visible'] = 2;
        $object->fields['fk_contrat']['visible'] = 2;

        if (!empty($object->id) && $action === 'edit')
        {
            print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';

            $head = operationorder_prepare_head($object);
            $picto = 'operationorder@operationorder';
            dol_fiche_head($head, 'card', $langs->trans('OperationOrder'), 0, $picto);

            print '<table class="border centpercent">'."\n";

            // Common attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

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
            $head = operationorder_prepare_head($object);
            $picto = 'operationorder@operationorder';
            dol_fiche_head($head, 'card', $langs->trans('OperationOrder'), -1, $picto);

            $formconfirm = getFormConfirmOperationOrder($form, $object, $action);
            if (!empty($formconfirm)) print $formconfirm;


            $linkback = '<a href="' .dol_buildpath('/operationorder/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

            $morehtmlref='<div class="refidno">';

            // Ref bis
            $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->operationorder->write, 'string', '', 0, 1);
            $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->operationorder->write, 'string', '', null, null, '', 1);

//            $morehtmlref.=$form->editfieldkey("Thirdparty", 'fk_soc', $object->ref_client, $object, $user->rights->operationorder->write, 'string', '', 0, 1);
//            $morehtmlref.=$form->editfieldval("Thirdparty", 'fk_soc', $object->ref_client, $object, $user->rights->operationorder->write, 'string', '', null, null, '', 1);
            // Thirdparty
            $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);

            // Project
            if (!empty($conf->projet->enabled))
            {
                $langs->load("projects");
                $morehtmlref .= '<br>'.$langs->trans('Project').' ';
                if ($usercancreate)
                {
                    if ($action != 'classify')
                        $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
                    if ($action == 'classify') {
                        //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                        $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                        $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                        $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                        $morehtmlref .= $formproject->select_projects($object->fk_soc, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                        $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                        $morehtmlref .= '</form>';
                    } else {
                        $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_soc, $object->fk_project, 'none', 0, 0, 0, 1);
                    }
                } else {
                    if (!empty($object->fk_project)) {
                        $proj = new Project($db);
                        $proj->fetch($object->fk_project);
                        $morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
                        $morehtmlref .= $proj->ref;
                        $morehtmlref .= '</a>';
                    } else {
                        $morehtmlref .= '';
                    }
                }
            }

            // Contrat
            if (!empty($conf->contrat->enabled))
            {
                $langs->load("contrat");
                $morehtmlref .= '<br>'.$langs->trans('Contrat').' ';
                if ($usercancreate)
                {
                    if ($action != 'setcontrat')
                        $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=setcontrat&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetContrat')).'</a> : ';
                    if ($action == 'setcontrat') {
                        //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                        $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                        $morehtmlref .= '<input type="hidden" name="action" value="setcontratin">';
                        $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                        ob_start();
                        $formcontrat->select_contract($object->fk_soc, $object->fk_contrat, 'fk_contrat', $maxlength, 1);
                        $select_contrat = ob_get_clean();
                        $morehtmlref .= $select_contrat;
                        $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                        $morehtmlref .= '</form>';
                    } else {
                        $contrat = new Contrat($db);
                        $contrat->fetch($object->fk_contrat);
                        $morehtmlref .= $contrat->getNomUrl();
                    }
                } else {
                    if (!empty($object->fk_contrat)) {
                        $contrat = new Contrat($db);
                        $contrat->fetch($object->fk_contrat);
                        $morehtmlref .= $contrat->getNomUrl();
                    } else {
                        $morehtmlref .= '';
                    }
                }
            }

            $morehtmlref.='</div>';


            $morehtmlstatus.=''; //$object->getLibStatut(2); // pas besoin fait doublon
            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);

            print '<div class="fichecenter">';

            print '<div class="fichehalfleft">'; // Auto close by commonfields_view.tpl.php
            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">'."\n";

            $permok = $usercancreate;
            // Common attributes
//            $keyforbreak='total_ht';
//            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';
            include dol_buildpath('operationorder/core/tpl/commonfields_view.tpl.php');

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print '</table>';

            print '</div></div>'; // Fin fichehalfright & ficheaddleft
            print '</div>'; // Fin fichecenter

            print '<div class="clearboth"></div><br />';


			/*
			 * Lines
			 */

			// JS nested
			$TNested = $object->fetch_all_children_nested();
			print '<div id="ajaxResults" ></div>';
			print '<div id="nestedLines" >';
			print _displaySortableNestedItems($TNested, 'sortableLists', true);
			print '</div>';
			print '<script src="'.dol_buildpath('operationorder/js/jquery-sortable-lists.min.js',1).'" ></script>';
			print '<link rel="stylesheet" href="'.dol_buildpath('operationorder/css/sortable.css',1).'" >';

			print '
			<script type="text/javascript">
			$(function()
			{
			    // Animate modified line
			    if(window.location.hash) {
					  var hash = window.location.hash; //Puts hash in variable, and removes the # character
					  // hash found
					  console.log($(hash).length);
					  if ($(hash).length){
					      if($(hash).hasClass("operation-order-sortable-list__item")) //operation-order-sortable-list__item__title
						  {
						      	let itemTitleblock = $(hash).find("> .operation-order-sortable-list__item__title");

								$(\'html,body\').animate({
								  scrollTop: itemTitleblock.offset().top-150
								}, 300);

								itemTitleblock.addClass("flipInX");
								itemTitleblock.addClass("animated");
						  }
					  }
				  } else {
					  // No hash found
				  }


				var options = {
					insertZone: 5, // This property defines the distance from the left, which determines if item will be inserted outside(before/after) or inside of another item.
					placeholderClass: \'operation-order-sortable-list__item--placeholder\',
					hintClass: \'operation-order-sortable-list__item--hint\',
					onChange: function( cEl )
					{

						$("#ajaxResults").html("");

						$.ajax({
							url: "'.dol_buildpath('operationorder/scripts/interface.php?action=setOperationOrderlevelHierarchy',1).'",
							method: "POST",
							data: {
							    \'operation-order-id\' : '.$object->id.',
								\'items\' : $(\'#sortableLists\').sortableListsToHierarchy()
							},
							dataType: "json",

							// La fonction à apeller si la requête aboutie
							success: function (data) {
								// Loading data
								console.log(data);
								if(data.result > 0 ){
								   // ok case
								   $("#ajaxResults").html(\'<span class="badge badge-success">\' + data.msg + \'</span>\');
								}
								else if(data.result < 0 ){
								   // error case
								   $("#ajaxResults").html(\'<span class="badge badge-danger">\' + data.errorMsg + \'</span>\');
								}
								else{
								   // nothing to do ?
								}
							},
							// La fonction à appeler si la requête n\'a pas abouti
							error: function( jqXHR, textStatus ) {
								alert( "Request failed: " + textStatus );
							}
						});
					},
					complete: function( cEl )
					{



					},
					isAllowed: function( cEl, hint, target )
					{
						// Be carefull if you test some ul/ol elements here.
						// Sometimes ul/ols are dynamically generated and so they have not some attributes as natural ul/ols.
						// Be careful also if the hint is not visible. It has only display none so it is at the previouse place where it was before(excluding first moves before showing).

						if( target.data(\'id\') != cEl.data(\'parent\') )
						{
							hint.addClass( "hint-desabled" );
							hint.removeClass( "hint-enabled" );
							return false;
						}
						else
						{
							hint.removeClass( "hint-desabled" );
							hint.addClass( "hint-enabled" );
							return true;
						}
					},
//					opener: {
//						active: false,
//						as: \'html\',  // if as is not set plugin uses background image
//						close: \'<i class="fa fa-minus c3"></i>\',  // or \'fa-minus c3\',  // or \'./imgs/Remove2.png\',
//						open: \'<i class="fa fa-plus"></i>\',  // or \'fa-plus\',  // or\'./imgs/Add2.png\',
//						openerCss: {
//							\'display\': \'inline-block\',
//							//\'width\': \'18px\', \'height\': \'18px\',
//							\'float\': \'left\',
//							\'margin-left\': \'-35px\',
//							\'margin-right\': \'5px\',
//							//\'background-position\': \'center center\', \'background-repeat\': \'no-repeat\',
//							\'font-size\': \'1.1em\'
//						}
//					},
					//ignoreClass: \'clickable\',
					handle: ".handle",
					insertZonePlus: true,
				};


				$(\'#sortableLists\').sortableLists( options );
			});


			</script>';



			// ADD FORM
			if($action != 'editline' && $object->status == OperationOrder::STATUS_DRAFT){
				print '<div class="add-line-form-wrap" >';
				print '<div class="add-line-form-title" >';
				print $langs->trans("AddOperationOrderLine");
				print '</div>';
				print '<div class="add-line-form-body" >';
				print _displayFormFields($object);
				print '</div>';
				print '</div>';
			}
			elseif($action == 'editline' && $object->status == OperationOrder::STATUS_DRAFT){
				$lineid = GETPOST('lineid', 'int');
				if(!empty($lineid)){

					$line=new OperationOrderDet($db);
					$res = $line->fetch($lineid);

					print '<div id="dialog-form-edit" style="display: none;" >';
					print '<div id="edit-item_'.$line->id.'" class="edit-line-form-wrap" title="'.$line->ref.'" >';
					print '<div class="edit-line-form-body" >';
					if($res>0){
						print _displayFormFields($object, $line, 0);
					}
					else{
						print $langs->trans('LineNotFound');
					}
					print '</div>';
					print '</div>';
					print '</div>';


					// MISE A JOUR AJAX DE L'ORDRE DES LIGNES
					print '
					<script type="text/javascript">
					$(function()
					{
						var cardUrl = "'.$_SERVER["PHP_SELF"].'?id='.$object->id.'";
						var itemHash = "#item_'.$line->id.'";

						var dialogBox = jQuery("#dialog-form-edit");
						var width = $(window).width();
						var height = $(window).height();
						if(width > 700){ width = 700; }
						if(height > 600){ height = 600; }
						//console.log(height);
						dialogBox.dialog({
							autoOpen: false,
							resizable: true,
					//		height: height,
							width: width,
							modal: true,
							buttons: {
								"'.$langs->transnoentitiesnoconv('Update').'": function() {
									dialogBox.find("form").submit();
								}
							},
							close: function( event, ui ) {
								window.location.replace(cardUrl + itemHash);
							}
						});

						function popOperationOrderEditLineFormDialog(id)
						{
							var item = $("#edit-item_" + id);

							dialogBox.dialog({
							  title: item.attr("title")
							});

							dialogBox.dialog( "open" );
						}

						popOperationOrderEditLineFormDialog("'.intval($lineid).'");

					});
					</script>';
				}
			}

            print '<div class="tabsAction">'."\n";
            if ($action != 'editline')
            {
                $parameters=array();
                $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
                if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

                if (empty($reshook))
                {
                    // Send
                    //        print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>'."\n";

                    // Modify
                    if (!empty($user->rights->operationorder->write))
                    {
                        // Valid
                        if ($object->status == OperationOrder::STATUS_DRAFT) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid">'.$langs->trans('OperationOrderValid').'</a></div>'."\n";

                        // Reopen
                        if ($object->status == OperationOrder::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=modify">'.$langs->trans('OperationOrderModify').'</a></div>'."\n";
                        if ($object->status == OperationOrder::STATUS_CLOSED) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans('OperationOrderReopen').'</a></div>'."\n";

                        // Close
                        if ($object->status == OperationOrder::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=close">'.$langs->trans('OperationOrderClose').'</a></div>'."\n";

                        // Clone
                        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=clone">'.$langs->trans("OperationOrderClone").'</a></div>'."\n";
                    }
                    else
                    {
                        // Valid
                        if ($object->status == OperationOrder::STATUS_DRAFT) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('OperationOrderValid').'</a></div>'."\n";

                        // Reopen
                        if ($object->status == OperationOrder::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('OperationOrderModify').'</a></div>'."\n";

                        // Close
                        if ($object->status == OperationOrder::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('OperationOrderClose').'</a></div>'."\n";

                        // Clone
                        print '<div class="inline-block divButAction"><a class="butAction" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("OperationOrderClone").'</a></div>'."\n";
                    }

                    if (!empty($user->rights->operationorder->delete))
                    {
                        print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("OperationOrderDelete").'</a></div>'."\n";
                    }
                    else
                    {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("OperationOrderDelete").'</a></div>'."\n";
                    }
                }
            }
            print '</div>'."\n";

            print '<div class="fichecenter"><div class="fichehalfleft">';
            print '<a name="builddoc"></a>'; // ancre

            // Documents generes
            $filename = dol_sanitizeFileName($object->ref);
            $filedir = $conf->operationorder->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
            $urlsource = $_SERVER['PHP_SELF'].'?id='.$object->id;
            $genallowed = $usercanread;
            $delallowed = $usercancreate;

//            var_dump($filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf);exit;
            print $formfile->showdocuments('operationorder', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang, '', $object);
            $somethingshown = $formfile->numoffiles;

            // Show links to link elements
            $linktoelem = $form->showLinkToObjectBlock($object, null, array($object->element));
            $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

            print '</div><div class="fichehalfright"><div class="ficheaddleft">';

            // List of actions on element
            include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
            $formactions = new FormActions($db);
            $somethingshown = $formactions->showactions($object, $object->element, $socid, 1);

            print '</div></div></div>';

            dol_fiche_end(-1);
        }
	}
}


llxFooter();
$db->close();



function _displaySortableNestedItems($TNested, $htmlId='', $open = true){
	global $langs, $user, $extrafields;
	if(!empty($TNested) && is_array($TNested)){
		$out = '<ul id="'.$htmlId.'" class="operation-order-sortable-list" >';
		foreach ($TNested as $k => $v) {
			$line = $v['object'];
			/**
			 * @var $line OperationOrderDet
			 */
			$line->calcPrices();

			if (empty($line->id)) $line->id = $line->rowid;

			$class = '';
			if ($open) {
				$class .= 'sortableListsClosed';
			}

			// Product
			$text = '';
			if ($line->fk_product > 0) {
				$product = new Product($line->db);
				$product->fetch($line->fk_product);
				$product->ref = $line->ref; //can change ref in hook
				$product->label = $line->label; //can change label in hook
				$text = $product->getNomUrl(1) . ' - ' . $product->label;

				$line->stock_reel 		= $product->stock_reel;
				$line->stock_theorique 	= $product->stock_theorique;
			}

			$out .= '<li id="item_' . $line->id . '" class="operation-order-sortable-list__item ' . $class . '" ';
			$out .= ' data-id="' . $line->id . '" ';
			$out .= ' data-ref="' . dol_escape_htmltag($line->ref) . '" ';
			$out .= ' data-rank="' . dol_escape_htmltag($line->rang) . '" ';
			$out .= ' data-parent="' . $line->fk_parent_line . '" ';
			$out .= '>';
			$out .= '<div class="operation-order-sortable-list__item__title">';
			$out .= '	<div class="operation-order-sortable-list__item__title__flex">';

			// DESCRIPTION
			$out .= '		<div class="operation-order-sortable-list__item__title__col -description">';
			$out .= $text;
			$out .= '		</div>';

			// QTY ORDERED
			$out .= '		<div class="operation-order-sortable-list__item__title__col -qty-ordered">';
			$out .= '			<span class="classfortooltip" title="' . $langs->trans("QtyOrdered") . '" >';
			$out .= '				<i class="fas fa-box-open"></i> ' . $line->qty;
			$out .= '			</span>';
			$out .= '		</div>';


			// TIME SPENT AND PLANNED
			$out .= '		<div class="operation-order-sortable-list__item__title__col -time-spent">';
			$out .= '			<i class="far fa-hourglass"></i> ';

			$hoursSpendClass = '';
			if(intval($line->time_planned) < intval($line->time_spent)){
				$hoursSpendClass = 'badge badge-danger';
			}

			$out .= '			<span class="classfortooltip '.$hoursSpendClass.'"  title="' . $langs->trans("HoursSpent") . '">';
			if (!empty($line->time_spent)){
				$out .= convertSecondToTime(intval($line->time_spent));
			}else{
				$out .= ' -- ';
			}

			$out .= '			</span>';
			$out.= ' / ';
			$out.= '			<span class="classfortooltip"  title="'.$langs->trans("HoursPlanned").'">';
			if (!empty($line->time_planned)){
				$out.= convertSecondToTime(intval($line->time_planned)) ;
			}else{
				$out .= ' -- ';
			}
			$out.= '			</span>';

			$out.= '		</div>';

			// ECART
			$out .= '		<div class="operation-order-sortable-list__item__title__col -difference">';
			if (!empty($line->time_planned) && !empty($line->time_spent)){
				$ecart = intval($line->time_planned) - intval($line->time_spent);
				$sign = '';
				if($ecart>0){
					$textClass = "text-success";
					$iconClass = "fa-caret-down";
					$sign = '-';
				}elseif($ecart==0){
					$textClass = "text-warning";
					$iconClass = "fa-caret-left";
				}else{
					$textClass = "text-danger";
					$iconClass = "fa-caret-up";
					$sign = '+';
				}

				$out.= '<span class="'.$textClass.' classfortooltip paddingrightonly" title="'.$langs->trans('TimeDifference').'" ><i class="fa '.$iconClass.'"></i> '.$sign. dol_print_date(abs($ecart), '%HH%M', true).'</span>';

			}else{
				$out .= ' -- ';
			}
			$out .= '		</div>';

			// PU HT
			$out .= '		<div class="operation-order-sortable-list__item__title__col -unit-price">';
			$out .= price($line->price).'&nbsp;'.$langs->trans('HT');
			$out .= '		</div>';


			// TOTAL HT
			$out .= '		<div class="operation-order-sortable-list__item__title__col -total-price">';
			$out .= price($line->total_ht).'&nbsp;'.$langs->trans('HT');
			$out .= '		</div>';


			// EMPLACEMENT
			$out .= '		<div class="operation-order-sortable-list__item__title__col -stock-status">';
			$out .=  $line->showOutputFieldQuick('fk_warehouse');
			$out .= '		</div>';

			// STOCK
			$out .= '		<div class="operation-order-sortable-list__item__title__col -stock-status">';
			$out .= $line->stockStatus();
			$out .= '		</div>';


			// ACTIONS
			$out.= '		<div class="operation-order-sortable-list__item__title__col -action">';

			if ($line->status == OperationOrder::STATUS_DRAFT && !empty($user->rights->operationorder->write)) {

				$editUrl = dol_buildpath('operationorder/card.php', 1).'?id='. $line->fk_operation_order.'&amp;action=editline&amp;lineid='.$line->id;

				//#item_'.$line->id.'
				$out.= '<a href="'.$editUrl.'" class="classfortooltip operation-order-sortable-list__item__title__button -edit-btn"  title="' . $langs->trans("Edit") . '" data-id="'.$line->id.'">';
				$out.= '<i class="fa fa-pencil "></i>';
				$out.= '</a>';

				$deleteUrl = dol_buildpath('operationorder/card.php', 1).'?id='. $line->fk_operation_order.'&amp;action=ask_deleteline&amp;lineid='.$line->id;

				$out.= '<a href="'.$deleteUrl.'" class="classfortooltip operation-order-sortable-list__item__title__button  -delete-btn"  title="' . $langs->trans("Delete") . '"  data-id="'.$line->id.'">';
				$out.= '<i class="fa fa-trash "></i>';
				$out.= '</a>';

				// Handler icon
				$out .= '<span class="operation-order-sortable-list__item__title__button handle move"><i title="' . $langs->trans("Move") . '" class="fa fa-th"></i></span>';
			}

			$out.= '		</div>';

			$out.= '	</div>';

			//Line extrafield
//			if (!empty($extrafields))
//			{
//				$line->fetch_optionals();
//				$out.= '<!-- extrafields -->';
//				$out.= '<table>';
//				$out.= $line->showOptionals($extrafields, 'view', array(), '', '', 1);
//				$out.= '</table>';
//			}


			$out.= '</div>';
			$out.= _displaySortableNestedItems($v['children'], '', $open);
			$out.= '</li>';
		}
		$out.= '</ul>';
		return $out;
	}
	else{
		return '';
	}
}


/**
 * @param $object OperationOrder
 * @param $line OperationOrderDet
 * @param $showSubmitBtn bool
 * @return string
 */
function _displayFormFields($object, $line= false, $showSubmitBtn = true)
{
	global $langs, $db, $form;

	$outForm = '';

	if($line && $line->id > 0){
		$action = 'edit';
	}
	else{
		$action = 'create';
		$line=new OperationOrderDet($db);

		// set default values
		$line->qty = '';
		$line->price = '';
	}

	$actionUrl = $_SERVER["PHP_SELF"].'?id='.$object->id;

	// Ancors
	$actionUrl.= ($action == 'create') ? '#addline':'#item_'.$line->id;

	$outForm.=  ($action == 'create') ? '<a name="addline" ></a>':'';

	$outForm.= '<form name="addproduct" action="' . $actionUrl .'" method="POST">' . "\n";
	$outForm.= '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
	$outForm.= '<input type="hidden" name="id" value="' . $object->id . '">' . "\n";
	$outForm.= '<input type="hidden" name="mode" value="">' . "\n";

	if($action == 'edit') {
		$outForm .= '<input type="hidden" name="action" value="updateline">' . "\n";
		$outForm .= '<input type="hidden" name="save" value="1">' . "\n";
		$outForm .= '<input type="hidden" name="editline" value="'.$line->id.'">' . "\n";
		$outForm .= '<input type="hidden" name="lineid" value="'.$line->id.'">' . "\n";
	}else{
		$outForm .= '<input type="hidden" name="action" value="addline">' . "\n";
	}

	$line->fields = dol_sort_array($line->fields, 'position');

	$outForm.= '<table class="table-full">';
	foreach($line->fields as $key => $val)
	{
		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3) continue;

		$mode = 'edit'; // edit or view

		// for some case if you need to change display mode
		if($key == 'xxxxxx' && $action == 'edit') {
			$mode = 'view';
		}

		if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! verifCond($val['enabled'])) continue;	// We don't want this field

		$outForm.=  '<tr id="field_'.$key.'">';
		$outForm.=  '<td';
		$outForm.=  ' class="titlefieldcreate';
		if ($val['notnull'] > 0) $outForm.=  ' fieldrequired';
		if ($val['type'] == 'text' || $val['type'] == 'html') $outForm.=  ' tdtop';
		$outForm.=  '"';
		$outForm.=  '>';

		if (!empty($val['help'])) $outForm.=  $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		else $outForm.=  $langs->trans($val['label']);
		$outForm.=  '</td>';

		$outForm.=  '<td>';

		// Load value from object
		$value = '';
		if(isset($line->{$key})){
			$value = $line->{$key};
		}

		if(GETPOSTISSET($key)){
			if (in_array($val['type'], array('int', 'integer'))) $value = GETPOST($key, 'int');
			elseif ($val['type'] == 'text' || $val['type'] == 'html') $value = GETPOST($key, 'none');
			else $value = GETPOST($key, 'alpha');
		}

		if($mode == 'edit'){
			$outForm.=  $line->showInputField($val, $key, $value, '', '', '', 0);
		}
		else{
			$outForm.=  $line->showOutputField($val, $key, $value, '', '', '', 0);
		}
		$outForm.=  '</td>';

		$outForm.=  '</tr>';
	}

	if($showSubmitBtn){

		$outForm.=  '<tr>';
		$outForm.=  '	<td colspan="2"><hr/></td>';
		$outForm.=  '</tr>';

		$outForm.=  '<tr>';
		$outForm.=  '	<td>';
		$outForm.=  '	</td>';
		$outForm.=  '	<td>';
		if($action == 'create'){
			$outForm.=  '<button type="submit" class="button" >'.$langs->trans('Add').'</button>';
		}else{
			$outForm.=  '<button type="submit" class="button" >'.$langs->trans('Save').'</button>';
		}
		$outForm.=  '	<button type="reset" class="button" >'.$langs->trans('Reset').'</button>';
		$outForm.=  '	</td>';
		$outForm.=  '</tr>';
	}

	$outForm.= '</table>';



	$outForm.= '</form>';

	return $outForm;
}
