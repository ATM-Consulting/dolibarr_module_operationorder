<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


global $forceall, $senderissupplier, $inputalsopricewithtax, $outputalsopricetotalwithtax;

$usemargins = 0;
if (!empty($conf->margin->enabled) && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) $usemargins = 1;

if (empty($dateSelector)) $dateSelector = 0;
if (empty($forceall)) $forceall = 0;
if (empty($senderissupplier)) $senderissupplier = 0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax = 0;
if (empty($outputalsopricetotalwithtax)) $outputalsopricetotalwithtax = 0;

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';
$domData .= ' data-product_type="'.$line->product_type.'"';


$coldisplay = 0; ?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr  id="row-<?php print $line->id?>" class="<?php empty($line->fk_parent_line) ? print 'drag drop' : 'nodrag nodrop'; ?> oddeven" <?php print $domData; ?> >
<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td class="linecolnum center"><?php $coldisplay++; ?><?php print ($i + 1); ?></td>
<?php } ?>
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?><div id="line_<?php print $line->id; ?>"></div>
<?php
if (true)
{
	$format = $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE ? 'dayhour' : 'day';

    if ($line->fk_product > 0)
	{
		print $form->textwithtooltip($text, $description, 3, '', '', $i, 0, (!empty($line->fk_parent_line) ?img_picto('', 'rightarrow') : ''));
	}
	else
	{
		if ($type == 1) $text = img_object($langs->trans('Service'), 'service');
		else $text = img_object($langs->trans('Product'), 'product');

		if (!empty($line->label)) {
			$text .= ' <strong>'.$line->label.'</strong>';
			print $form->textwithtooltip($text, dol_htmlentitiesbr($line->description), 3, '', '', $i, 0, (!empty($line->fk_parent_line) ?img_picto('', 'rightarrow') : ''));
		} else {
			if (!empty($line->fk_parent_line)) print img_picto('', 'rightarrow');
			if (preg_match('/^\(DEPOSIT\)/', $line->description)) {
				$newdesc = preg_replace('/^\(DEPOSIT\)/', $langs->trans("Deposit"), $line->description);
				print $text.' '.dol_htmlentitiesbr($newdesc);
			}
			else {
				print $text.' '.dol_htmlentitiesbr($line->description);
			}
		}
	}

	// Show date range
	if ($line->element == 'facturedetrec') {
		if ($line->date_start_fill || $line->date_end_fill) print '<br><div class="clearboth nowraponall">';
		if ($line->date_start_fill) print $langs->trans('AutoFillDateFromShort').': '.yn($line->date_start_fill);
		if ($line->date_start_fill && $line->date_end_fill) print ' - ';
		if ($line->date_end_fill) print $langs->trans('AutoFillDateToShort').': '.yn($line->date_end_fill);
		if ($line->date_start_fill || $line->date_end_fill) print '</div>';
	}
	else {
		if ($line->date_start || $line->date_end) print '<br><div class="clearboth nowraponall">'.get_date_range($line->date_start, $line->date_end, $format).'</div>';
		//print get_date_range($line->date_start, $line->date_end, $format);
	}

	// Add description in form
	if ($line->fk_product > 0 && !empty($conf->global->PRODUIT_DESC_IN_FORM))
	{
		print (!empty($line->description) && $line->description != $line->product_label) ? '<br>'.dol_htmlentitiesbr($line->description) : '';
	}
}

print '</td>';

$coldisplay++;
$positiverates = '';

?>
	<td class="linecolqty nowrap right"><?php $coldisplay++; ?>
<?php
if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
	// I comment this because it shows info even when not required
	// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
	// must also not be output for most entities (proposal, intervention, ...)
	//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
	print price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
} else print '&nbsp;';
print '</td>';


// TODO à revoir
print '<td class="linecolemplacement right">'.$line->emplacement.'</td>';

print '<td class="linecolpc right">'.$line->pc.'</td>';

print '<td class="linecoltimeplanned right">'.convertSecondToTime($line->time_planned, 'allhourmin').'</td>';

print '<td class="linecoltimespent right">'.convertSecondToTime($line->time_spent, 'allhourmin').'</td>';
// TODO FIN

if ($this->status == 0 && ($object_rights->write) && $action != 'selectlines') {
	print '<td class="linecoledit center">';
	$coldisplay++;
	if (($line->info_bits & 2) == 2 || !empty($disableedit)) {
	} else { ?>
		<a href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id; ?>">
		<?php print img_edit().'</a>';
	}
	print '</td>';

	print '<td class="linecoldelete center">';
	$coldisplay++;
	if (($line->fk_prev_id == null) && empty($disableremove)) { //La suppression n'est autorisée que si il n'y a pas de ligne dans une précédente situation
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=ask_deleteline&amp;lineid='.$line->id.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';

	if (!empty($line->fk_parent_line))
    {
        $coldisplay++;
        print '<td class="center"></td>';
    }
	elseif ($num > 1 && $conf->browser->layout != 'phone' && empty($disablemove)) {
		print '<td class="linecolmove tdlineupdown center">';
		$coldisplay++;
		if ($i > 0) { ?>
			<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=up&amp;rowid='.$line->id; ?>">
			<?php print img_up('default', 0, 'imgupforline'); ?>
			</a>
		<?php }
		if ($i < $num - 1) { ?>
			<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=down&amp;rowid='.$line->id; ?>">
			<?php print img_down('default', 0, 'imgdownforline'); ?>
			</a>
		<?php }
		print '</td>';
    } else {
		print '<td '.(($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'></td>';
		$coldisplay++;
	}
} else {
	print '<td colspan="3"></td>';
	$coldisplay = $coldisplay + 3;
}

if ($action == 'selectlines') { ?>
	<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>" ></td>
<?php }

print "</tr>\n";

//Line extrafield
if (!empty($extrafields))
{
	print $line->showOptionals($extrafields, 'view', array('style'=>'class="drag drop oddeven"', 'colspan'=>$coldisplay), '', '', empty($conf->global->MAIN_EXTRAFIELDS_IN_ONE_TD) ? 0 : 1);
}

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";
