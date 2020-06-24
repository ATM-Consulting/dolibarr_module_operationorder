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

	if ($action == "getPlannedOperationOrder") {
		// Parse the start/end parameters.
		// These are assumed to be ISO8601 strings with no time nor timeZone, like "2013-12-29".
		// Since no timeZone will be present, they will parsed as UTC.

		$timeZone = GETPOST('timeZone');
		$eventsType = GETPOST('eventsType');
		$range_start = OO_parseFullCalendarDateTime(GETPOST('start'), $timeZone);
		$range_end = OO_parseFullCalendarDateTime(GETPOST('end'), $timeZone);

		if($eventsType == 'dayOff'){
			$data = _getJourOff($range_start->getTimestamp(), $range_end->getTimestamp());
		}
		else
		{
			$data = _getOperationOrderEvents($range_start->getTimestamp(), $range_end->getTimestamp(), $eventsType);
		}


		$parameters=array();
		$reshook=$hookmanager->executeHooks('jsonInterface',$parameters,$data, $action);    // Note that $action and $object may have been modified by hook
		if ($reshook < 0){
			// pas de gestion d'erreur pour l'instant pour cet action
		}elseif ($reshook>0){
			$data = $hookmanager->resArray;
		}

		print json_encode($data);
		exit;
	}
	elseif($action == 'getBusinessHours'){

        $beginOfWeek = GETPOST('beginOfWeek');
        $endOfWeek = GETPOST('endOfWeek');

        $data = getOperationOrderUserPlanningSchedule($beginOfWeek,  $endOfWeek);

        print json_encode($data);
        exit;

    }
	elseif($action=='setOperationOrderlevelHierarchy'){
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
	elseif($action=='statusRank'){
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
	if($action=='getTableDialogPlanable') $data['result'] = _getTableDialogPlanable($data['startTime'], $data['endTime'], $data['allDay'], $data['url'], '', '', $data['beginOfWeek'], $data['endOfWeek']);
	elseif($action=='updateOperationOrderAction') $data['result'] = _updateOperationOrderAction($data['startTime'], $data['endTime'], $data['fk_action'], $data['action'], $data['allDay']);
}

echo json_encode($data);



function _getTableDialogPlanable($startTime, $endTime, $allDay, $url, $id = 'create-operation-order-action', $action = 'create-operation-order-action', $beginOfWeek=0, $endOfWeek=0) {
    global $db, $langs, $hookmanager;

    $TPlanableOO = OperationOrder::getPlannableOperationOrder();
	$TPlanableOOOptions = array();
    if(!empty($TPlanableOO)){
    	foreach ($TPlanableOO as $key => $operationOrder){
			$TPlanableOOOptions[$operationOrder->id] = $operationOrder->ref . ' ' . $operationOrder->thirdparty->name;
		}
	}

    $out= '<table id="'.$id.'" class="table" style="width:800px;" >';

    $out.= '<thead>';

    $out.= '<tr>';
    $out.= ' <th class="text-center" >'.$langs->trans('Ref').'</th>';
    $out.= ' <th class="text-center" >'.$langs->trans('RefCustomer').'</th>';
    $out.= ' <th class="text-center"  >'.$langs->trans('Module1Name').'</th>';
    $out.= ' <th class="text-center" >'.$langs->trans('TimePlannedTheoretical').'</th>';
    $out.= ' <th class="text-center" >'.$langs->trans('TimePlannedForced').'</th>';
    $out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';

    $parameters = array(
        'out' =>& $out
    );
    $reshook=$hookmanager->executeHooks('addOperationorderPlannableTableTitle',$parameters,$object, $action);
    if($reshook < 0) return -1;


    $out.= '</tr>';

    $out.= '</thead>';

    $out.= '<tbody>';

    foreach ($TPlanableOO as $operationOrder)
    {

        $out.= '<tr>';

        //ref OR
        $url = DOL_URL_ROOT . "/custom/operationorder/operationorder_planning.php";
        $out.= ' <td data-order="'.$operationOrder->ref.'" data-search="'.$operationOrder->ref.'"  ><a href="'.$url.'?action=createOperationOrderAction&operationorder='.$operationOrder->id.'&startTime='.$startTime.'&endTime='.$endTime.'&endOfWeek='.$endOfWeek.'&beginOfWeek='.$beginOfWeek.'">'.$operationOrder->ref.'</a></td>';

        //ref client
        //TODO : ajout lien vers planning page avec action pour ajouter l'événement
        $out.= ' <td data-order="'.$operationOrder->ref_client.'" data-search="'.$operationOrder->ref_client.'"  >'.$operationOrder->ref_client.'</td>';

        //Nom Client
        $soc = new Societe($db);
        $res = $soc->fetch($operationOrder->fk_soc);
        if ($res < 0) return -1;
        $out.= ' <td data-order="'.$soc->name.'" data-search="'.$soc->name.'"  >'.$soc->name.'</td>';

        //durée théorique et forcée
        $out.= ' <td>'.convertSecondToTime($operationOrder->time_planned_t).'</td>';
        $out.= ' <td>'.convertSecondToTime($operationOrder->time_planned_f).'</td>';

        $out.= ' <td>'.$operationOrder->getLibStatut().'</td>';

        $parameters = array(
            'out' =>& $out,
            'operationOrder' => $operationOrder
        );
        $reshook=$hookmanager->executeHooks('addOperationorderPlannableTableField',$parameters,$object, $action);
        if($reshook < 0) return -1;

        $out.= '</tr>';
    }
    $out.= '</tbody>';

    $out.= '</table>';

    $out.= '<script src="'. DOL_URL_ROOT .'/custom/operationorder/vendor/data-tables/datatables.min.js"></script>';
    $out.='<script src="'.DOL_URL_ROOT.'/custom/operationorder/vendor/data-tables/jquery.dataTables.min.js"></script>';

    $out.= '<script type="text/javascript" >
					$(document).ready(function(){

					    $("#' . $id . '").DataTable({
						"pageLength" : 10,
						"language": {
							"url": "'.DOL_URL_ROOT.'/custom/operationorder/vendor/data-tables/french.json"
						},
						responsive: true
					});

					});
			   </script>';

    return $out;
}

function _updateOperationOrderAction($startTime, $endTime, $fk_action, $action,  $allDay){
    global $db, $user;

    dol_include_once('/operationorder/class/operationorder.class.php');
    dol_include_once('/operationorder/class/operationorderaction.class.php');

    if($action == 'drop')
    {
        $db->begin();
        $action_or = new OperationOrderAction($db);
        $res = $action_or->fetch($fk_action);
        if ($res > 0)
        {
            $action_or->dated = $startTime;
            $action_or->datef = $endTime;
            if (!empty($allDay)) $action_or->fullday = 1;
            $res = $action_or->save($user);
            if ($res > 0)
            {
                $or = new OperationOrder($db);
                $res = $or->fetch($action_or->fk_operationorder);
                if (empty($or->array_options)) $or->fetch_optionals();
                if ($res > 0)
                {
                    $or->planned_date = intval($action_or->dated);
                    $or->save($user);
                    $db->commit();
                    return 1;
                }
            }
        }
        $db->rollback();
        return -1;
    } else {

        $error = 0;

        if(!empty($fk_action))
        {

            $db->begin();

            $action_or = new OperationOrderAction($db);
            $res = $action_or->fetch($fk_action);

            if($res) {

                $operationorder = new OperationOrder($db);
                $res = $operationorder->fetch($action_or->fk_operationorder);

                if($res){

                    $time_planned = $endTime - $startTime;

					$TRes = getOperationOrderActionsArray(date("Y-m-d H:i:s", $action_or->dated), convertSecondToTime($time_planned));
					$endTime = strtotime($TRes['total']['dateEnd']);

					if ($endTime != $action_or->datef){

                        $action_or->datef = $endTime;

                        $res = $action_or->save($user);
                        if($res < 0) $error++;

                        $operationorder->time_planned_f = $time_planned;
                        $res = $operationorder->save($user);
                        if($res < 0) $error++;
                    }

                } else {
                    $error++;
                }

            } else {
                $error++;
            }

            if(!$error) {
                $db->commit();
                return 1;
            }
            else {
                $db->rollback();
                return -1;
            }
        }

        return 0;
    }
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


/**
 * @param int $start
 * @param int $end
 * @param string $agendaType not used yet for multiple source type
 * @return false|string
 */
function  _getOperationOrderEvents($start = 0, $end = 0, $agendaType = 'orPlanned'){

	global $db, $hookmanager, $langs, $user, $conf;


	dol_include_once('/operationorder/class/operationorder.class.php');
	dol_include_once('/operationorder/class/operationorderaction.class.php');
	dol_include_once('/operationorder/class/operationorderstatus.class.php');

	$sOperationOrder = new OperationOrder($db); // a static usage of operation order class
	$sOperationOrderAction = new OperationOrderAction($db); // a static usage of OperationOrderAction class
	$sOperationOrderStatus = new OperationOrderStatus($db); // a static usage of OperationOrderAction class


	$langs->loadLangs(array('operationorder@operationorder', 'orders', 'companies', 'bills', 'products', 'other'));

	$TRes = array();

	$sql = 'SELECT o.rowid id, oa.dated, oa.datef, oa.rowid actionid  FROM '.MAIN_DB_PREFIX.$sOperationOrder->table_element.' o ';
	$sql.= ' JOIN '.MAIN_DB_PREFIX.$sOperationOrderAction->table_element.' oa ON (o.rowid = oa.fk_operationorder) ';
	//$sql.= ' JOIN '.MAIN_DB_PREFIX.$sOperationOrderStatus->table_element.' os ON (o.status = s.rowid) ';

	$sql.= ' WHERE 1 = 1 ';

	if(!empty($start)){
		$sql.= ' AND oa.dated <= \''.date('Y-m-d H:i:s', $end).'\'';
	}

	if(!empty($start)){
		$sql.= ' AND oa.datef >= \''.date('Y-m-d H:i:s', $start).'\'';
	}

	$sql.= ' AND o.status IN ( SELECT s.rowid FROM '.MAIN_DB_PREFIX.$sOperationOrderStatus->table_element.' s WHERE  display_on_planning > 0 ) ';
	$sql.= ' AND o.entity IN ('.getEntity('operationorder', 1).') ';

	$resql = $db->query($sql);

	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
			$event = new fullCalendarEvent();
            $event->title	= '';
            $event->msg = '';
			$operationOrder = new OperationOrder($db);
			$operationOrder->fetch($obj->id);
			$operationOrder->loadStatusObj();
            if($conf->stock->enabled && !empty($conf->global->OPODER_DISPLAY_STOCK_ON_PLANNING)) {
                $isStockAvailable = $operationOrder->isStockAvailable();
                if($isStockAvailable === $operationOrder::OR_ONLY_PHYSICAL_STOCK_NOT_ENOUGH) {
                    $event->title .= '<i class="fa fa-exclamation" aria-hidden="true" style="color:orange;"></i> &nbsp;';
                    $event->msg .= '<i class="fa fa-exclamation" aria-hidden="true" style="color:orange;"></i> &nbsp;'.$langs->trans('OnlyVirtualStockIsEnough').'<br/>';
                }
                if($isStockAvailable === $operationOrder::OR_ALL_STOCK_NOT_ENOUGH) {
                    $event->title .= '<i class="fa fa-exclamation" aria-hidden="true" style="color:red;"></i> &nbsp;';
                    $event->msg .= '<i class="fa fa-exclamation" aria-hidden="true" style="color:red;"></i> &nbsp;'.$langs->trans('NotEnoughStock').'<br/>';
                }
            }
			$event->title	.= $operationOrder->ref;

			$obj->dated = $db->jdate($obj->dated);
			$obj->datef = $db->jdate($obj->datef);


			$event->url		= dol_buildpath('/operationorder/operationorder_card.php', 1).'?id='.$operationOrder->id;
			$event->start	= date('c', $obj->dated);
			$event->end		= date('c', $obj->datef);

			$fullcalendar_scheduler_businessHours_week_end = !empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END : '18:00';
			$testDayEndDate = date("Y-m-d ".$fullcalendar_scheduler_businessHours_week_end.":00", strtotime($event->end));
//var_dump(strtotime($event->end) > strtotime($testDayEndDate)); exit;
			if (date('d', strtotime($event->start)) != date('d', strtotime($event->end)) || strtotime($event->end) > strtotime($testDayEndDate))
			{
				// obliger de réécrire les formats des dates pour afficher dans allDay
				// Note: This value is exclusive. For example, an event with the end of 2018-09-03 will appear to span through 2018-09-02 but end before the start of 2018-09-03. See how events are are parsed from a plain object for further details.
				$event->start = date("Y-m-d", strtotime($event->start));

				$addDay = 1;
				if (strtotime($event->end) > strtotime($testDayEndDate)) $addDay++;
				$event->end = date("Y-m-d", strtotime('+'.$addDay.' day', strtotime($event->end)));
				$event->allDay = true;
			}

			$event->operationOrderId = $obj->id;
			$event->operationOrderActionId = $obj->actionid;



			$event->color = $operationOrder->objStatus->color;


			if($db->jdate($obj->datef) < time()){
				$event->color = OO_colorLighten($event->color, 10);
			}

			$T = array();

			$TFieldForTooltip = array('status', 'ref', 'ref_client', 'fk_soc', 'planned_date', 'time_planned_t', 'time_planned_f');

			foreach ($operationOrder->fields as $fieldKey => $field){
				if(!in_array($fieldKey, $TFieldForTooltip)) continue;

				$T[$fieldKey] = $langs->trans($field['label']) .' : '.$operationOrder->showOutputFieldQuick($fieldKey);
			}

			$T['datef'] = $langs->trans('DateEnd') . ' : ' . date('d/m/Y H:i:s', $operationOrder->planned_date + (!empty($operationOrder->time_planned_f) ? $operationOrder->time_planned_f : $operationOrder->time_planned_t));

			$event->msg.= implode('<br/>',$T);

			$parameters= array(
				'sqlObj' => $obj,
				'operationOrder' => $operationOrder,
				'T' => $T
			);

			$reshook=$hookmanager->executeHooks('operationorderplanning',$parameters,$event, $agendaType);    // Note that $action and $object may have been modified by hook

			if ($reshook>0)
			{
				$event = $hookmanager->resArray;
			}


			$TRes[] = $event;
		}
	}
	else
	{
		dol_print_error($db);
	}

	return $TRes;
}


