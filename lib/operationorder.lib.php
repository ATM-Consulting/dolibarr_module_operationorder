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

/**
 *	\file		lib/operationorder.lib.php
 *	\ingroup	operationorder
 *	\brief		This file is an example module library
 *				Put some comments here
 */

/**
 * @return array
 */
function operationorderAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load('operationorder@operationorder');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/operationorder/admin/operationorder_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/operationorder/admin/operationorder_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'extrafields';
    $h++;
    $head[$h][0] = dol_buildpath("/operationorder/admin/operationorder_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@operationorder:/operationorder/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@operationorder:/operationorder/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'operationorder');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	OperationOrder	$object		Object company shown
 * @return 	array				Array of tabs
 */
function operationorder_prepare_head(OperationOrder $object)
{
    global $db, $langs, $conf;

    $langs->load("operationorder@operationorder");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/operationorder/operationorder_card.php", 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("OperationOrderCard");
    $head[$h][2] = 'card';
    $h++;

    if (isset($object->fields['note_public']) || isset($object->fields['note_private']))
    {
        $nbNote = 0;
        if (!empty($object->note_private)) $nbNote++;
        if (!empty($object->note_public)) $nbNote++;
        $head[$h][0] = dol_buildpath('/operationorder/note.php', 1).'?id='.$object->id;
        $head[$h][1] = $langs->trans('Notes');
        if ($nbNote > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
        $head[$h][2] = 'note';
        $h++;
    }

    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->operationorder->dir_output . "/operationorder/" . dol_sanitizeFileName($object->ref);
    $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    $nbLinks=Link::count($db, $object->element, $object->id);
    $head[$h][0] = dol_buildpath("/operationorder/document.php", 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans('Documents');
    if (($nbFiles+$nbLinks) > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.($nbFiles+$nbLinks).'</span>';
    $head[$h][2] = 'document';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@operationorder:/operationorder/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@operationorder:/operationorder/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'operationorder@operationorder');

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'operationorder@operationorder', 'remove');

    return $head;
}

/**
 * @param Form      $form       Form object
 * @param OperationOrder  $object     OperationOrder object
 * @param string    $action     Triggered action
 * @return string
 */
function getFormConfirmOperationOrder($form, $object, $action)
{
    global $langs, $user;

    $formconfirm = '';

    if ($action === 'valid' && !empty($user->rights->operationorder->write))
    {
        $body = $langs->trans('ConfirmValidateOperationOrderBody', $object->getRef());
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmValidateOperationOrderTitle'), $body, 'confirm_validate', '', 0, 1);
    }
    elseif ($action === 'close' && !empty($user->rights->operationorder->write))
    {
        $body = $langs->trans('ConfirmCloseOperationOrderBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCloseOperationOrderTitle'), $body, 'confirm_close', '', 0, 1);
    }
//    elseif ($action === 'accept' && !empty($user->rights->operationorder->write))
//    {
//        $body = $langs->trans('ConfirmAcceptOperationOrderBody', $object->ref);
//        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmAcceptOperationOrderTitle'), $body, 'confirm_accept', '', 0, 1);
//    }
//    elseif ($action === 'refuse' && !empty($user->rights->operationorder->write))
//    {
//        $body = $langs->trans('ConfirmRefuseOperationOrderBody', $object->ref);
//        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmRefuseOperationOrderTitle'), $body, 'confirm_refuse', '', 0, 1);
//    }
    elseif ($action === 'modify' && !empty($user->rights->operationorder->write))
    {
        $body = $langs->trans('ConfirmModifyOperationOrderBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmModifyOperationOrderTitle'), $body, 'confirm_modify', '', 0, 1);
    }
//    elseif ($action === 'reopen' && !empty($user->rights->operationorder->write))
//    {
//        $body = $langs->trans('ConfirmReopenOperationOrderBody', $object->ref);
//        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmReopenOperationOrderTitle'), $body, 'confirm_reopen', '', 0, 1);
//    }
    elseif ($action === 'delete' && !empty($user->rights->operationorder->write))
    {
        $body = $langs->trans('ConfirmDeleteOperationOrderBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeleteOperationOrderTitle'), $body, 'confirm_delete', '', 0, 1);
    }
    elseif ($action === 'clone' && !empty($user->rights->operationorder->write))
    {
        $body = $langs->trans('ConfirmCloneOperationOrderBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCloneOperationOrderTitle'), $body, 'confirm_clone', '', 0, 1);
    }
    elseif ($action == 'ask_deleteline')
    {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.GETPOST('lineid'), $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
    }
//    elseif ($action === 'cancel' && !empty($user->rights->operationorder->write))
//    {
//        $body = $langs->trans('ConfirmCancelOperationOrderBody', $object->ref);
//        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCancelOperationOrderTitle'), $body, 'confirm_cancel', '', 0, 1);
//    }

    return $formconfirm;
}
