<?php
require('config.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/barcode.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/doc/phpbarcode.modules.php';
$barcodeGen = new modPhpbarcode;

if (!($user->admin || $user->rights->operationorder->manager->read)) {
    	accessforbidden();
}

$langs->load("operationorder@operationorder");
$hookmanager->initHooks(array('OOmanagercard'));

?><!-- <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> -->
<!DOCTYPE html>
<html>
<head>
	<title>Dolibarr - <?php echo $langs->trans('OperationOrder'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<!--	<link rel="stylesheet" href="css/normalize.css"/>-->
	<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="css/jquery-ui-1.10.4.custom.min.css" />
	<link rel="stylesheet" href="css/manager.css"/>

	<script src="js/jquery-1.9.1.min.js" type="text/javascript"></script>
	<!-- Il faut mettre le js bootstrap avant jquery ui sinon il y a certains bugs jquery (exemple : il n'y a plus de croix sur les dialogs) -->
	<script src="vendor/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="js/jquery-ui-1.10.4.custom.min.js"></script>

	<script src="<?php echo DOL_URL_ROOT; ?>/core/js/lib_head.js.php" type="text/javascript"></script>

</head>
<body>
<div class="container-fluid">
	<div id="HeaderBar" class="row">
		<div class="col-md-3">
			<form name="control" id="control" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
				<input type="text" id="masterInput" placeholder="saisie manuelle" class="form-control"/>
			</form>
		</div>
		<div id="userList" class="col-md-9">
			<span>user1</span>
			<span>user2</span>
		</div>
	</div>

	<div id="infosBar" class="row">
		<div class="col-md-12">
			<p>
				<label>Utilisateur courant&nbsp;:&nbsp;</label><span id="infoUser"></span><br />
				<label>OR Courant&nbsp;:&nbsp;</label><span id="infoOR"></span><br />
				<label>Tâche Courante&nbsp;:&nbsp;</label><span id="infoTask"></span><br />
			</p>
			<div id="responseMessageSuccess" style="display:none" class="alert alert-success"></div>
			<div id="responseMessageError" style="display:none" class="alert alert-danger alert-dismissible show"></div>
		</div>
	</div>

	<div id="centerBar" class="row">
		<table id="orList">
			<thead>
			<tr class="table-header">
				<th>Client</th>
				<th>RefOR</th>
				<?php if ($conf->dolifleet->enabled){ ?>
					<th>Immat</th>
				<?php } ?>
				<th>Code Barre</th>
			</tr>
			</thead>
			<tbody>

			</tbody>
		</table>
		<table id="actionList">
			<tr class="table-header">
				<th>Action</th>
				<th>Code</th>
			</tr>
		</table>
		<!--<div class="col-md-6">

		</div>
		<div class="col-md-6">

		</div>-->
	</div>
	<div id="ORLines" class="row">
		<table id="tableLines">
			<thead>
				<tr>
					<th>Ref Article</th>
					<th>Qty</th>
					<th>Action</th>
					<th>Code Barre</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Vid-05-112</td>
					<td>1</td>
					<td>Start</td>
					<td>Code barre</td>
				</tr>
			</tbody>
		</table>
	</div>

</div>
<div id="dialogforpopup" style="display: none;"></div>
<script src="js/manager.js.php" type="text/javascript"></script>

<?php


$reshook = $hookmanager->executeHooks('formObjectOptionsEnd', $parameters, $object, $action);

?>

</body>
</html>