/**
 * @param int $start
 * @param int $end
 * @param string $agendaType not used yet for multiple source type
 * @return false|string
 */
function  _getJourOff($start = 0, $end = 0){

	global $db;

	dol_include_once('/operationorder/class/operationorderjoursoff.class.php');

	$dayOff = new OperationOrderJoursOff($db);

	$TFilter = array(
		array(
			'operator' => '>=',
			'value' => date('Y-m-d H:i:s', $start),
			'field' => 'date',
		),
		array(
			'operator' => '<=',
			'value' => date('Y-m-d H:i:s', $end),
			'field' => 'date',
		)
	);

	$TDayOff = $dayOff->fetchAll(0, false, $TFilter);


	$TRes = array();

	if (!empty($TDayOff))
	{
		foreach ($TDayOff as $dayOff)
		{
			$event = new fullCalendarEvent();

			$event->title	= $dayOff->label;

			$event->url		= '';
			$event->start	= date('c', $dayOff->date);
			// $event->end	= date('c', $dayOff->date);
			$event->allDay  = true; // will make the time show
			$event->msg = '';
			$event->color = '#a3a3a3';

			if($db->jdate($dayOff->date) < time()){
				$event->color = OO_colorLighten($event->color, 10);
			}

			$TRes[] = $event;

			$eventbg = clone $event;
			$eventbg->rendering = 'background';
			$TRes[] = $eventbg;
		}
	}

	return $TRes;
}


