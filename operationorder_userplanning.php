<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('operationorder/class/operationorderplanning.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');


$usercanmodify = $user->rights->operationorder->userplanning->write | $user->rights->operationorder->usergroupplanning->write;

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'userplanning';
$userid = GETPOST('userid');
$usergroupid = GETPOST('usergroupid');
$action = GETPOST('action');

$hookmanager->initHooks(array('userplanning'));


/* VIEW */

llxHeader('', $langs->trans("UserPlanning"));

if(!empty($userid))
{
    $object = new User($db);
    $object->fetch($userid);
    $form = new Form($db);

    $head = user_prepare_head($object);

    $title = $langs->trans("User");
    dol_fiche_head($head, 'userplanning', $title, -1, 'user');

    if($action == 'edit')
    {
        print getUserPlanning($object, $action);


    } else {

        print getUserPlanning($object, $action);

    }

} elseif (!empty($usergroupid))
{
    $object = new Usergroup($db);
    $object->fetch($usergroupid);

    $form = new Form($db);

    $head = group_prepare_head($object);
    $title = $langs->trans("Group");
    dol_fiche_head($head, 'usergroupplanning', $title, -1, 'group');

    if($action == 'edit')
    {
        print userGroupPlanning($object, $action);



    } else {

        print userGroupPlanning($object, $action);

    }

}
