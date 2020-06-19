<?php

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once __DIR__ . '/../class/unitstools.class.php';
require_once __DIR__ . '/../lib/operationorder.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
dol_include_once('/operationorder/class/operationorder.class.php');
dol_include_once('/operationorder/class/operationordertasktime.class.php');
global $db;
$hookmanager->initHooks(array('oordermanagerinterface'));

/*
 * Action
 */
$data = $_POST;
$data['result'] = 0; // by default if no action result is false
$data['errorMsg'] = ''; // default message for errors
$data['msg'] = '';

$action = GETPOST('action');

$parameters = array('data' => &$data);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some

if (empty($reshook) && !empty($action))
{
	if ($action == "getUserList")
	{
		if (empty($conf->global->OPERATION_ORDER_GROUPUSER_DEFAULTPLANNING))
		{
			$data['errorMsg'] = "no usergroup for planning defined";

		}
		else
		{
			$userGroup = new UserGroup($db);
			$retgroup = $userGroup->fetch($conf->global->OPERATION_ORDER_GROUPUSER_DEFAULTPLANNING);
			if ($retgroup > 0)
			{
				$userList = $userGroup->listUsersForGroup();
				if (!empty($userList))
				{
					$data['users'] = array();

					foreach ($userList as $u)
					{
						$data['users'][] = $u->login;
					}
				}
				$data['result'] = 1;
			}
		}
	}

	else if ($action == "getActionsList")
	{
		// TODO récupérer les actions improd venant du dev de Lena
		$data['actions'] = array(array('Annulation', 'IMPAnnul'), array('Toilette', 'IMPToilette'), array('Fin de journée', 'IMPFin'));
		$data['result'] = 1;
	}

	else if ($action == "getORList")
	{
		$sql = "SELECT DISTINCT ooa.fk_operationorder FROM ".MAIN_DB_PREFIX."operationorderaction ooa";
		$sql.= " WHERE ooa.datef >= '".date("Y-m-d 00:00:00")."'";
		$sql.= " AND ooa.dated <= '".date("Y-m-d 23:59:59")."'";

		$data['oOrders']=array();

		$resql = $db->query($sql);
		if (!$resql)
		{
			$data['errorMsg'] = $db->lasterror;
		}
		else
		{
			$i = 0;
			while ($obj = $db->fetch_object($resql))
			{
				$oOrder = new OperationOrder($db);
				$oOrder->fetch($obj->fk_operationorder);

				if ($oOrder->id)
				{
					$data['oOrders'][$i] = array(
						'client' => $oOrder->thirdparty->name
						,'ref'=>$oOrder->ref
						,'barcode' => 'OR'.$oOrder->ref
					);

					if ($conf->dolifleet->enabled)
					{
						$data['oOrders'][$i]['immat'] = '';

						if (isset($oOrder->array_options['options_fk_dolifleet_vehicule']))
						{
							$sqlVeh = "SELECT immatriculation FROM ".MAIN_DB_PREFIX."dolifleet_vehicule";
							$sqlVeh.= " WHERE rowid = ". $oOrder->array_options['options_fk_dolifleet_vehicule'];
							$resqlVeh = $db->query($sqlVeh);

							if ($resqlVeh && $db->num_rows($resql)) $obj = $db->fetch_object($resqlVeh);
							$data['oOrders'][$i]['immat'] = $obj->immatriculation;
						}
					}

					$data['result'] = 1;
				}

				$i++;
			}
		}
	}

	else if ($action == "getORLines")
	{
		$orBarcode = GETPOST('or_barcode');
		$orRef = substr($orBarcode, 2);
		$OR = new OperationOrder($db);
		$OR->fetchBy($orRef, 'ref');
		$OR->fetchLines();

		if (!empty($OR->lines))
		{
			$TPointable = array();
			foreach ($OR->lines as $line)
			{
//				$data['debug'][] = $line->fk_product;
				if ($line->fk_product && ! array_key_exists(intval($line->fk_product), $TPointable))
				{
					$TPointable[$line->fk_product] = false;
					$line->fetch_product();
					$data['debug'][$line->fk_product] = $line->product->array_options;
					if ($line->product->array_options['options_or_scan'] == "1")
					{
						$TPointable[$line->fk_product] = true;
					}
				}

				$data['oOrderLines'][] = array(
					'ref' 		=> $line->product_ref
					,'qty' 		=> $line->qty
					,'action' 	=> $TPointable[$line->fk_product] ? "Démarrer" : "Sortie de stock"
					,'barcode' 	=> 'LIG'.$line->id
					,'pointable'=> $TPointable[$line->fk_product]
				);
			}
		}
		$data['result'] = 1;
	}

	else if ($action == 'startImprod')
	{
		$data['debug'] = '';
		$u = GETPOST('user'); // code barre user USR{login}
		$improd = GETPOST('improd'); // IMP{libelléImprod}

		$usr = new User($db);
		$usr->fetch('', substr($u, 3));

		// stop le compteur courant de l'utilisateur
		$counter = new OperationOrderTaskTime($db);
		$ret = $counter->fetchCourantCounter($usr->id);
		if ($ret > 0)
		{
			$counter->task_datehour_f = dol_now();
			$counter->task_duration = $counter->task_datehour_f - $counter->task_datehour_d;
			$counter->update($usr);
			$data['debug'].= 'stop counter '.$counter->label.' '.$counter->id;
		}

		// start compteur improd
		$newCounter = new OperationOrderTaskTime($db);
		$newCounter->label = substr($improd, 3);
		$newCounter->task_datehour_d = dol_now();
		$newCounter->fk_user = $usr->id;
		$newCounter->entity = $conf->entity;

		$newCounter->save($usr);
		$data['debug'].= 'start counter '.$newCounter->label.' '.$newCounter->id;
		$data['msg'] = "Compteur " . substr($improd, 3) . " démarré pour l'utilisateur ".$usr->login;
		$data['result'] = 1;

	}

	else if ($action == 'stopUserWork')
	{
		$u = GETPOST('user'); // code barre user USR{login}

		$usr = new User($db);
		$usr->fetch('', substr($u, 3));

		// stop le compteur courant de l'utilisateur
		$counter = new OperationOrderTaskTime($db);
		$ret = $counter->fetchCourantCounter($usr->id);

		if ($ret > 0)
		{
			$counter->task_datehour_f = dol_now();
			$counter->task_duration = $counter->task_datehour_f - $counter->task_datehour_d;
			$counter->update($usr);
		}
		$data['msg'] = "Compteur " . $counter->label . " arrété pour l'utilisateur ".$usr->login;
		$data['result'] = 1;
	}


}

print json_encode($data);
