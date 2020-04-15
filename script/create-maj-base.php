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
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */

if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);

	require '../config.php';
} else {
	global $db;
}
global $langs;

// AGENDA TRIGGERS
// Get max rank triggers available
$sqlAgenda = 'SELECT MAX(rang) as maxRank';
$sqlAgenda.= ' FROM '.MAIN_DB_PREFIX.'c_action_trigger';
$sqlAgenda.= ' WHERE code NOT IN (\'WEBHOST_STATUS\', \'WEBINSTANCE_STATUS\') LIMIT 1';
$agendaTriggerRank=0;
$resql=$db->query($sqlAgenda);
if ($resql){
	$num = $db->num_rows($resql);
	if($num > 0){
		$obj = $db->fetch_object($resql);
		$agendaTriggerRank = $obj->maxRank;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}
$TAgendaTriggers = array();

$agendaTriggerRank ++;
$TAgendaTriggers[] = array(
	'code' => 'OPERATIONORDER_STATUS', // This trigger does not exist but it used to trigger event creation by object imself, search this code with MAIN_AGENDA_ACTIONAUTO_ prefix
	'label' => $langs->transnoentities('OperationOrderStatusChange'),
	'description' => $langs->transnoentities('OperationOrderStatusChangeDesc'),
	'elementtype' => 'operationorder',
	'rang' => $agendaTriggerRank
);


foreach ($TAgendaTriggers as $agendaTrigger){

	// check if agenda trigger conf already exist before add it
	$sqlAgenda = 'SELECT COUNT(*) as alreadyExists  FROM '.MAIN_DB_PREFIX.'c_action_trigger as a WHERE  a.code = \''.$db->escape($agendaTrigger['code']).'\' LIMIT 1';
	$resql=$db->query($sqlAgenda);
	if ($resql){
		$obj = $db->fetch_object($resql);

		if(empty($obj->alreadyExists)){
			$sqlAgenda = 'insert into '.MAIN_DB_PREFIX.'c_action_trigger (code,label,description,elementtype,rang)';
			$sqlAgenda.= ' values (\''.$agendaTrigger['code'].'\',\''.$db->escape($agendaTrigger['label']).'\',\''.$db->escape($agendaTrigger['description']).'\',\''.$agendaTrigger['elementtype'].'\','.$agendaTrigger['rang'].');';
			dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_'.$agendaTrigger['code'], 1, 'chaine', 0, '', $conf->entity);
		}
		else{
			$sqlAgenda = 'UPDATE '.MAIN_DB_PREFIX.'c_action_trigger SET ';
			$Tfields = array();
			foreach ($agendaTrigger as $key => $value){
				$Tfields[] = $key.' = \''.$this->db->escape($value).'\'';
			}
			$sqlAgenda.= implode(', ', $Tfields);
			$sqlAgenda.= ' WHERE code = \''.$this->db->escape($agendaTrigger['code']).'\' ';
		}
		$resqlsave=$db->query($sqlAgenda);

		if(!$resqlsave){
			dol_print_error($db, 'UPDATE/SAVE AGENDA TRIGGER');
		}

	}
	$db->free($resql);
}

dol_include_once('/operationorder/class/operationorder.class.php');

$o=new OperationOrder($db);
$o->init_db_by_vars();

$o=new OperationOrderDet($db);
$o->init_db_by_vars();

$o=new OperationOrderDictType($db);
$o->init_db_by_vars();

$o=new OperationOrderStatus($db);
$o->init_db_by_vars();

$o=new OperationOrderStatusUserGroupRight($db);
$o->init_db_by_vars();

$o=new OperationOrderStatusTarget($db);
$o->init_db_by_vars();

// Multientity patch
$db->query("UPDATE '.MAIN_DB_PREFIX.'operationorder_status SET entity = '1' WHERE `entity` = 0;");
