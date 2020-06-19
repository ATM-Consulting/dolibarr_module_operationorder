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
	console.log('setParam');
	// entrée clavier
	$('#masterInput').val(Barcode);
	// soumission du form
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
		console.log('resetState');

		this.state = {
			user: null
			,oOrder:null
			,lig:null
			,action:null
			,prod:null
		}

		$('.active').each(function(){
			$(this).removeClass('active');
		})

		$('#tableLines tbody').empty();
	}

	setstate(str)
	{
		console.log('setState For '+str);

		if (str.indexOf('USR') == 0) this.state.user = str;
		else if (str.indexOf('IMP') == 0) this.state.action = str;
		else if (str.indexOf('OR') == 0) this.state.oOrder = str;
		else if (str.indexOf('LIG') == 0) this.state.lig = str;
		else this.state.prod = str;

		let target = $('[data-barcode="'+str+'"]');

		if (target.parent().find('.active').length != 0) // s'il y a déjà un actif, on le désactive
		{
			target.parent().find('.active').each(function () {
				$(this).removeClass('active');
			})
		}
		target.addClass('active');

		this.displayInfo();

		this.runCommand();

	}

	displayInfo()
	{
		let userInfo = $('#infoUser');
		let infoOR = $('#infoOR');
		let infoTask = $('#infoTask');

		if (this.state.user != null)
			userInfo.html(this.state.user.substr(3));

		if (this.state.oOrder != null)
			infoOR.html(this.state.oOrder.substr(2));
	}

	// gère les appels ajax à faire selon le state de l'application
	/*
	1) user + or + ligne => start compteur
	2) or + ligne prod => sortie de stock
	3) user + improd => start compteur improd || annul chaine de saisie || fin de journée (stop compteur courant)
	 */
	runCommand()
	{
		if (this.state.action == 'IMPAnnul')
		{
			this.resetState();
			return;
		}

		if (this.state.prod != null)
		{
			if (this.state.oOrder == null) this.setErrorMsg('Veuillez sélectionner un OR avant de sortir une pièce');
			else
			{
				this.startAction({
					or_barcode: this.state.oOrder
					,lig:this.state.lig
					,prod:this.state.prod
				});
			}
		}

		if (this.state.lig !== null)
		{
			// TODO faire un truc selon la ligne (pointable ou sortie de stock)
			let line = $('[data-barcode="'+this.state.lig+'"]');
			if (this.state.oOrder == null) this.setErrorMsg('Veuillez sélectionner un OR avant de faire cette action');

			else if (line.data('pointable') == true)
			{
				if (this.state.user == null) this.setErrorMsg('Veuillez sélectionner un utilisateur avant de faire cette action');
				else
				{
					// étant donné que c'est un pointable, on doit démarrer un compteur
					this.startAction({
						user: this.state.user
						,or_barcode: this.state.oOrder
						,lig: this.state.lig
						,pointable:line.data('pointable')
					});

				}
			}
			else
			{
				this.startAction({
					user: this.state.user
					,or_barcode: this.state.oOrder
					,lig: this.state.lig
					,pointable:line.data('pointable')
				});
			}

			// si c'est un pointable il nous faut un user dans le state
			this.resetState();
			return;
		}

		if (this.state.user !== null)
		{
			this.getORList(this.state.oOrder);
		}

		// gestion des actions improd
		if (this.state.action !== null)
		{
			// seul annulation n'a pas besoin de user
			if (this.state.user == null)
			{
				this.setErrorMsg('Veuillez sélectionner un utilisateur avant de faire cette action');
				this.resetState();
			}
			else
			{
				if (this.state.action == 'IMPFin') this.stopUserWork(); // fin de journée
				else this.startAction({
					user:this.state.user,
					action: this.state.action
					}); // compteur improd
			}
		}

		if (this.state.oOrder !== null)
		{
			this.getORLines(this.state.oOrder);
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
					userList.append('<div class="user" data-barcode="'+barcode+'" onclick="javascript:setParam(\''+barcode+'\')"><?php print img_object('','user'); ?><br />'+user+'</div>');
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

	getORList(selectedOr)
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

			if (data.oOrders.length > 0) {
				data.oOrders.forEach(function(item) {
					let classes = "oorder";
					if (selectedOr == item.barcode) classes+= " active";
					var tr = '<tr class="'+classes+'" onclick="javascript:setParam(\'' + item.barcode + '\')" data-barcode="' + item.barcode + '">';
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
			orLines.empty();

			if (data.oOrderLines.length) {
				data.oOrderLines.forEach(function(item) {
					var tr = '<tr class="oorderline" '+ (!item.pointable ? '' : 'onclick="javascript:setParam(\'' + item.barcode + '\')"') +' data-barcode="' + item.barcode + '" data-pointable="' + item.pointable + '">';
					tr+= '<td>'+item.ref+'</td>';
					tr+= '<td>'+item.qty+'</td>';
					tr+= '<td>'+item.action+'</td>';
					tr+= '<td>'+(!item.pointable ? '' : item.barcode)+'</td></tr>';

					orLines.append(tr);
				});
			}
		});

	}

	/* ACTION */

	// TODO appel ajax pour stopper le compteur en cours de l'utilisateur (Fin de journée)
	stopUserWork()
	{
		$.ajax({
			url: endPoint,
			data: {
				action: 'stopUserWork'
				,user: this.state.user
			},
			dataType: 'json'
		}).done(function (data) {
			console.log(data);
		});

		this.resetState();
	}

	// TODO démarrage d'un compteur (et stop du précédent s'il existe)
	// devra être appelé sur les improd et sur la chaine User->OR->ligne pointable ou OR->produit
	startAction(data)
	{
		console.log(data);
		// si dans les data, on a user et action => improd
		if (data.action != undefined)
		{
			$.ajax({
				url: endPoint,
				data: {
					action: 'startImprod'
					,user: this.state.user
					,improd: this.state.action
				},
				dataType: 'json'
			}).done(function (response) {
				console.log(response);
			});
		}

		// si on a OR, lig et pointable à false => sortie de stock
		// ne devrait pas se présenter => les non-pointable sont des pièce à sortir et donc à scanner
		else if (data.pointable == false)
		{
			// on fait rien parce qu'on attend un code barre produit équivalent
			//data.action = 'stockMouvement'
			//$.ajax({
			//	url: endPoint,
			//	data: data,
			//	dataType: 'json'
			//}).done(function (response) {
			//	console.log(response);
			//});
		}

		// si on a OR, lig et pointable à true => vérif user + startCompteur
		else if (data.pointable == true)
		{
			data.action = 'startLineCounter'
			$.ajax({
				url: endPoint,
				data: data,
				dataType: 'json'
			}).done(function (response) {
				console.log(response);
			});
		}

		// Scan OR + pièce => sortie de stock
		else if (data.prod != undefined)
		{
			data.action = 'stockMouvement'
			$.ajax({
				url: endPoint,
				data: data,
				dataType: 'json'
			}).done(function (response) {
				console.log(response);
			});
		}

		this.resetState();
	}

	// TODO trouver un moyen d'afficher l'alerte dans le dom quelque part
	setErrorMsg(msg)
	{
		alert(msg);
	}
}
