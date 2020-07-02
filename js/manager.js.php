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

	$('.alert-danger').alert();

});

function setParam(Barcode) {
	console.log('setParam');
	// entrée clavier
	$('#masterInput').val(Barcode);
	// soumission du form
	$('#control').submit();
}

function setErrorMsg(msg)
{
	//alert(msg);
	let msgDiv = $('<div id="responseMessageError" style="display:none" class="alert alert-danger alert-dismissible show"></div>');

	msgDiv.html(msg + '<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
		'    <span aria-hidden="true">&times;</span>\n' +
		'  </button>');
	msgDiv.fadeIn();

	msgDiv.appendTo('#infosBar .col-md-12');
}

checkLoginStatus();

function checkLoginStatus() {

	$.ajax({
		url: endPoint,
		dataType: "json",
		crossDomain: true,
		data: {
			action:'logged-status'
		}
	})
		.then(function (data){

			if(data.msg!='ok') {
				document.location.href = document.location.href; // reload car la session est expirée
			}
			else {
				setTimeout(function() {
					checkLoginStatus();
				}, 30000);
			}

		});

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
			,courantTask:null
		}

		$('.active').each(function(){
			$(this).removeClass('active');
		})

		$('#tableLines tbody').empty();

		this.displayInfo();
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

		this.runCommand();

		this.displayInfo();

	}

	displayInfo()
	{
		let userInfo = $('#infoUser');
		let infoOR = $('#infoOR');
		let infoTask = $('#infoTask');

		if (this.state.user != null)
			userInfo.html(this.state.user.substr(3));
		else userInfo.html('');

		if (this.state.oOrder != null)
			infoOR.html(this.state.oOrder.substr(2));
		else
			infoOR.html('');

		if (this.state.courantTask != null)
			infoTask.html(this.state.courantTask);
		else
			infoTask.html('');
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
			if (this.state.oOrder == null)
			{
				setErrorMsg('Veuillez sélectionner un OR avant de sortir une pièce');
				this.resetState();
			}
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
			// faire un truc selon la ligne (pointable ou sortie de stock)
			let line = $('[data-barcode="'+this.state.lig+'"]');
			if (this.state.oOrder == null) setErrorMsg('Veuillez sélectionner un OR avant de faire cette action');

			else if (line.data('pointable') == true)
			{
				if (this.state.user == null) setErrorMsg('Veuillez sélectionner un utilisateur avant de faire cette action');
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

		// gestion des actions improd
		if (this.state.action !== null)
		{
			// seul annulation n'a pas besoin de user
			if (this.state.user == null)
			{
				setErrorMsg('Veuillez sélectionner un utilisateur avant de faire cette action');
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

		if (this.state.user !== null)
		{
			this.getORList(this.state.oOrder, this.state.user);
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
			let userList = $('#userList')
			userList.html('');

			if (data.users.length)
			{
				data.users.forEach(function(user) {
					var barcode = 'USR'+user;
					userList.append('<div class="user" data-barcode="'+barcode+'" onclick="javascript:setParam(\''+barcode+'\')"><?php print img_object('','user'); ?><br />'+user+'</div>');
				})
			}
			else userList.append('<p>'+data.errorMsg+'<p>');
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
					actionsList.append('<tr class="action" onclick="javascript:setParam(\'' + item[1] + '\')"><td>' + item[0] + '</td><td>' + item[2] + '<br />' + item[1] + '</td></tr>');
				});
			}
		});

	}

	getORList(selectedOr, curentuser)
	{
		$.ajax({
			url: endPoint,
			data: {
				action: 'getORList'
				,user: curentuser
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
					tr+= '<td>'+item.bars+'<br />'+item.barcode+'</td></tr>';

					orList.append(tr);
				});
			}

			let infoTask = $('#infoTask');

			if (data.courantTask.length > 0)
			{
				infoTask.html(data.courantTask);
			}
			else
			{
				infoTask.html('');
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
			//console.log(data);
			let orLines = $('#tableLines tbody');
			orLines.empty();

			if (data.oOrderLines.length) {
				data.oOrderLines.forEach(function(item) {
					var tr = '<tr class="oorderline" '+ (!item.pointable ? '' : 'onclick="javascript:setParam(\'' + item.barcode + '\')"') +' data-barcode="' + item.barcode + '" data-pointable="' + item.pointable + '">';
					tr+= '<td>'+item.ref+'</td>';
					tr+= '<td>'+( item.qtyUsed != 0 ? item.qtyUsed + " / " : "" )+item.qty+'</td>';
					tr+= '<td>'+item.action+'</td>';
					tr+= '<td>'+(!item.pointable ? '' : item.bars + '<br />' + item.barcode)+'</td></tr>';

					orLines.append(tr);
				});
			}
		});

	}

	/* ACTION */

	// appel ajax pour stopper le compteur en cours de l'utilisateur (Fin de journée)
	stopUserWork()
	{
		$.ajax({
			url: endPoint,
			data: {
				action: 'stopUserWork'
				,user: this.state.user
			},
			dataType: 'json'
		}).done(function (response) {
			//console.log(data);
			if (response.result == 1 && response.msg != '')
			{
				let msgDiv = $('#responseMessageSuccess');
				msgDiv.html(response.msg);
				msgDiv.fadeIn();
				msgDiv.fadeOut(3000);
			}
			else if (response.result == 0 && response.errorMsg)
			{
				setErrorMsg(response.errorMsg)
			}
		});

		this.resetState();
	}

	// démarrage d'un compteur (et stop du précédent s'il existe)
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
				//console.log(response);
				if (response.result == 1 && response.msg != '')
				{
					let msgDiv = $('#responseMessageSuccess');
					msgDiv.html(response.msg);
					msgDiv.fadeIn();
					msgDiv.fadeOut(3000);
				}
				else if (response.result == 0 && response.errorMsg)
				{
					setErrorMsg(response.errorMsg)
				}
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
				//console.log(response);
				if (response.result == 1 && response.msg != '')
				{
					let msgDiv = $('#responseMessageSuccess');
					msgDiv.html(response.msg);
					msgDiv.fadeIn();
					msgDiv.fadeOut(3000);
				}
				else if (response.result == 0 && response.errorMsg)
				{
					setErrorMsg(response.errorMsg)
				}
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
				if (response.result == 1 && response.msg != '')
				{
					let msgDiv = $('#responseMessageSuccess');
					msgDiv.html(response.msg);
					msgDiv.fadeIn();
					msgDiv.fadeOut(3000);
				}
				else if (response.result == 0 && response.errorMsg)
				{
					setErrorMsg(response.errorMsg)
				}
			});
		}

		this.resetState();
	}

}
