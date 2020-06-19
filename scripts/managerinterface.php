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
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
dol_include_once('/operationorder/class/operationorder.class.php');
dol_include_once('/operationorder/class/operationordertasktime.class.php');
dol_include_once('/operationorder/class/operationorderbarcode.class.php');
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
		$data['actions'] = array(array('Annulation', 'IMPAnnul'), array('Fin de journée', 'IMPFin'));

		$barcode=new OperationOrderBarCode($db);
		$TBarCodes = $barcode->fetchAll('', '', array('entity' => $conf->entity));
		$data['debug'] = $TBarCodes;

		if (!empty($TBarCodes))
		{
			foreach ($TBarCodes as $improd) {
				$data['actions'][] = array($improd->label, $improd->code);
			}
		}

		$data['result'] = 1;
	}

	else if ($action == "getORList")
	{
		$data['courantTask'] = ''; // tâche courante de l'utilisateur

		$u = GETPOST('user'); // code barre user USR{login}
		$usr = new User($db);
		$usr->fetch('', substr($u, 3));

		$counter = new OperationOrderTaskTime($db);
		$ret = $counter->fetchCourantCounter($usr->id);
		if ($ret > 0)
		{
			$data['courantTask'] = $counter->label;
			if (!empty($counter->fk_orDet))
			{
				$sql = "SELECT oorder.ref FROM ".MAIN_DB_PREFIX."operationorder oorder";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."operationorderdet ordet ON ordet.fk_operation_order = oorder.rowid";
				$sql.= " WHERE ordet.rowid = ".$counter->fk_orDet;

				$resql = $db->query($sql);
				if ($resql)
				{
					$obj = $db->fetch_object($resql);
					if (!empty($obj->ref)) $data['courantTask'].= ' ('.$obj->ref.')';
				}

			}
		}

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
//		$data['debug'] = '';
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
//			$data['debug'].= 'stop counter '.$counter->label.' '.$counter->id;
		}

		$label = substr($improd, 3);
		if (is_numeric($label))
		{
			$impbarcode = new OperationOrderBarCode($db);
			$retImp = $impbarcode->fetchBy($improd, 'code');
			$data['debug'] = $impbarcode->label;
			if($retImp > 0)
			{
				$label = $impbarcode->label;
			}
		}

		// start compteur improd
		$newCounter = new OperationOrderTaskTime($db);
		$newCounter->label = $label;
		$newCounter->task_datehour_d = dol_now();
		$newCounter->fk_user = $usr->id;
		$newCounter->entity = $conf->entity;

		$retSave = $newCounter->save($usr);
		if ($retSave > 0)
		{
//			$data['debug'].= 'start counter '.$newCounter->label.' '.$newCounter->id;
			$data['msg'] = "Compteur " . $label . " démarré pour l'utilisateur ".$usr->login;
			$data['result'] = 1;
		}
		else
		{
			$data['errorMsg'].= 'error start counter';
		}

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

	else if ($action == 'startLineCounter')
	{
		$orBarcode = GETPOST('or_barcode');
		$orRef = substr($orBarcode, 2);

		$OR = new OperationOrder($db);
		$ret = $OR->fetchBy($orRef, 'ref');

		$u = GETPOST('user');

		$usr = new User($db);
		$usr->fetch('', substr($u, 3));

		$lig = GETPOST('lig');
		$lineId = substr($lig, 3);
		$line = new OperationOrderDet($db);
		$line->fetch($lineId);

		if (!$usr->id)
		{
			$data['errorMsg'] = "Invalid user to start counter";
		}
		else if ($OR->id != $line->fk_operation_order)
		{
			$data['errorMsg'] = "Error : selected line is not from selected OR";
		}
		else
		{
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

			$newCounter = new OperationOrderTaskTime($db);
			$newCounter->label = $line->label;
			$newCounter->task_datehour_d = dol_now();
			$newCounter->fk_user = $usr->id;
			$newCounter->fk_orDet = $line->id;
			$newCounter->entity = $conf->entity;

			$retSave = $newCounter->save($usr);
			if ($retSave > 0)
			{
				$data['debug'].= 'start counter '.$newCounter->label.' '.$newCounter->id;
				$data['msg'] = "Compteur " . $newCounter->label . " démarré pour l'utilisateur ".$usr->login;
				$data['result'] = 1;
			}
		}
	}
	else if ($action == 'stockMouvement')
	{
		$or_barcode = GETPOST('or_barcode');
		$lig = GETPOST('lig');

		$prod_barcode = GETPOST('prod');
		$prod = new Product($db);
		$ret = $prod->fetch('','','', $prod_barcode);
//		$data['debug'] = $prod->id;
		if ($ret > 0)
		{
			$orRef = substr($or_barcode, 2);

			$OR = new OperationOrder($db);
			$ret = $OR->fetchBy($orRef, 'ref');
			if ($ret > 0)
			{
				$OR->fetchLines();
				if (!empty($OR->lines))
				{
					$prodTotalQty = 0;
					$found = false;
					foreach ($OR->lines as $line)
					{
						if ($line->fk_product == $prod->id) {

							$found = true;
							$prodTotalQty+=$line->qty;
//							$data['debug'] = $line;
						}
					}

					if ($found)
					{
//						$data['debug'] = "product found";

						if (empty($conf->global->STOCK_SUPPORTS_SERVICES) && $prod->type == Product::TYPE_SERVICE)
						{
							$data['errorMsg'] = 'Error : product provided is a service and stock doesn\'t supports services';
						}
						else
						{
							if (empty($prod->fk_default_warehouse)) $data['errorMsg'] = 'Error : no default warehouse for this product';
							else
							{
								// création de mouvement de stock
								$mvt = new MouvementStock($db);
								$mvt->origin = $OR;

								$qty = 1;

								if (!empty($conf->global->PRODUCT_USE_UNITS))
								{
									if (!empty($prod->fk_unit) && $prod->fk_unit != 1) // pièce
										$qty = $prodTotalQty;
								}

								$mvt->livraison($user, $prod->id, $prod->fk_default_warehouse, $qty, 0, $langs->trans('productUsedForOorder', $OR->ref));

							}

						}
					}
				}
			}
			else
			{
				$data['errorMsg'] = 'Can\' fetch OR';
			}
		}
		else
		{
			$data['errorMsg'] = 'Can\' fetch product with barcode '.$prod_barcode;
		}

	}

}

print json_encode($data);

