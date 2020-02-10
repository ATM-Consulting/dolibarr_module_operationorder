<?php
/**
 * Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}
$usemargins = 0;
if (!empty($conf->margin->enabled) && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande')))
{
	$usemargins = 1;
}
if (!isset($dateSelector)) global $dateSelector; // Take global var only if not already defined into function calling (for example formAddObjectLine)
global $forceall, $forcetoshowtitlelines, $senderissupplier, $inputalsopricewithtax;
if (!isset($dateSelector)) $dateSelector = 1; // For backward compatibility
elseif (empty($dateSelector)) $dateSelector = 0;
if (empty($forceall)) $forceall = 0;
if (empty($senderissupplier)) $senderissupplier = 0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax = 0;
// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete
if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) $colspan++; //Add column for Total (currency) if required
if (in_array($object->element, array('propal', 'commande', 'order', 'facture', 'facturerec', 'invoice', 'supplier_proposal', 'order_supplier', 'invoice_supplier'))) $colspan++; // With this, there is a column move button
//print $object->element;
// Lines for extrafield
$objectline = new OperationOrderDet();

print "<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->\n";
$nolinesbefore = (count($this->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {
	?>
	<tr class="liste_titre<?php echo (($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_add_') ?> nodrag nodrop">
		<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
			<td class="linecolnum center"></td>
		<?php } ?>
		<td class="linecoldescription minwidth500imp">
			<div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('AddNewLine'); ?></span><?php // echo $langs->trans("FreeZone"); ?>
		</td>

		<td class="linecolqty right"><?php echo $langs->trans('Qty'); ?></td>

        <?php
        print '<td class="linecolemplacement right">'.$langs->trans('Emplacement').'</td>';

        print '<td class="linecolpc right">'.$langs->trans('PC').'</td>';

        print '<td class="linecoltimeplanned right">'.$langs->trans('TimePlanned').'</td>';

        print '<td class="linecoltimespent right">'.$langs->trans('TimeSpent').'</td>';
        ?>

		<td class="linecoledit" colspan="<?php echo $colspan; ?>">&nbsp;</td>
	</tr>
	<?php
}
?>
<tr class="pair nodrag nodrop nohoverpair<?php echo ($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_create'; ?>">
	<?php
	$coldisplay = 0;
	// Adds a line numbering column
	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
		$coldisplay++;
		echo '<td class="nobottom linecolnum center"></td>';
	}
	$coldisplay++;
	?>
	<td class="nobottom linecoldescription minwidth500imp">

		<?php
		$freelines = false;
		if (empty($conf->global->MAIN_DISABLE_FREE_LINES))
		{
			$freelines = true;
			$forceall = 1; // We always force all type for free lines (module product or service means we use predefined product or service)
			if ($object->element == 'contrat')
			{
				if (empty($conf->product->enabled) && empty($conf->service->enabled) && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $forceall = -1; // With contract, by default, no choice at all, except if CONTRACT_SUPPORT_PRODUCTS is set
				elseif (empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $forceall = 3;
			}
			// Free line
			echo '<span class="prod_entry_mode_free">';
			// Show radio free line
			if ($forceall >= 0 && (!empty($conf->product->enabled) || !empty($conf->service->enabled)))
			{
				echo '<label for="prod_entry_mode_free">';
				echo '<input type="radio" class="prod_entry_mode_free" name="prod_entry_mode" id="prod_entry_mode_free" value="free"';
				//echo (GETPOST('prod_entry_mode')=='free' ? ' checked' : ((empty($forceall) && (empty($conf->product->enabled) || empty($conf->service->enabled)))?' checked':'') );
				echo (GETPOST('prod_entry_mode') == 'free' ? ' checked' : '');
				echo '> ';
				// Show type selector
				echo $langs->trans("FreeLineOfType");
				echo '</label>';
				echo ' ';
			}
			else
			{
				echo '<input type="hidden" id="prod_entry_mode_free" name="prod_entry_mode" value="free">';
				// Show type selector
				if ($forceall >= 0)
				{
					if (empty($conf->product->enabled) || empty($conf->service->enabled)) echo $langs->trans("Type");
					else echo $langs->trans("FreeLineOfType");
					echo ' ';
				}
			}
			echo $form->select_type_of_lines(isset($_POST["type"]) ?GETPOST("type", 'alpha', 2) : -1, 'type', 1, 1, $forceall);
			echo '</span>';
		}
		// Predefined product/service
		if (!empty($conf->product->enabled) || !empty($conf->service->enabled))
		{
			if ($forceall >= 0 && $freelines) echo '<br>';
			echo '<span class="prod_entry_mode_predef">';
			echo '<label for="prod_entry_mode_predef">';
			echo '<input type="radio" class="prod_entry_mode_predef" name="prod_entry_mode" id="prod_entry_mode_predef" value="predef"'.(GETPOST('prod_entry_mode') == 'predef' ? ' checked' : '').'> ';
			if (empty($senderissupplier))
			{
				if (!empty($conf->product->enabled) && empty($conf->service->enabled)) echo $langs->trans('PredefinedProductsToSell');
				elseif ((empty($conf->product->enabled) && !empty($conf->service->enabled)) || ($object->element == 'contrat' && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS))) echo $langs->trans('PredefinedServicesToSell');
				else echo $langs->trans('PredefinedProductsAndServicesToSell');
			}
			else
			{
				if (!empty($conf->product->enabled) && empty($conf->service->enabled)) echo $langs->trans('PredefinedProductsToPurchase');
				elseif (empty($conf->product->enabled) && !empty($conf->service->enabled)) echo $langs->trans('PredefinedServicesToPurchase');
				else echo $langs->trans('PredefinedProductsAndServicesToPurchase');
			}
			echo '</label>';
			echo ' ';
			$filtertype = '';
			if (!empty($object->element) && $object->element == 'contrat' && empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $filtertype = '1';
			if (empty($senderissupplier))
			{
				$statustoshow = 1;
				if (!empty($conf->global->ENTREPOT_EXTRA_STATUS))
				{
					// hide products in closed warehouse, but show products for internal transfer
					$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, 'warehouseopen,warehouseinternal', GETPOST('combinations', 'array'));
				}
				else
				{
					$form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, '', GETPOST('combinations', 'array'));
				}
				if (!empty($conf->global->MAIN_AUTO_OPEN_SELECT2_ON_FOCUS_FOR_CUSTOMER_PRODUCTS))
				{
					?>
				<script type="text/javascript">
					$(document).ready(function(){
						// On first focus on a select2 combo, auto open the menu (this allow to use the keyboard only)
						$(document).on('focus', '.select2-selection.select2-selection--single', function (e) {
							console.log('focus on a select2');
							if ($(this).attr('aria-labelledby') == 'select2-idprod-container')
							{
								console.log('open combo');
								$('#idprod').select2('open');
							}
						});
					});
				</script>
					<?php
				}
			}
			else
			{
				// $senderissupplier=2 is the same as 1 but disables test on minimum qty and disable autofill qty with minimum
				if ($senderissupplier != 2)
				{
					$ajaxoptions = array(
					'update' => array('qty'=>'qty', 'remise_percent' => 'discount', 'idprod' => 'idprod'), // html id tags that will be edited with which ajax json response key
					'option_disabled' => 'idthatdoesnotexists', // html id to disable once select is done
					'warning' => $langs->trans("NoPriceDefinedForThisSupplier") // translation of an error saved into var 'warning' (for example shown we select a disabled option into combo)
					);
					$alsoproductwithnosupplierprice = 0;
				}
				else
				{
					$ajaxoptions = array(
					'update' => array('remise_percent' => 'discount')			// html id tags that will be edited with each ajax json response key
					);
					$alsoproductwithnosupplierprice = 1;
				}
				$form->select_produits_fournisseurs($object->socid, GETPOST('idprodfournprice'), 'idprodfournprice', '', '', $ajaxoptions, 1, $alsoproductwithnosupplierprice, 'maxwidth500');
				if (!empty($conf->global->MAIN_AUTO_OPEN_SELECT2_ON_FOCUS_FOR_SUPPLIER_PRODUCTS))
				{
					?>
				<script type="text/javascript">
					$(document).ready(function(){
						// On first focus on a select2 combo, auto open the menu (this allow to use the keyboard only)
						$(document).on('focus', '.select2-selection.select2-selection--single', function (e) {
							//console.log('focus on a select2');
							if ($(this).attr('aria-labelledby') == 'select2-idprodfournprice-container')
							{
								$('#idprodfournprice').select2('open');
							}
						});
					});
				</script>
					<?php
				}
			}
			echo '<input type="hidden" name="pbq" id="pbq" value="">';
			echo '</span>';
		}
		if (is_object($hookmanager) && empty($senderissupplier))
		{
			$parameters = array('fk_parent_line'=>GETPOST('fk_parent_line', 'int'));
			$reshook = $hookmanager->executeHooks('formCreateProductOptions', $parameters, $object, $action);
			if (!empty($hookmanager->resPrint)) {
				print $hookmanager->resPrint;
			}
		}
		if (is_object($hookmanager) && !empty($senderissupplier))
		{
			$parameters = array('htmlname'=>'addproduct');
			$reshook = $hookmanager->executeHooks('formCreateProductSupplierOptions', $parameters, $object, $action);
			if (!empty($hookmanager->resPrint)) {
				print $hookmanager->resPrint;
			}
		}
		if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
			if (!empty($conf->variants->enabled)) {
				echo '<div id="attributes_box"></div>';
			}
			echo '<br>';
		}
		// Editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows = ROWS_2;
		$enabled = (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
		if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
		$toolbarname = 'dolibarr_details';
		if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) $toolbarname = 'dolibarr_notes';
		$doleditor = new DolEditor('dp_desc', GETPOST('dp_desc', 'none'), '', (empty($conf->global->MAIN_DOLEDITOR_HEIGHT) ? 100 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enabled, $nbrows, '98%');
		$doleditor->Create();
		// Show autofill date for recurring invoices
		if (!empty($conf->service->enabled) && $object->element == 'facturerec')
		{
			echo '<div class="divlinefordates"><br>';
			echo $langs->trans('AutoFillDateFrom').' ';
			echo $form->selectyesno('date_start_fill', $line->date_start_fill, 1);
			echo ' - ';
			echo $langs->trans('AutoFillDateTo').' ';
			echo $form->selectyesno('date_end_fill', $line->date_end_fill, 1);
			echo '</div>';
		}
		echo '</td>';

		?>
	</td>


	<?php
	$coldisplay++;
	?>
	<td class="nobottom linecolqty right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="<?php echo (isset($_POST["qty"]) ?GETPOST("qty", 'alpha', 2) : 1); ?>">
	</td>
	<?php

    $coldisplay++;
    print '<td class="nobottom linecolemplacement right"><input type="text" size="3" name="emplacement" id="emplacement" class="flat right" value="'.(GETPOSTISSET('emplacement') ? GETPOST("emplacement", 'alpha', 2) : '').'"></td>';

    $coldisplay++;
    print '<td class="nobottom linecolpc right"><input type="text" size="3" name="pc" id="pc" class="flat right" value="'.(GETPOSTISSET('pc') ? GETPOST("pc", 'alpha', 2) : '').'"></td>';

    $coldisplay++;
    print '<td class="nobottom linecoltimeplanned right">';
    print $form->select_duration('time_planned', GETPOST('time_planned'), 0, 'text');
    print '</td>';

    $coldisplay++;
    print '<td class="nobottom linecoltimespent right">';
    print $form->select_duration('time_spent', GETPOST('time_spent'), 0, 'text');
    print '</td>';

	$coldisplay += $colspan;
	?>
	<td class="nobottom linecoledit center valignmiddle" colspan="<?php echo $colspan; ?>">
		<input type="submit" class="button" value="<?php echo $langs->trans('Add'); ?>" name="addline" id="addline">
	</td>
</tr>

<?php
if (is_object($objectline)) {
	print $objectline->showOptionals($extrafields, 'edit', array('colspan'=>$coldisplay), '', '', empty($conf->global->MAIN_EXTRAFIELDS_IN_ONE_TD) ? 0 : 1);
}

if ((!empty($conf->service->enabled) || ($object->element == 'contrat')) && $dateSelector && GETPOST('type') != '0')	// We show date field if required
{
	print '<tr id="trlinefordates" class="oddeven">'."\n";
	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { print '<td></td>'; }
	print '<td colspan="'.($coldisplay - (empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? 0 : 1)).'">';
	$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
	$date_end = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
	if (!empty($object->element) && $object->element == 'contrat')
	{
		print $langs->trans("DateStartPlanned").' ';
		print $form->selectDate($date_start, "date_start", $usehm, $usehm, 1, "addproduct");
		print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
		print $form->selectDate($date_end, "date_end", $usehm, $usehm, 1, "addproduct");
	}
	else
	{
		print $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
		print $form->selectDate($date_start, 'date_start', empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE) ? 0 : 1, empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE) ? 0 : 1, 1, "addproduct", 1, 0);
		print ' '.$langs->trans('to').' ';
		print $form->selectDate($date_end, 'date_end', empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE) ? 0 : 1, empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE) ? 0 : 1, 1, "addproduct", 1, 0);
	};
	print '<script>';
	if (!$date_start) {
		if (isset($conf->global->MAIN_DEFAULT_DATE_START_HOUR)) {
			print 'jQuery("#date_starthour").val("'.$conf->global->MAIN_DEFAULT_DATE_START_HOUR.'");';
		}
		if (isset($conf->global->MAIN_DEFAULT_DATE_START_MIN)) {
			print 'jQuery("#date_startmin").val("'.$conf->global->MAIN_DEFAULT_DATE_START_MIN.'");';
		}
	}
	if (!$date_end) {
		if (isset($conf->global->MAIN_DEFAULT_DATE_END_HOUR)) {
			print 'jQuery("#date_endhour").val("'.$conf->global->MAIN_DEFAULT_DATE_END_HOUR.'");';
		}
		if (isset($conf->global->MAIN_DEFAULT_DATE_END_MIN)) {
			print 'jQuery("#date_endmin").val("'.$conf->global->MAIN_DEFAULT_DATE_END_MIN.'");';
		}
	}
	print '</script>';
	print '</td>';
	print '</tr>'."\n";
}


print "<script>\n";

?>
<script>
	/* JQuery for product free or predefined select */
	jQuery(document).ready(function() {

	$("#prod_entry_mode_free").on( "click", function() {
	setforfree();
	});
	$("#select_type").change(function()
	{
	setforfree();
	if (jQuery('#select_type').val() >= 0)
	{
	/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
	jQuery('#dp_desc').focus();
	/* focus if CKEDITOR */
	if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
	{
	var editor = CKEDITOR.instances['dp_desc'];
	if (editor) { editor.focus(); }
	}
	}
	console.log("Hide/show date according to product type");
	if (jQuery('#select_type').val() == '0')
	{
	jQuery('#trlinefordates').hide();
	jQuery('.divlinefordates').hide();
	}
	else
	{
	jQuery('#trlinefordates').show();
	jQuery('.divlinefordates').show();
	}
	});

	$("#prod_entry_mode_predef").on( "click", function() {
	console.log("click prod_entry_mode_predef");
	setforpredef();
	jQuery('#trlinefordates').show();
	});

	<?php
	if (!$freelines) { ?>
		$("#prod_entry_mode_predef").click();
		<?php
	}
	?>

	/* When changing predefined product, we reload list of supplier prices required for margin combo */
	$("#idprod, #idprodfournprice").change(function()
	{
		console.log("Call method change() after change on #idprod or #idprodfournprice. this.val = "+$(this).val());

		setforpredef();		// TODO Keep vat combo visible and set it to first entry into list that match result of get_default_tva

		jQuery('#trlinefordates').show();

		<?php
		if (empty($conf->global->MAIN_DISABLE_EDIT_PREDEF_PRICEHT) && empty($senderissupplier))
		{
			?>
			// Get the HT price for the product and display it
			console.log("Load price without tax and set it into #price_ht");
			$.post('<?php echo DOL_URL_ROOT; ?>/product/ajax/products.php?action=fetch',
				{ 'id': $(this).val(), 'socid' : <?php print $object->socid; ?> },
				function(data) { jQuery("#price_ht").val(data.price_ht); },
				'json'
			);
			<?php
		}

		?>

		/* To process customer price per quantity */
		var pbq = parseInt($('option:selected', this).attr('data-pbq'));
		var pbqqty = parseFloat($('option:selected', this).attr('data-pbqqty'));
		var pbqpercent = parseFloat($('option:selected', this).attr('data-pbqpercent'));

		if ((jQuery('#idprod').val() > 0 || jQuery('#idprodfournprice').val()) && typeof pbq !== "undefined")
		{
			console.log("We choose a price by quanty price_by_qty id = "+pbq+" price_by_qty qty = "+pbqqty+" price_by_qty percent = "+pbqpercent);
			jQuery("#pbq").val(pbq);
			if (jQuery("#qty").val() < pbqqty)
			{
				jQuery("#qty").val(pbqqty);
			}

		}
		else
		{
			jQuery("#pbq").val('');
		}

		/* To set focus */
		if (jQuery('#idprod').val() > 0 || jQuery('#idprodfournprice').val() > 0)
		{
			/* focus work on a standard textarea but not if field was replaced with CKEDITOR */
			jQuery('#dp_desc').focus();
			/* focus if CKEDITOR */
			if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined")
			{
				var editor = CKEDITOR.instances['dp_desc'];
				if (editor) { editor.focus(); }
			}
		}
	});

		<?php if (GETPOST('prod_entry_mode') == 'predef') { // When we submit with a predef product and it fails we must start with predef ?>
		setforpredef();
		<?php } ?>
	});

	/* Function to set fields from choice */
	function setforfree() {
		console.log("Call setforfree. We show most fields");
		jQuery("#prod_entry_mode_free").prop('checked',true).change();
		jQuery("#prod_entry_mode_predef").prop('checked',false).change();
		jQuery("#search_idprod, #idprod, #search_idprodfournprice, #buying_price").val('');
	}
	function setforpredef() {
		console.log("Call setforpredef. We hide some fields and show dates");
		jQuery("#select_type").val(-1);
		jQuery("#prod_entry_mode_free").prop('checked',false).change();
		jQuery("#prod_entry_mode_predef").prop('checked',true).change();

		jQuery('#trlinefordates, .divlinefordates').show();
	}

<?php

print '</script>';

print "<!-- END PHP TEMPLATE objectline_create.tpl.php -->\n";
