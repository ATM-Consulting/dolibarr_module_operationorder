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

        case 'confirm_reopen':
			if (!empty($user->rights->operationorder->write)) $object->setDraft($user);

			break;
		case 'confirm_validate':
			if (!empty($user->rights->operationorder->write)) $object->setValid($user);

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

//                $object->addline(
//                    GETPOST('dp_desc'),
//                    GETPOST('')
//                );


                // Set if we used free entry or predefined product
                $predef = '';
                $product_desc = (GETPOST('dp_desc') ?GETPOST('dp_desc') : '');
                $price_ht = GETPOST('price_ht');
                $price_ht_devise = GETPOST('multicurrency_price_ht');
                $prod_entry_mode = GETPOST('prod_entry_mode');
                if ($prod_entry_mode == 'free')
                {
                    $idprod = 0;
                    $tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
                }
                else
                {
                    $idprod = GETPOST('idprod', 'int');
                    $tva_tx = '';
                }

                $qty = GETPOST('qty'.$predef);
                $remise_percent = (GETPOST('remise_percent'.$predef) != '' ? GETPOST('remise_percent'.$predef) : 0);

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

                if (empty($idprod) && ($price_ht < 0) && ($qty < 0)) {
                    setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
                    $error++;
                }
                if ($prod_entry_mode == 'free' && empty($idprod) && GETPOST('type') < 0) {
                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
                    $error++;
                }
                if ($prod_entry_mode == 'free' && empty($idprod) && $price_ht == '' && $price_ht_devise == '') 	// Unit price can be 0 but not ''. Also price can be negative for order.
                {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
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

                if (!$error && !empty($conf->variants->enabled) && $prod_entry_mode != 'free') {
                    if ($combinations = GETPOST('combinations', 'array')) {
                        //Check if there is a product with the given combination
                        $prodcomb = new ProductCombination($db);

                        if ($res = $prodcomb->fetchByProductCombination2ValuePairs($idprod, $combinations)) {
                            $idprod = $res->fk_product_child;
                        }
                        else
                        {
                            setEventMessages($langs->trans('ErrorProductCombinationNotFound'), null, 'errors');
                            $error++;
                        }
                    }
                }

                if (!$error && ($qty >= 0) && (!empty($product_desc) || !empty($idprod))) {
                    // Clean parameters
                    $date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
                    $date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
                    $price_base_type = (GETPOST('price_base_type', 'alpha') ?GETPOST('price_base_type', 'alpha') : 'HT');

                    // Ecrase $pu par celui du produit
                    // Ecrase $desc par celui du produit
                    // Ecrase $tva_tx par celui du produit
                    // Ecrase $base_price_type par celui du produit
                    if (!empty($idprod)) {
                        $prod = new Product($db);
                        $prod->fetch($idprod);

                        $label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

                        // Update if prices fields are defined
                        $tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
                        $tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
                        if (empty($tva_tx)) $tva_npr = 0;

                        $pu_ht = $prod->price;
                        $pu_ttc = $prod->price_ttc;
                        $price_min = $prod->price_min;
                        $price_base_type = $prod->price_base_type;

                        // If price per segment
                        if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->thirdparty->price_level))
                        {
                            $pu_ht = $prod->multiprices[$object->thirdparty->price_level];
                            $pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
                            $price_min = $prod->multiprices_min[$object->thirdparty->price_level];
                            $price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
                            if (!empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
                            {
                                if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx = $prod->multiprices_tva_tx[$object->thirdparty->price_level];
                                if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr = $prod->multiprices_recuperableonly[$object->thirdparty->price_level];
                            }
                        }
                        // If price per customer
                        elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES))
                        {
                            require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

                            $prodcustprice = new Productcustomerprice($db);

                            $filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $object->thirdparty->id);

                            $result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
                            if ($result >= 0)
                            {
                                if (count($prodcustprice->lines) > 0)
                                {
                                    $pu_ht = price($prodcustprice->lines[0]->price);
                                    $pu_ttc = price($prodcustprice->lines[0]->price_ttc);
                                    $price_base_type = $prodcustprice->lines[0]->price_base_type;
                                    $tva_tx = $prodcustprice->lines[0]->tva_tx;
                                    if ($prodcustprice->lines[0]->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) $tva_tx .= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
                                    $tva_npr = $prodcustprice->lines[0]->recuperableonly;
                                    if (empty($tva_tx)) $tva_npr = 0;
                                }
                            }
                            else
                            {
                                setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
                            }
                        }
                        // If price per quantity
                        elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
                        {
                            if ($prod->prices_by_qty[0])	// yes, this product has some prices per quantity
                            {
                                // Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
                                $pqp = GETPOST('pbq', 'int');

                                // Search price into product_price_by_qty from $prod->id
                                foreach ($prod->prices_by_qty_list[0] as $priceforthequantityarray)
                                {
                                    if ($priceforthequantityarray['rowid'] != $pqp) continue;
                                    // We found the price
                                    if ($priceforthequantityarray['price_base_type'] == 'HT')
                                    {
                                        $pu_ht = $priceforthequantityarray['unitprice'];
                                    }
                                    else
                                    {
                                        $pu_ttc = $priceforthequantityarray['unitprice'];
                                    }
                                    // Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
                                    break;
                                }
                            }
                        }
                        // If price per quantity and customer
                        elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES))
                        {
                            if ($prod->prices_by_qty[$object->thirdparty->price_level])	// yes, this product has some prices per quantity
                            {
                                // Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
                                $pqp = GETPOST('pbq', 'int');
                                // Search price into product_price_by_qty from $prod->id
                                foreach ($prod->prices_by_qty_list[$object->thirdparty->price_level] as $priceforthequantityarray)
                                {
                                    if ($priceforthequantityarray['rowid'] != $pqp) continue;
                                    // We found the price
                                    if ($priceforthequantityarray['price_base_type'] == 'HT')
                                    {
                                        $pu_ht = $priceforthequantityarray['unitprice'];
                                    }
                                    else
                                    {
                                        $pu_ttc = $priceforthequantityarray['unitprice'];
                                    }
                                    // Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
                                    break;
                                }
                            }
                        }

                        $tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
                        $tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));

                        // if price ht is forced (ie: calculated by margin rate and cost price). TODO Why this ?
                        if (!empty($price_ht)) {
                            $pu_ht = price2num($price_ht, 'MU');
                            $pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
                        }
                        // On reevalue prix selon taux tva car taux tva transaction peut etre different
                        // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
                        elseif ($tmpvat != $tmpprodvat) {
                            if ($price_base_type != 'HT') {
                                $pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
                            } else {
                                $pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
                            }
                        }

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

                        // Add custom code and origin country into description
                        if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (!empty($prod->customcode) || !empty($prod->country_code))) {
                            $tmptxt = '(';
                            // Define output language
                            if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
                                $outputlangs = $langs;
                                $newlang = '';
                                if (empty($newlang) && GETPOST('lang_id', 'alpha'))
                                    $newlang = GETPOST('lang_id', 'alpha');
                                if (empty($newlang))
                                    $newlang = $object->thirdparty->default_lang;
                                if (!empty($newlang)) {
                                    $outputlangs = new Translate("", $conf);
                                    $outputlangs->setDefaultLang($newlang);
                                    $outputlangs->load('products');
                                }
                                if (!empty($prod->customcode))
                                    $tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
                                if (!empty($prod->customcode) && !empty($prod->country_code))
                                    $tmptxt .= ' - ';
                                if (!empty($prod->country_code))
                                    $tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, 0, $db, $outputlangs, 0);
                            } else {
                                if (!empty($prod->customcode))
                                    $tmptxt .= $langs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
                                if (!empty($prod->customcode) && !empty($prod->country_code))
                                    $tmptxt .= ' - ';
                                if (!empty($prod->country_code))
                                    $tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, 0, $db, $langs, 0);
                            }
                            $tmptxt .= ')';
                            $desc = dol_concatdesc($desc, $tmptxt);
                        }

                        $type = $prod->type;
                        $fk_unit = $prod->fk_unit;
                    } else {
                        $pu_ht = price2num($price_ht, 'MU');
                        $pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
                        $tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
                        $tva_tx = str_replace('*', '', $tva_tx);
                        $label = (GETPOST('product_label') ? GETPOST('product_label') : '');
                        $desc = $product_desc;
                        $type = GETPOST('type');
                        $fk_unit = GETPOST('units', 'alpha');
                        $pu_ht_devise = price2num($price_ht_devise, 'MU');
                    }

                    // Margin
                    $fournprice = price2num(GETPOST('fournprice'.$predef) ? GETPOST('fournprice'.$predef) : '');
                    $buyingprice = price2num(GETPOST('buying_price'.$predef) != '' ? GETPOST('buying_price'.$predef) : ''); // If buying_price is '0', we muste keep this value

                    // Local Taxes
                    $localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
                    $localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

                    $desc = dol_htmlcleanlastbr($desc);

                    $info_bits = 0;
                    if ($tva_npr)
                        $info_bits |= 0x01;

                    if (((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->produit->ignore_price_min_advance)) || empty($conf->global->MAIN_USE_ADVANCED_PERMS)) && (!empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min)))) {
                        $mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
                        setEventMessages($mesg, null, 'errors');
                    } else {
                        // Insert line
//                        var_dump($label);exit;
                        $result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $info_bits, 0, $price_base_type, $pu_ttc, $date_start, $date_end, $type, - 1, 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_options, $fk_unit, '', 0, $pu_ht_devise);

                        if ($result > 0) {
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
                            unset($_POST['remise_percent']);
                            unset($_POST['price_ht']);
                            unset($_POST['multicurrency_price_ht']);
                            unset($_POST['price_ttc']);
                            unset($_POST['tva_tx']);
                            unset($_POST['product_ref']);
                            unset($_POST['product_label']);
                            unset($_POST['product_desc']);
                            unset($_POST['fournprice']);
                            unset($_POST['buying_price']);
                            unset($_POST['np_marginRate']);
                            unset($_POST['np_markRate']);
                            unset($_POST['dp_desc']);
                            unset($_POST['idprod']);
                            unset($_POST['units']);

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
                        } else {
                            setEventMessages($object->error, $object->errors, 'errors');
                        }
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
    if ($action == 'updateline' && $usercancreate && GETPOST('save'))
    {
        // Clean parameters
        $date_start = '';
        $date_end = '';
        $date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
        $date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
        $description = dol_htmlcleanlastbr(GETPOST('product_desc', 'none'));
        $pu_ht = GETPOST('price_ht');
        $vat_rate = (GETPOST('tva_tx') ?GETPOST('tva_tx') : 0);
        $pu_ht_devise = GETPOST('multicurrency_subprice');

        // Define info_bits
        $info_bits = 0;
        if (preg_match('/\*/', $vat_rate))
            $info_bits |= 0x01;

        // Define vat_rate
        $vat_rate = str_replace('*', '', $vat_rate);
        $localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
        $localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);

        // Add buying price
        $fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
        $buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : ''); // If buying_price is '0', we muste keep this value

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
        $productid = GETPOST('productid', 'int');
        if (!empty($productid)) {
            $product = new Product($db);
            $product->fetch($productid);

            $type = $product->type;

            $price_min = $product->price_min;
            if ((!empty($conf->global->PRODUIT_MULTIPRICES) || !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) && !empty($object->thirdparty->price_level))
                $price_min = $product->multiprices_min[$object->thirdparty->price_level];

            $label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

            if (((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->produit->ignore_price_min_advance)) || empty($conf->global->MAIN_USE_ADVANCED_PERMS)) && ($price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min)))) {
                setEventMessages($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency)), null, 'errors');
                $error++;
            }
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
            if (empty($user->rights->margins->creer))
            {
                foreach ($object->lines as &$line)
                {
                    if ($line->id == GETPOST('lineid'))
                    {
                        $fournprice = $line->fk_fournprice;
                        $buyingprice = $line->pa_ht;
                        break;
                    }
                }
            }
            $result = $object->updateline(GETPOST('lineid'), $description, $pu_ht, GETPOST('qty'), GETPOST('remise_percent'), $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $info_bits, $date_start, $date_end, $type, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $special_code, $array_options, GETPOST('units'), $pu_ht_devise);

            if ($result >= 0) {
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
                unset($_POST['remise_percent']);
                unset($_POST['price_ht']);
                unset($_POST['multicurrency_price_ht']);
                unset($_POST['price_ttc']);
                unset($_POST['tva_tx']);
                unset($_POST['product_ref']);
                unset($_POST['product_label']);
                unset($_POST['product_desc']);
                unset($_POST['fournprice']);
                unset($_POST['buying_price']);

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
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }
    } elseif ($action == 'updateline' && $usercancreate && GETPOST('cancel', 'alpha') == $langs->trans('Cancel')) {
        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // Pour reaffichage de la fiche en cours d'edition
        exit();
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
llxHeader('', $title);

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
            $morehtmlref.=$form->editfieldkey("RefClient", 'ref_client', $object->ref_client, $object, $user->rights->operationorder->write, 'string', '', 0, 1);
            $morehtmlref.=$form->editfieldval("RefClient", 'ref_client', $object->ref_client, $object, $user->rights->operationorder->write, 'string', '', null, null, '', 1);

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
//                        $morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
//                        $morehtmlref .= $proj->ref;
//                        $morehtmlref .= '</a>';
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

            // Common attributes
            $keyforbreak='total_ht';
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print '</table>';

            print '</div></div>'; // Fin fichehalfright & ficheaddleft
            print '</div>'; // Fin fichecenter

            print '<div class="clearboth"></div><br />';




            /*
             * Lines
             */
            //$result = $object->getLinesArray();

            print '<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#addline' : '#line_'.GETPOST('lineid')).'" method="POST">
            <input type="hidden" name="token" value="' . $_SESSION ['newtoken'].'">
            <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
            <input type="hidden" name="mode" value="">
            <input type="hidden" name="id" value="' . $object->id.'">';

            if (!empty($conf->use_javascript_ajax) && $object->status == OperationOrder::STATUS_DRAFT) {
                include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
            }

            print '<div class="div-table-responsive-no-min">';
            print '<table id="tablelines" class="noborder noshadow" width="100%">';

            $defaulttpldir = str_replace(DOL_DOCUMENT_ROOT, '', dol_buildpath('operationorder/core/tpl'));
            // Show object lines
            if (!empty($object->lines))
                $ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 1, $defaulttpldir);

            $numlines = count($object->lines);

            /*
             * Form to add new line
             */
            if ($object->status == OperationOrder::STATUS_DRAFT && $usercancreate && $action != 'selectlines')
            {
                if ($action != 'editline')
                {
                    // Add free products/services
                    $object->formAddObjectLine(1, $mysoc, $soc);

                    $parameters = array();
                    // Note that $action and $object may be modified by hook
                    $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action);
                }
            }
            print '</table>';
            print '</div>';

            print "</form>\n";

//            dol_fiche_end();






            print '<div class="tabsAction">'."\n";
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
                    if ($object->status == OperationOrder::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans('OperationOrderModify').'</a></div>'."\n";

                    // Clone
                    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=clone">'.$langs->trans("OperationOrderClone").'</a></div>'."\n";
                }
                else
                {
                    // Valid
                    if ($object->status == OperationOrder::STATUS_DRAFT) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('OperationOrderValid').'</a></div>'."\n";

                    // Reopen
                    if ($object->status == OperationOrder::STATUS_VALIDATED) print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('OperationOrderModify').'</a></div>'."\n";

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
