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
dol_include_once('operationorder/class/operationorder.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');

if(empty($user->rights->operationorder->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('operationorder@operationorder');


$toselect = GETPOST('toselect', 'array');
$search_by=GETPOST('search_by', 'alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$object = new OperationOrderHistory($db);
$operationOrder = new OperationOrder($db);

if (!empty($id) || !empty($ref)) {
	$operationOrder->fetch($id, true, $ref);

	$result = restrictedArea($user, $operationOrder->element, $id, $operationOrder->table_element . '&' . $operationOrder->element);


	$status = new Operationorderstatus($db);
	$res = $status->fetchDefault($object->status, $object->entity);
	if ($res < 0) {
		setEventMessage($langs->trans('ErrorLoadingStatus'), 'errors');
	}
	$usercanread = $user->rights->operationorder->read;
	$usercancreate = $permissionnote = $permissiontoedit = $permissiontoadd = $permissiondellink = $operationOrder->userCan($user, 'edit'); // Used by the include of actions_setnotes.inc.php

}
$hookmanager->initHooks(array('operationorderhistorylist'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


if (empty($reshook))
{
    // do action from GETPOST ...
}

/*
 * View
 */

llxHeader('', $langs->trans('OperationOrderHistoryList'), '', '');

//$type = GETPOST('type');
//if (empty($user->rights->operationorder->all->read)) $type = 'mine';

if ($operationOrder->id > 0){
	$head = operationorder_prepare_head($operationOrder);
	$picto = 'operationorder@operationorder';
	dol_fiche_head($head, 'history', $langs->trans('OperationOrder'), -1, $picto);
	$linkback = '<a href="'.dol_buildpath('/operationorder/list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	$morehtmlref='<div class="refidno">';
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $operationOrder->ref_client, $operationOrder, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $operationOrder->ref_client, $operationOrder, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $operationOrder->thirdparty->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref.='<br>'.$langs->trans('Project') . ' ';

		if (! empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($operationOrder->fk_project);
			$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $operationOrder->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
			$morehtmlref.=$proj->ref;
			$morehtmlref.='</a>';
		} else {
			$morehtmlref.='';
		}
	}
	$morehtmlref.='</div>';
	dol_banner_tab($operationOrder, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	unset($object->fields['fk_operationorder']);
	print '<div class="underbanner clearboth"></div>';
}
	// TODO ajouter les champs de son objet que l'on souhaite afficher
$keys = array_keys($object->fields);
$fieldList = 't.'.implode(', t.', $keys);


$listViewName = 'operationorderhistory';
$inputPrefix  = 'Listview_'.$listViewName.'_search_';

// Search value
if(GETPOSTISSET('button_removefilter_x')){
    $search_overshootStatus = '';
}


$sql = 'SELECT '.$fieldList;

// Add fields from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= ' FROM '.MAIN_DB_PREFIX.'operationorderhistory t ';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'operationorder oo ON (t.fk_operationorder = oo.rowid) ';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (t.fk_user_creat = u.rowid) ';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'operationorderdet ood ON (t.fk_operationorderdet = ood.rowid) ';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON (ood.fk_product = p.rowid) ';

$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListJoin', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= ' WHERE  t.entity IN ('.getEntity('operationorder', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;
if ($operationOrder->id > 0) $sql.= ' AND t.fk_operationorder = '.$operationOrder->id;

// Add where from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_operationorderhistory', 'POST');

$nbLine = GETPOST('limit');
if (empty($nbLine)) $nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;


// List configuration
$listViewConfig = array(
    'view_type' => 'list' // default = [list], [raw], [chart]
    ,'allow-fields-select' => true
    ,'limit'=>array(
        'nbLine' => $nbLine
    )
    ,'list' => array(
        'title' => $langs->trans('OperationOrderHistoryList')
        ,'image' => 'title_generic.png'
        ,'picto_precedent' => '<'
        ,'picto_suivant' => '>'
        ,'noheader' => 0
        ,'messageNothing' => $langs->trans('NoOperationOrderHistory')
        ,'picto_search' => img_picto('', 'search.png', '', 0)
        ,'massactions'=>array(

        )
        ,'param_url' => '&id='.GETPOST('id')
    )
    ,'subQuery' => array()
    ,'link' => array()
    ,'type' => array(
        'date_creation' => 'date' // [datetime], [hour], [money], [number], [integer]
    )
    ,'search' => array(
        'date_creation' => array('search_type' => 'calendars', 'allow_is_null' => false, 'field' => array('t.date_creation'))
        ,'fk_operationorder' => array('search_type' => true, 'table' => 'oo', 'field' => array('ref')) // input text de recherche sur plusieurs champs
        ,'fk_operationorderdet' => array('search_type' => true, 'table' => 'p', 'field' => array('ref')) // input text de recherche sur plusieurs champs
        ,'fk_user_creat' => array('search_type' => true, 'table' => 'u', 'field' => array('lastname', 'firstname')) // input text de recherche sur plusieurs champs
        ,'title' => array('search_type' => true, 'table' => 't', 'field' => 'title') // input text de recherche sur plusieurs champs
        ,'description' => array('search_type' => true, 'table' => 't', 'field' => 'description') // input text de recherche sur plusieurs champs
    )
    ,'translate' => array()
    ,'hide' => array(
        'rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
    )
    ,'title'=>array (
        'date_creation' => $langs->trans($object->fields['date_creation']['label']),
        'fk_operationorder' => $langs->trans('OperationOrder'),
        'fk_operationorderdet' => $langs->trans('Product'),
        'fk_user_creat' => $langs->trans($object->fields['fk_user_creat']['label']),
        'title' => $langs->trans($object->fields['title']['label']),
        'description' => $langs->trans($object->fields['description']['label'])
    )
    ,'eval'=>array(
        'fk_operationorder' => '_getOONomURL(\'@fk_operationorder@\')',
        'fk_operationorderdet' => '_getProdNomURL(\'@fk_operationorderdet@\')',
        'fk_user_creat' => '_getUserNomURL(\'@fk_user_creat@\')'
    )
    ,'sortfield'=>'rowid'
    ,'sortorder'=>'DESC'
);
if(!empty($operationOrder->id)) {
	unset($listViewConfig['title']['fk_operationorder']);
	unset($listViewConfig['search']['fk_operationorder']);
	unset($listViewConfig['eval']['fk_operationorder']);
}

foreach ($object->fields as $key => $field){
    // visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create).
    // Using a negative value means field is not shown by default on list but can be selected for viewing)


    if(!empty($field['enabled']) && !isset($listViewConfig['title'][$key]) && !empty($field['visible']) && in_array($field['visible'], array(1,2,4)) ) {
        $listViewConfig['title'][$key] = $langs->trans($field['label']);
    }

    if(!isset($listViewConfig['hide'][$key]) && (empty($field['visible']) || $field['visible'] <= -1)){
        $listViewConfig['hide'][] = $key;
    }

//    if(!isset($listViewConfig['eval'][$key])){
//        $listViewConfig['eval'][$key] = '_getObjectOutputField(\''.$key.'\', \'@rowid@\', \'@val@\')';
//    }
}







$r = new Listview($db, 'operationorderhistory');

// Change view from hooks
$parameters=array('listViewConfig' => $listViewConfig);
$reshook=$hookmanager->executeHooks('listViewConfig',$parameters,$r);    // Note that $action and $object may have been modified by hook
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if ($reshook>0)
{
    $listViewConfig = $hookmanager->resArray;
}
print '<input type="hidden" name="id" value="'.GETPOST('id').'"/>';
echo $r->render($sql, $listViewConfig);

$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

llxFooter('');
$db->close();


function _getObjectOutputField($key, $fk_operationOrder = 0, $val = '')
{
    $operationOrder = getOperationOrderFromCache($fk_operationOrder);
    if(!$operationOrder){return 'error';}

    return $operationOrder->showOutputFieldQuick($key);
}

function _getOvershootStatus($fk_operationOrder = 0)
{
    $operationOrder = getOperationOrderFromCache($fk_operationOrder);
    if(!$operationOrder){return 'error';}

    return $operationOrder->getOvershootStatus();
}

function getOperationOrderFromCache($fk_operationOrder){
    global $db, $TOperationOrderCache;


    if(empty($TOperationOrderCache[$fk_operationOrder])){
        $operationOrder = new OperationOrder($db);
        if($operationOrder->fetch($fk_operationOrder, false) <= 0)
        {
            return false;
        }

        $TOperationOrderCache[$fk_operationOrder] = $operationOrder;
    }
    else{
        $operationOrder = $TOperationOrderCache[$fk_operationOrder];
    }

    return $operationOrder;
}


function _getObjectExtrafieldOutputField($key, $fk_operationOrder = 0)
{
    global $extrafields;

    $operationOrder = getOperationOrderFromCache($fk_operationOrder);
    if(!$operationOrder){return 'error';}

    $value = $operationOrder->array_options["options_".$key];

    return  $extrafields->showOutputField($key, $value);
}

function _getEntity($val = '')
{
    global $db, $TEntityCache;

    if(empty($val)){
        return '';
    }
    $val = intval($val);

    if(empty($TEntityCache[$val])){
        $daoMulticompany = new DaoMulticompany($db);
        if($daoMulticompany->fetch(intval($val)) <= 0)
        {
            return '';
        }

        $TEntityCache[$val] = $daoMulticompany;
    }
    else{
        $daoMulticompany = $TEntityCache[$val];
    }

    return  htmlentities($daoMulticompany->name);
}

function _getOONomURL($fk_operation_order) {
    return OperationOrder::getStaticNomUrl($fk_operation_order);
}
function _getProdNomURL($fk_operationorderdet) {
    global $db;
    if(!empty($fk_operationorderdet)) {
        $ooDet = new OperationOrderDet($db);
        $ooDet->fetch($fk_operationorderdet);
        $ooDet->fetch_product();
        if(!empty($ooDet->product)) return $ooDet->product->getNomUrl();
    }
    return '';
}
function _getUserNomURL($fk_user) {
    global $db;
    if(!empty($fk_user)) {
        $userNomUrl = new User($db);
        $userNomUrl->fetch($fk_user);
        return $userNomUrl->getNomUrl();
    } else return '';
}
