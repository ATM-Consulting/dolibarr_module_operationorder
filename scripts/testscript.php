<?php

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once __DIR__ . '/../lib/operationorder.lib.php';

/**
 * exemple avec appel getOperationOrderActionsArray("2020-05-29 15:00:00", "03:20") :
 * array(
 * 		'2020-05-29' => array (
 * 			'dateStart' => "2020-05-29 15:00:00"
 * 			'dateEnd'	=> "2020-05-29 17:00:00"
 * 			'timeSpent'	=> 7200 (en secondes)
 * 		)
 * 		'2020-04-13' => array (
 * 			'dateStart' => "2020-06-02 08:00:00"
 * 			'dateEnd'	=> "2020-06-02 09:20:00"
 * 			'timeSpent'	=> 4800 (en secondes)
 * 		)
 * 		'total' => array (
 * 			'dateStart' => "2020-05-29 15:00:00"
 * 			'dateEnd'	=> "2020-06-02 09:20:00"
 * 			'timeSpent'	=> 12000 (en secondes)
 * 			'timeSpentHours'	=> "03:20"
 * 			'excluded' 	=> array(
 * 				"2020-05-30" => "samedi"
 * 				"2020-05-31" => "dimanche"
 * 				"2020-06-01" => "férié"
 * 			)
 * 		)
 * );
 */
$ActionsArray = getOperationOrderActionsArray("2020-05-29 15:00:00", "03:20");

var_dump($ActionsArray);
