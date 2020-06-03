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
$objecttype = GETPOST('objecttype');
$objectid = GETPOST('objectid');
$action = GETPOST('action');

$hookmanager->initHooks(array('userplanning'));


if($action == 'save'){

    if($objecttype=='user')
    {
        header('Location: '.$_SERVER['PHP_SELF'].'?objectid='.$objectid.'&objecttype='.$objecttype);
    }
    elseif ($objecttype=='usergroup')
    {
        header('Location: '.$_SERVER['PHP_SELF'].'?objectid='.$objectid .'&objecttype='.$objecttype);
    }

}

/* VIEW */

llxHeader('', $langs->trans("UserPlanning"));


if($objecttype == 'user')
{

    $object = new User($db);
    $object->fetch($objectid);
    $form = new Form($db);

    $head = user_prepare_head($object);

    $title = $langs->trans("User");
    dol_fiche_head($head, 'userplanning', $title, -1, 'user');

    print getUserPlanning($object, $objecttype, $action);

} elseif ($objecttype == 'usergroup')
{
    $object = new Usergroup($db);
    $object->fetch($objectid);

    $form = new Form($db);

    $head = group_prepare_head($object);
    $title = $langs->trans("Group");
    dol_fiche_head($head, 'usergroupplanning', $title, -1, 'group');


    print getUserPlanning($object, 'usergroup', $action);

}
