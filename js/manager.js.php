<?php

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

?>

let endPoint = "scripts/managerinterface.php";

$(document).ready(function () {
	let App = new Application;
	$('#masterInput').focus()

	// liste des utilisateurs
	App.getUserList();

	// liste des actions IMPROD
	App.getActionsList();

	// liste des OR du jour
	App.getORList();

	$('#control').on('submit', function(e) {
		e.preventDefault();
		App.setstate($('#masterInput').val())
		$('#masterInput').val(null);
		$('#masterInput').focus()
	});

});

function setParam(Barcode) {
	$('#masterInput').val(Barcode);
	$('#control').submit();
}

class Application
{

	/* Fonctionnel */

	constructor()
	{
		this.resetState();
	}

	resetState()
	{
		this.state = {
			user: null
			,oOrder:null
			,lig:null
			,action:null
		}
	}

	setstate(str)
	{
		if (str.indexOf('USR') == 0) this.state.user = str;
		else if (str.indexOf('IMP') == 0) this.state.action = str;
		else if (str.indexOf('OR') == 0) this.state.oOrder = str;
		else if (str.indexOf('LIG') == 0) this.state.lig = str;

		console.log(this.state);

		this.runCommand();
	}

	// gère les appels ajax à faire selon le state de l'application
	/*
	1) user + or + ligne => start compteur
	2) or + ligne prod => sortie de stock
	3) user + improd => start compteur improd || annul chaine de saisie || fin de journée (stop compteur courant)
	 */
	runCommand()
	{
		var user = this.state.user;
		var oOrder = this.state.oOrder;
		var lig = this.state.lig
		var action = this.state.action;

		if (user !== null)
		{
			this.getORList();
		}

		// gestion des actions improd
		if (action !== null)
		{
			if (action == 'IMPAnnul')
			{
				if (user == null && oOrder == null && lig == null)
					this.resetState();
			}
			else { // seul annulation n'a pas besoin de user
				if (user == null)
				{
					this.setErrorMsg('Veuillez sélectionner un utilisateur avant de faire cette action');
					this.resetState();
				}
				else
				{
					if (action == 'IMPFin') this.stopUserWork(); // fin de journée
					else this.startCount({user:user, action: action}); // compteur improd
				}
			}
		}

		if (oOrder !== null)
		{
			this.getORLines(oOrder);
		}

	}

	/* Récupération de données */

	getUserList()
	{
		$.ajax({
			url: endPoint,
			data: {
				action: 'getUserList'
			},
			dataType: 'json'
		}).done(function (data) {
			if (data.users.length)
			{
				let userList = $('#userList')
				userList.html('');
				data.users.forEach(function(user) {
					var barcode = 'USR'+user;
					userList.append('<span class="user" data-barcode="'+barcode+'" onclick="javascript:setParam(\''+barcode+'\')"><?php print img_object('','user'); ?>'+user+'</span>');
				})
			}
		});
	}

	getActionsList()
	{
		// data à récupérer dans le dictionnaire des actions + "fin de journée" et "annulation"
		$.ajax({
			url: endPoint,
			data: {
				action: 'getActionsList'
			},
			dataType: 'json'
		}).done(function (data) {
			if (data.actions.length) {
				let actionsList = $('#actionList');
				data.actions.forEach(function (item) {
					actionsList.append('<tr class="action" onclick="javascript:setParam(\'' + item[1] + '\')"><td>' + item[0] + '</td><td>' + item[1] + '</td></tr>');
				});
			}
		});

	}

	getORList()
	{
		$.ajax({
			url: endPoint,
			data: {
				action: 'getORList'
			},
			dataType: 'json'
		}).done(function (data) {
			let orList = $('#orList tbody');
			orList.empty();

			if (data.oOrders.length > 1) {
				data.oOrders.forEach(function(item) {
					var tr = '<tr class="oorder" onclick="javascript:setParam(\'' + item.barcode + '\')">';
					tr+= '<td>'+item.client+'</td>';
					tr+= '<td>'+item.ref+'</td>';
					if (item.immat !== undefined) tr+= '<td>'+item.immat+'</td>';
					tr+= '<td>'+item.barcode+'</td></tr>';

					orList.append(tr);
				});
			}
		});
	}

	getORLines(OR_Barcode)
	{
		$.ajax({
			url: endPoint,
			data: {
				action: 'getORLines'
				,or_barcode: OR_Barcode
			},
			dataType: 'json'
		}).done(function (data) {
			console.log(data);
			let orLines = $('#tableLines tbody');
			//orLines.empty();

			//if (data.oOrders.length) {
			//	data.oOrders.forEach(function(item) {
			//		var tr = '<tr class="oorder" onclick="javascript:setParam(\'' + item.barcode + '\')">';
			//		tr+= '<td>'+item.client+'</td>';
			//		tr+= '<td>'+item.ref+'</td>';
			//		if (item.immat !== undefined) tr+= '<td>'+item.immat+'</td>';
			//		tr+= '<td>'+item.barcode+'</td></tr>';
			//
			//		orList.append(tr);
			//	});
			//}
		});
	}

	/* ACTION */

	// TODO appel ajax pour stopper le compteur en cours de l'utilisateur (Fin de journée)
	stopUserWork()
	{
		this.resetState();
	}

	// TODO démarrage d'un compteur (et stop du précédent s'il existe)
	// devra être appelé sur les improd et sur la chaine User->OR->ligne pointable
	startCount(data)
	{
		console.log(data);
	}

	// TODO trouver un moyen d'afficher l'alerte dans le dom quelque part
	setErrorMsg(msg)
	{
		alert(msg);
	}
}