// Class à but descriptive, de doc etc... elle remplace juste le new stdClass qui etait utilisé juste avant.
class fullCalendarEvent {

	public $title;
	public $url;

	/**
	 * @var string $start date c format
	 */
	public $start;

	/**
	 * @var string $start date c format
	 */
	public $end;
	public $msg = '';
	public $color;

	/**
	 * @var bool Determines if the event is shown in the “all-day” section of relevant views. In addition, if true the time text is not displayed with the event.
	 */
 	public $allDay  = false; // will make the time show

	/**
	 * @var string The rendering type of this event. Can be empty (normal rendering), "background", or "inverse-background"
	 */
	public $rendering = '';

//
//	id
//	String. A unique identifier of an event. Useful for getEventById.
//
//	groupId
//	String. Events that share a groupId will be dragged and resized together automatically.
//
//
//	start
//	Date object that obeys the current timeZone. When an event begins.
//
//	end
//	Date object that obeys the current timeZone. When an event ends. It could be null if an end wasn’t specified.
//
//	Note: This value is exclusive. For example, an event with the end of 2018-09-03 will appear to span through 2018-09-02 but end before the start of 2018-09-03. See how events are are parsed from a plain object for further details.
//
//	title
//	String. The text that will appear on an event.
//
//	url
//	String. A URL that will be visited when this event is clicked by the user. For more information on controlling this behavior, see the eventClick callback.
//
//	classNames
//	An array of strings like [ 'myclass1', myclass2' ]. Determines which HTML classNames will be attached to the rendered event.
//
//	editable
//	Boolean (true or false) or null. The value overriding the editable setting for this specific event.
//
//	startEditable
//	Boolean (true or false) or null. The value overriding the eventStartEditable setting for this specific event.
//
//	durationEditable
//	Boolean (true or false) or null. The value overriding the eventDurationEditable setting for this specific event.
//
//	resourceEditable
//	Boolean (true or false) or null. The value overriding the eventResourceEditable setting for this specific event.
//
//	rendering
//	The rendering type of this event. Can be empty (normal rendering), "background", or "inverse-background"
//
//	overlap
//	The value overriding the eventOverlap setting for this specific event. If false, prevents this event from being dragged/resized over other events. Also prevents other events from being dragged/resized over this event. Does not accept a function.
//
//	constraint
//	The eventConstraint override for this specific event.
//
//	backgroundColor
//	The eventBackgroundColor override for this specific event.
//
//	borderColor
//	The eventBorderColor override for this specific event.
//
//	textColor
//	The eventTextColor override for this specific event.
//
//	extendedProps
//	A plain object holding miscellaneous other properties specified during parsing. Receives properties in the explicitly given extendedProps hash as well as other non-standard properties.
//
//	source
//	A reference to the Event Source this event came from. If the event was added dynamically via addEvent, and the source parameter was not specified, this value will be null.
}
