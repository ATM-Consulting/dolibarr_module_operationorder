<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $action
 * $conf
 * $langs
 *
 * $keyforbreak may be defined to key to switch on second column
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}
if (!is_object($form)) $form = new Form($db);

?>
<!-- BEGIN PHP TEMPLATE commonfields_view.tpl.php -->
<?php

$object->fields = dol_sort_array($object->fields, 'position');

foreach ($object->fields as $key => $val)
{
	if (!empty($keyforbreak) && $key == $keyforbreak) break; // key used for break on second column

	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) continue;

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) continue; // We don't want this field
	if (in_array($key, array('ref', 'status'))) continue; // Ref and status are already in dol_banner

	$value = $object->$key;

	print '<tr><td class="titlefield">';
    print '<table class="nobordernopadding centpercent">';
    print '<tr>';

	print '<td';
	print ' class="titlefield fieldname_'.$key;
	//if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
	if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
	print '">';
	if (!empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	else print $langs->trans($val['label']);

    if ( $status->userCan($user, 'edit') && $permok && empty($val['noteditable']) && ($action != 'edit_attribute' || GETPOST('attribute') != $key))
    {
        $fieldid = 'id';
        if ($object->table_element == 'societe') $fieldid = 'socid';
        print '<td class="right"><a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?'.$fieldid.'='.$object->id.'&action=edit_attribute&attribute='.$key.'&ignorecollapsesetup=1">'.img_edit().'</a></td>';
    }
    print '</tr></table>';

	print '</td>';
	print '<td class="valuefield fieldname_'.$key;
	if ($val['type'] == 'text') print ' wordbreak';
	print '">';

    if ($action == 'edit_attribute' && $permok && GETPOST('attribute', 'none') == $key)
    {
        $fieldid = 'id';
        if ($object->table_element == 'societe') $fieldid = 'socid';
        print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formextra">';
        print '<input type="hidden" name="action" value="update_attribute">';
        print '<input type="hidden" name="attribute" value="'.$key.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="'.$fieldid.'" value="'.$object->id.'">';
        print $object->showInputField($val, $key, $value, '', '', '', 0, $object->id, $object->table_element);

        print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';

        print '</form>';
    }
    else
    {
        print $object->showOutputField($val, $key, $value, '', '', '', 0);
    }

	//print dol_escape_htmltag($object->$key, 1, 1);
	print '</td>';
	print '</tr>';
}

print '</table>';

// We close div and reopen for second column
print '</div>';
print '<div class="fichehalfright">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

$alreadyoutput = 1;
foreach ($object->fields as $key => $val)
{
	if ($alreadyoutput)
	{
		if (!empty($keyforbreak) && $key == $keyforbreak) {
			$alreadyoutput = 0; // key used for break on second column
		}
		else {
			continue;
		}
	}

	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) continue;

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !$val['enabled']) continue; // We don't want this field
	if (in_array($key, array('ref', 'status'))) continue; // Ref and status are already in dol_banner

	$value = $object->$key;

    print '<tr><td class="titlefield">';
    print '<table class="nobordernopadding centpercent">';
    print '<tr>';

	print '<td';
	print ' class="titlefield fieldname_'.$key;
	//if ($val['notnull'] > 0) print ' fieldrequired';		// No fieldrequired inthe view output
	if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
	print '">';
	if (!empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	else print $langs->trans($val['label']);

	if ( $status->userCan($user, 'edit') && $permok && empty($val['noteditable']) && ($action != 'edit_attribute' || GETPOST('attribute') != $key))
    {
        $fieldid = 'id';
        if ($object->table_element == 'societe') $fieldid = 'socid';
        print '<td class="right"><a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?'.$fieldid.'='.$object->id.'&action=edit_attribute&attribute='.$key.'&ignorecollapsesetup=1">'.img_edit().'</a></td>';
    }
    print '</tr></table>';

	print '</td>';
	print '<td>';
    if ($action == 'edit_attribute' && $permok && GETPOST('attribute', 'none') == $key)
    {
        $fieldid = 'id';
        if ($object->table_element == 'societe') $fieldid = 'socid';
        print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formextra">';
        print '<input type="hidden" name="action" value="update_attribute">';
        print '<input type="hidden" name="attribute" value="'.$key.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="'.$fieldid.'" value="'.$object->id.'">';
        print $object->showInputField($val, $key, $value, '', '', '', 0, $object->id, $object->table_element);

        print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';

        print '</form>';
    }
    else
    {
        print $object->showOutputField($val, $key, $value, '', '', '', 0);
    }
	//print dol_escape_htmltag($object->$key, 1, 1);
	print '</td>';
	print '</tr>';
}

?>
<!-- END PHP TEMPLATE commonfields_view.tpl.php -->
