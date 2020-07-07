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
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/doc/tcpdfbarcode.modules.php';
dol_include_once('/operationorder/class/usergroupoperationorder.class.php');
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
	if ($action == 'logged-status')
	{
		$data['msg'] = 'ok';
	}
	else if ($action == "getUserList")
	{
		$data['users'] = array();

		if (empty($conf->global->OPERATION_ORDER_GROUPUSER_DEFAULTPLANNING))
		{
			$data['errorMsg'] = $langs->trans('ErrorNoGroupForPlanning');

		}
		else
		{
			$userGroup = new OperationOrderUserPlanning($db);
			$retgroup = $userGroup->fetch($conf->global->OPERATION_ORDER_GROUPUSER_DEFAULTPLANNING);
			if ($retgroup > 0)
			{
				$userList = $userGroup->listUsersForGroup();
				if (!empty($userList))
				{
					foreach ($userList as $u)
					{
						$data['users'][] = $u->login;
					}
				}
				else
				{
					$data['errorMsg'] = $langs->trans('ErrorNoUserInGroupForPlanning');
				}
				$data['result'] = 1;
			}
		}
	}

	else if ($action == "getActionsList")
	{
		$data['actions'] = array(
			array(
				'Annulation',
				'IMPAnnul',
				displayBarcode('IMPAnnul')
			),
			array(
				'Fin de journée',
				'IMPFin',
				displayBarcode('IMPFin')
			)
		);

		$barcode=new OperationOrderBarCode($db);
		$TBarCodes = $barcode->fetchAll('', '', array('entity' => $conf->entity));
		$data['debug'] = $TBarCodes;

		if (!empty($TBarCodes))
		{
			foreach ($TBarCodes as $improd) {
				$data['actions'][] = array($improd->label, $improd->code, displayBarcode($improd->code));
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
						,'bars' => displayBarcode('OR'.$oOrder->ref)
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

		$data['oOrderLines'] = array();

		if (!empty($OR->lines))
		{
			$TPointable = $ProdErrors = $TLastLines = array();

			$alreadyUsed = array();
			$sql = "SELECT mvt.fk_product, SUM(mvt.value) as total FROM ".MAIN_DB_PREFIX."stock_mouvement as mvt";
			$sql.= " WHERE mvt.origintype = 'operationorder'";
			$sql.= " AND mvt.fk_origin = ".$OR->id;
			$sql.= " GROUP BY mvt.fk_product";

			$resql = $db->query($sql);
			if ($resql)
			{
				while ($obj = $db->fetch_object($resql))
				{
					$alreadyUsed[$obj->fk_product] = abs($obj->total);
				}
			}

			// récupération de la dernière ligne de chaque produit pour affichage sortie de stock
			foreach ($OR->lines as $line)
			{
				if ($line->fk_product)
				{
					$TLastLines[$line->fk_product] = $line->id;
				}
			}

			foreach ($OR->lines as $line)
			{
//				$data['debug'][] = $line->fk_product;

				if ($line->fk_product && ! array_key_exists(intval($line->fk_product), $TPointable))
				{
					$TPointable[$line->fk_product] = false;
					$line->fetch_product();

					if ($line->product->array_options['options_or_scan'] == "1")
					{
						$TPointable[$line->fk_product] = true;
					}
					else if ($line->product->type == Product::TYPE_SERVICE) continue;

					$ProdErrors[$line->fk_product] = '';

					if (empty($conf->global->STOCK_SUPPORTS_SERVICES) && $line->product->type == Product::TYPE_SERVICE && !$TPointable[$line->fk_product])
						$ProdErrors[$line->fk_product].=$langs->trans('ErrorStockMVTService')."<br />";

					if (empty($line->product->barcode))
						$ProdErrors[$line->fk_product].=$langs->trans('ErrorProductHasNoBarCode')."<br />";

					if (empty($line->product->fk_default_warehouse))
						$ProdErrors[$line->fk_product].=$langs->trans('ErrorNoDefaultWarehouse')."<br />";

				}

				$used = 0;
				if (isset($alreadyUsed[$line->fk_product]))
				{
					if ($alreadyUsed[$line->fk_product] > $line->qty)
					{
						if ($TLastLines[$line->fk_product] != $line->id)
						{
							$used = $line->qty;
							$alreadyUsed[$line->fk_product] -= $line->qty;
						}
						else
						{
							$used = $alreadyUsed[$line->fk_product];
							unset($alreadyUsed[$line->fk_product]);
						}
					}
					else
					{
						$used = $alreadyUsed[$line->fk_product];
						unset($alreadyUsed[$line->fk_product]);
					}
				}

				$data['oOrderLines'][] = array(
					'ref' 		=> $line->product_ref
					,'qty' 		=> $line->qty
					,'qtyUsed'	=> $used
					,'action' 	=> $TPointable[$line->fk_product] ? "Démarrer" : (empty($ProdErrors[$line->fk_product]) ? "Sortie de stock" : $ProdErrors[$line->fk_product])
					,'barcode' 	=> 'LIG'.$line->id
					,'bars'		=> $TPointable[$line->fk_product] ? displayBarcode('LIG'.$line->id) : ""
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
			$ret = $counter->update($usr);
			if ($ret > 0 && $counter->fk_orDet > 0)
			{
				// mise à jour du temps passé sur la ligne pointable
				$ordet = new OperationOrderDet($db);
				$ordet->fetch($counter->fk_orDet);

				$ordet->time_spent += $counter->task_duration;
				$ordet->update($usr);

				$remaining = $counter->remainingCountersForOR($ordet->id);
				// changement de statut de l'OR de la ligne
				if (!empty($conf->global->OPORDER_CHANGE_OR_STATUS_ON_STOP) && !empty($conf->global->OPODER_STATUS_ON_STOP) && !$remaining)
				{
					list($changeReturn, $message) = changeORStatus($ordet->fk_operation_order, $conf->global->OPODER_STATUS_ON_STOP);
					if ($changeReturn) $data['msg'].=$message;
					else $data['errorMsg'].=$message;
				}
			}
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
			$data['msg'].= $langs->trans('MsgCounterStart', $label, $usr->login);
			$data['result'] = 1;
		}
		else
		{
			$data['errorMsg'].= $langs->trans('ErrorCounterStart');
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
			$retupd = $counter->update($usr);

			if ($retupd > 0)
			{
				if ($counter->fk_orDet > 0)
				{
					// mise à jour du temps passé sur la ligne pointable
					$ordet = new OperationOrderDet($db);
					$ordet->fetch($counter->fk_orDet);

					$ordet->time_spent += $counter->task_duration;
					$ordet->update($usr);

					$remaining = $counter->remainingCountersForOR($ordet->id);
					// changement de statut de l'OR de la ligne
					if (!empty($conf->global->OPORDER_CHANGE_OR_STATUS_ON_STOP) && !empty($conf->global->OPODER_STATUS_ON_STOP) && !$remaining)
					{
						list($changeReturn, $message) = changeORStatus($ordet->fk_operation_order, $conf->global->OPODER_STATUS_ON_STOP);
						if ($changeReturn) $data['msg'].=$message;
						else $data['errorMsg'].=$message;
					}
				}
				$data['msg'].= $langs->trans('MsgCounterStop', $counter->label, $usr->login);
				$data['result'] = 1;
			}
			else
			{
				$data['errorMsg'] = $langs->trans('ErreurCounterStop', $counter->label, $usr->login);
			}
		}
		else if ($ret == 0) {
			$data['msg'].= $langs->trans('noCounterToStop');
			$data['result'] = 1;
		}
		else
		{
			$data['errorMsg'] = $langs->trans('ErrorCurrentCounterStop', $usr->login);
		}


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
			$data['errorMsg'] = $langs->trans("ErrorCounterInvalidUser");
		}
		else if ($OR->id != $line->fk_operation_order)
		{
			$data['errorMsg'] = $langs->trans("ErreurCounterInvalidLineSelected");
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
				$ret = $counter->update($usr);

				if ($ret > 0 && $counter->fk_orDet > 0)
				{
					// mise à jour du temps passé sur la ligne pointable
					$ordet = new OperationOrderDet($db);
					$ordet->fetch($counter->fk_orDet);

					$ordet->time_spent += $counter->task_duration;
					$ordet->update($usr);

					$remaining = $counter->remainingCountersForOR($ordet->id);
					// changement de statut de l'OR de la ligne
					if (!empty($conf->global->OPORDER_CHANGE_OR_STATUS_ON_STOP) && !empty($conf->global->OPODER_STATUS_ON_STOP) && !$remaining)
					{
						list($changeReturn, $message) = changeORStatus($ordet->fk_operation_order, $conf->global->OPODER_STATUS_ON_STOP);
						if ($changeReturn) $data['msg'].=$message;
						else $data['errorMsg'].=$message;
					}
				}

				$data['debug'].= 'stop counter '.$counter->label.' '.$counter->id;
			}

			$remaining = $counter->remainingCountersForOR($line->id);

			$newCounter = new OperationOrderTaskTime($db);
			$newCounter->label = $line->label;
			$newCounter->task_datehour_d = dol_now();
			$newCounter->fk_user = $usr->id;
			$newCounter->fk_orDet = $line->id;
			$newCounter->entity = $conf->entity;

			$retSave = $newCounter->save($usr);
			if ($retSave > 0)
			{
				// s'il y a déjà des compteurs en court sur l'OR, on a déjà changé le statut
				// changement de statut de l'OR de la ligne
				if (!empty($conf->global->OPORDER_CHANGE_OR_STATUS_ON_START) && !empty($conf->global->OPODER_STATUS_ON_START) && !$remaining)
				{
					list($changeReturn, $message) = changeORStatus($line->fk_operation_order, $conf->global->OPODER_STATUS_ON_START);
					$data['debug'] = $changeReturn . ' | ' . $message;
					if ($changeReturn) $data['msg'].=$message;
					else $data['errorMsg'].=$message;
				}

				$data['debug'].= 'start counter '.$newCounter->label.' '.$newCounter->id;
				$data['msg'].= $langs->trans('MsgCounterStart', $newCounter->label, $usr->login);
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

				$alreadyUsed = array();
				$sql = "SELECT mvt.fk_product, SUM(mvt.value) as total FROM ".MAIN_DB_PREFIX."stock_mouvement as mvt";
				$sql.= " WHERE mvt.origintype = 'operationorder'";
				$sql.= " AND mvt.fk_origin = ".$OR->id;
				$sql.= " GROUP BY mvt.fk_product";

				$resql = $db->query($sql);
				if ($resql)
				{
					while ($obj = $db->fetch_object($resql))
					{
						$alreadyUsed[$obj->fk_product] = abs($obj->total);
					}
				}

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
							$data['errorMsg'] = $langs->trans('ErrorStockMVTService');
						}
						else
						{
							if (empty($prod->fk_default_warehouse)) $data['errorMsg'] = $langs->trans('ErrorNoDefaultWarehouse');
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

								$qtyAfterMvt = (float) $alreadyUsed[$prod->id] + (float) $qty;
								if ($qtyAfterMvt > $prodTotalQty && !empty($conf->global->OPODER_CANT_EXCEED_SENT_QTY))
								{
									$data['errorMsg'] = $langs->trans('ErrorProductqtyExceded');
								}
								else
								{
									$mvt->livraison($user, $prod->id, $prod->fk_default_warehouse, $qty, 0, $langs->trans('productUsedForOorder', $OR->ref));
									$data['result'] = 1;
									$data['msg'] = $langs->trans('StockMouvementGenerated', $prod->ref);
								}

							}

						}
					}
					else
					{
						// le produit n'existe pas, on le crée si la conf est activée
						if (!empty($conf->global->OPODER_ADD_PRODUCT_IN_OR_IF_MISSING))
						{
							if ($prod->fk_default_warehouse <= 0) $prod->fk_default_warehouse = 0;

							if (empty($prod->fk_default_warehouse)) $data['errorMsg'] = $langs->trans('ErrorCannotAddProductNoDefaultWarehouse', $prod->ref, $OR->ref);
							else
							{
								$ret = $OR->addline('',1, $prod->price, $prod->fk_default_warehouse, 1, 0, 0, $prod->id);
								if ($ret > 0)
								{
									// une fois le produit ajouté, on fait la sortie de stock
									$data['msg'] = $langs->trans('ProductAddedToOR', $prod->ref, $OR->ref);

									$mvt = new MouvementStock($db);
									$mvt->origin = $OR;

									$qty = 1;

									if (!empty($conf->global->PRODUCT_USE_UNITS))
									{
										if (!empty($prod->fk_unit) && $prod->fk_unit != 1) // pièce
											$qty = $prodTotalQty;
									}

									$mvt->livraison($user, $prod->id, $prod->fk_default_warehouse, $qty, 0, $langs->trans('productUsedForOorder', $OR->ref));
									$data['result'] = 1;
									$data['msg'].= '<br />'.$langs->trans('StockMouvementGenerated', $prod->ref);
								}
								else
								{
									$data['errorMsg'] = $langs->trans('ErrorAddProductInOR', $prod->ref, $OR->ref);
								}
							}
						}
						else
						{
							$data['errorMsg'] = $langs->trans('ErrorProductMissingInOR', $prod->ref, $OR->ref);
						}
					}
				}
			}
			else
			{
				$data['errorMsg'] = $langs->trans('ErrorCantFetchOR');
			}
		}
		else
		{
			$data['errorMsg'] = $langs->trans('ErrorNoProdWithThisBarcode', $prod_barcode);
		}

	}

}

print json_encode($data);

function displayBarcode($code = '')
{
	global $db;

	$moduleBarcode = new modTcpdfbarcode($db);

	// Build barcode on disk (not used, this is done to make debug easier)
	$result = $moduleBarcode->writeBarCode($code, 'C128', 'Y');
	// Generate on the fly and output barcode with generator
	$url = DOL_URL_ROOT.'/viewimage.php?modulepart=barcode&amp;generator=tcpdfbarcode&amp;code='.urlencode($code).'&amp;encoding=C128';
	//print $url;
	$barcode =  '<img src="'.$url.'" title="'.$code.'" border="0">';

	return $barcode;
}

function changeORStatus($or_id, $fk_status)
{
	global $user, $db, $langs;

	$return = false;
	$message = "";

	$OR = new OperationOrder($db);
	$OR->fetch($or_id);

	$ret = $OR->setStatus($user, $fk_status);
	if ($ret > 0)
	{
		$OR->loadStatusObj();
		$message.= $langs->trans('OperationOrderSetStatus', $OR->objStatus->label, $OR->ref).'<br />';
		$return = true;
	}
	else
	{
		$message.=$OR->error.'<br />';
	}

	return array($return, $message);
}
