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
dol_include_once('/operationorder/class/operationorder.class.php');
global $db;
$hookmanager->initHooks(array('oorderinterface'));

/*
 * Action
 */
$data = $_POST;
$data['result'] = 0; // by default if no action result is false
$data['errorMsg'] = ''; // default message for errors
$data['msg'] = '';


// do action from GETPOST ...
if(GETPOST('action'))
{
	$action = GETPOST('action');

	if($action=='setOperationOrderlevelHierarchy'){
		if (! $user->rights->operationorder->write){
			$data['result'] = -1; // by default if no action result is false
			$data['errorMsg'] = $langs->trans("ErrorForbidden"); // default message for errors
		}
		else{

			$data['result'] = _updateOperationOrderlevelHierarchy(GETPOST('operation-order-id') , $data['items'],0, $data['errorMsg']);
			if($data['result']>0){
				$data['msg'] =  $langs->transnoentities('Updated') . ' : ' .  $data['result'];
			}
		}
	}
	if($action=='statusRank'){
		require_once __DIR__ . '/../class/operationorderstatus.class.php';
		$data['msg'] = 'UpdateStatus';
		_statusRank($data);
	}
	elseif($action=='getProductInfos' && !empty($user->rights->produit->lire)){
		include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		$productId = GETPOST('fk_product', 'int');


		$product = new Product($db);
		if(!empty($productId) && $product->fetch($productId) > 0)
		{
			$data['result'] = 1;
			$data['fk_default_warehouse'] = $product->fk_default_warehouse;
			$data['price'] = price($product->price);
			$data['duration_unit'] = $product->duration_unit;
			$data['duration_value'] = $product->duration_value;

			$data['time_plannedhour'] = 0;
			$data['time_plannedmin'] = 0;


			if(!empty($product->duration_unit))
			{
				$fk_duration_unit = UnitsTools::getUnitFromCode($product->duration_unit, 'short_label');
				if($fk_duration_unit<1) {
					$data['errorMsg'].=  (!empty($data['errorMsg'])?'<br/>':'').$langs->transnoentities('UnitCodeNotFound', $product->duration_unit);
				}

				if(!empty($product->duration_value) && $fk_duration_unit > 0){
					$fk_unit_hours = UnitsTools::getUnitFromCode('H', 'code');
					if($fk_unit_hours>0) {
						$durationHours = UnitsTools::unitConverteur($product->duration_value, $fk_duration_unit, $fk_unit_hours);

						$data['time_plannedhour'] = floor($durationHours);
						$data['time_plannedmin'] = floor(($durationHours-floor($durationHours)) * 60);
					}
					else{
						$data['errorMsg'].=  (!empty($data['errorMsg'])?'<br/>':'').$langs->transnoentities('UnitCodeNotFound', 'H');
					}

				}
			}

			// pour les hooks avec multicompany et si besoin de faire de traitement en fonction de l'object
			$entity = 0;
			$element = GETPOST('element', 'aZ09');
			$element_id = GETPOST('element_id', 'int');
			$fromObject = false;

			$data['log'][] = 'test element : '.$element.' , element_id '.$element_id;
			if(!empty($element) && !empty($element_id)){
				$fromObject = OperationOrderObjectAutoLoad($element,$db);
				if($fromObject && $fromObject->fetch($element_id) <= 0){
					$data['log'][] = 'OperationOrderObjectAutoLoad fail';
					$fromObject=false;
				}
				else
				{
					$data['log'][] = 'OperationOrderObjectAutoLoad success';
				}
			}

			// Change view from hooks
			$data['log'][] = 'call hook';
			$parameters=array('data' =>& $data, 'entity'=>$entity, 'fromObject' => $fromObject);
			$reshook=$hookmanager->executeHooks('jsonInterface',$parameters,$product, $action);    // Note that $action and $object may have been modified by hook
			if ($reshook < 0){
				$data['result'] = $reshook;  $data['errorMsg'] = $hookmanager->error;
			}elseif ($reshook>0){
				$data = $hookmanager->resArray;
			}

		}
		else{
			$data['result'] = 0;
		}
	}
	if($action=='getFormDialogPlanable') $data['result'] = _getFormDialogPlanable($data['startTime'], $data['endTime'], $data['allDay'], $data['url']);

}

