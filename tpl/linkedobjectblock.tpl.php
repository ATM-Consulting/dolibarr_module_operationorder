<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
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
if (empty($conf) || ! is_object($conf)) {
    print "Error, template page can't be called as URL";
    exit;
}

?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $db,$user;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("operationorder@operationorder");

$var=true;
$total=0;
foreach($linkedObjectBlock as $key => $objectlink)
{
	$var=!$var;
?>
<tr <?php echo $GLOBALS['bc'][$var]; ?> >
    <td><?php echo $langs->trans('OperationOrder') ?></td>
    <td><?php echo $objectlink->getNomUrl(1); ?></td>
	<td class="center"><?php echo $objectlink->label; ?></td>
	<td class="center"><?php echo dol_print_date($objectlink->date_operation_order, 'day'); ?></td>
	<td class="center"></td>
	<td class="right"><?php echo $objectlink->getLibStatut(2); ?></td>
	<td class="right"><a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$objectlink->id.'&action=dellink&dellinkid='.$key; ?>"><span class="fas fa-unlink"></span></a></td>
</tr>
<?php
}
?>


<!-- END PHP TEMPLATE -->