echo json_encode($data);



function _getFormDialogPlanable($startTime, $endTime, $allDay, $url) {
    global $db;
    $TPlanableOO = OperationOrder::getAllOOPlanableLabel();
    $outForm = '<form name="create-supplier-order-form" action="' . $url .'" method="POST">' . "\n";
    $outForm.= '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";
    $outForm.= '<input type="hidden" name="startTime" value="' . $startTime . '">' . "\n";
    $outForm.= '<input type="hidden" name="endTime" value="' . $endTime . '">' . "\n";
    $outForm.= '<input type="hidden" name="allDay" value="' . $allDay . '">' . "\n";
    $outForm.= '<input type="hidden" name="action" value="create-event">' . "\n";
    $form = new Form($db);
    $outForm.= $form->selectarray('operationorder', $TPlanableOO, '',  0,  0,  0,  '',  0,  0,  0,  '',  '', 1);

    $outForm .='</form>';


    return $outForm;
}

/**
 * @param $operationOrderId
 * @param $TItem
 * @param int $parent
 * @param string $errorMsg
 * @param int $updated
 * @return int
 * @throws Exception
 */
function _updateOperationOrderlevelHierarchy($operationOrderId, $TItem, $parent = 0, &$errorMsg = '', &$updated = 0){
	global $db;

	if(!is_array($TItem)){
		$errorMsg.= 'Error : invalid format'."\n";
		return -1;
	}

	if(empty($TItem)){
		return 0;
	}

	foreach ($TItem as $item){
		if(empty($item['id'])){
			$errorMsg.= 'Error : invalid format id missing : '.$item['id']."\n";
			return -1;
		}

		$item['id'] = str_replace("item_", "", $item['id']);
		if(empty($item['id']) || !is_numeric($item['id'])){
			$errorMsg.= 'Error : invalid format id'."\n";
			return -1;
		}

		$item['id'] = intval($item['id']);

		if(!isset($item['order'])){
			$errorMsg.= 'Error : invalid format order missing'."\n";
			return -1;
		}

		$rank = intval($item['order']) + 1;
		if(!empty($item['children']) && is_array($item['children'])){
			$res = _updateOperationOrderlevelHierarchy($operationOrderId, $item['children'], $item['id'] , $errorMsg, $updated );
			if($res<0){
				return -1;
			}
		}

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "operationorderdet SET";
		$sql .= " rang = " . intval($rank). " ";
		$sql .= " WHERE rowid=" . $item['id'];
		$sql .= " AND fk_operation_order=" . $operationOrderId;
		$sql .= " AND fk_parent_line = " . intval($parent); // Vu que le parent ne peut pas être modifié alors on a une erreur

		$db->begin();
		$resql = $db->query($sql);

		dol_syslog(
			"updateOperationOrderlevelHierarchy '" . $sql
			,LOG_ERR
		);


		if($resql>0){
			$db->commit();
			$updated++;
		}
		else{
			$errorMsg.= 'Error : update data base'."\n";
			return -1;
			$db->rollback();
		}
	}

	return $updated;
}



function _statusRank(&$data)
{
	global $langs;
	$TRowOrder= GETPOST('TRowOrder');
	if(is_array($TRowOrder) && !empty($TRowOrder))
	{
		foreach($TRowOrder as $rank => $value)
		{
			$rowid= intval($value);
			$rank = intval($rank);

			if($rowid>0)
			{
				OperationOrderStatus::updateRank($rowid,$rank);
			}
			else{
				$data['errorMsg'] = $langs->trans('StatusNotFound'); // default message for errors
			}
		}
		$data['result'] = 1;
		return;
	}
	else{
		$data['errorMsg'] = $langs->trans('StatusOrderListEmpty'); // default message for errors
	}
}
