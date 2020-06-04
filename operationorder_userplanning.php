<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('operationorder/class/operationorderuserplanning.class.php');
dol_include_once('operationorder/lib/operationorder.lib.php');

//rights
$usercanmodifyuserplanning = $user->rights->operationorder->userplanning->write;
$usercanmodifygroupplanning = $user->rights->operationorder->usergroupplanning->write;

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'userplanning';
$objecttype = GETPOST('objecttype');
$objectid = GETPOST('objectid');
$action = GETPOST('action');
$datasplanning = array();
$datasplanning['lundi_heuredam'] = GETPOST('lundi_heuredam');
$datasplanning['lundi_heurefam'] = GETPOST('lundi_heurefam');
$datasplanning['lundi_heuredpm'] = GETPOST('lundi_heuredpm');
$datasplanning['lundi_heurefpm'] = GETPOST('lundi_heurefpm');
$datasplanning['mardi_heuredam'] = GETPOST('mardi_heuredam');
$datasplanning['mardi_heurefam'] = GETPOST('mardi_heurefam');
$datasplanning['mardi_heuredpm'] = GETPOST('mardi_heuredpm');
$datasplanning['mardi_heurefpm'] = GETPOST('mardi_heurefpm');
$datasplanning['mercredi_heuredam'] = GETPOST('mercredi_heuredam');
$datasplanning['mercredi_heurefam'] = GETPOST('mercredi_heurefam');
$datasplanning['mercredi_heuredpm'] = GETPOST('mercredi_heuredpm');
$datasplanning['mercredi_heurefpm'] = GETPOST('mercredi_heurefpm');
$datasplanning['jeudi_heuredam'] = GETPOST('jeudi_heuredam');
$datasplanning['jeudi_heurefam'] = GETPOST('jeudi_heurefam');
$datasplanning['jeudi_heuredpm'] = GETPOST('jeudi_heuredpm');
$datasplanning['jeudi_heurefpm'] = GETPOST('jeudi_heurefpm');
$datasplanning['vendredi_heuredam'] = GETPOST('vendredi_heuredam');
$datasplanning['vendredi_heurefam'] = GETPOST('vendredi_heurefam');
$datasplanning['vendredi_heuredpm'] = GETPOST('vendredi_heuredpm');
$datasplanning['vendredi_heurefpm'] = GETPOST('vendredi_heurefpm');
$datasplanning['samedi_heuredam'] = GETPOST('samedi_heuredam');
$datasplanning['samedi_heurefam'] = GETPOST('samedi_heurefam');
$datasplanning['samedi_heuredpm'] = GETPOST('samedi_heuredpm');
$datasplanning['samedi_heurefpm'] = GETPOST('samedi_heurefpm');
$datasplanning['dimanche_heuredam'] = GETPOST('dimanche_heuredam');
$datasplanning['dimanche_heurefam'] = GETPOST('dimanche_heurefam');
$datasplanning['dimanche_heuredpm'] = GETPOST('dimanche_heuredpm');
$datasplanning['dimanche_heurefpm'] = GETPOST('dimanche_heurefpm');
$datasplanning['lundiam'] = !empty(GETPOST('lundiam')) ? GETPOST('lundiam') : 0;
$datasplanning['lundipm'] = !empty(GETPOST('lundipm')) ? GETPOST('lundipm') : 0;
$datasplanning['mardiam'] = !empty(GETPOST('mardiam')) ? GETPOST('mardiam') : 0;
$datasplanning['mardipm'] = !empty(GETPOST('mardipm')) ? GETPOST('mardipm') : 0;
$datasplanning['mercrediam'] = !empty(GETPOST('mercrediam')) ? GETPOST('mercrediam') : 0;
$datasplanning['mercredipm'] = !empty(GETPOST('mercredipm')) ? GETPOST('mercredipm') : 0;
$datasplanning['jeudiam'] = !empty(GETPOST('jeudiam')) ? GETPOST('jeudiam') : 0;
$datasplanning['jeudipm'] = !empty(GETPOST('jeudipm')) ? GETPOST('jeudipm') : 0;
$datasplanning['vendrediam'] = !empty(GETPOST('vendrediam')) ? GETPOST('vendrediam') : 0;
$datasplanning['vendredipm'] = !empty(GETPOST('vendredipm')) ? GETPOST('vendredipm') : 0;
$datasplanning['samediam'] = !empty(GETPOST('samediam')) ? GETPOST('samediam') : 0;
$datasplanning['samedipm'] = !empty(GETPOST('samedipm')) ? GETPOST('samedipm') : 0;
$datasplanning['dimancheam'] = !empty(GETPOST('dimancheam')) ? GETPOST('dimancheam') : 0;
$datasplanning['dimanchepm'] = !empty(GETPOST('dimanchepm')) ? GETPOST('dimanchepm') : 0;


if($objecttype == 'user')
{
    $hookmanager->initHooks(array('userplanning'));
    $object = new User($db);
    $object->fetch($objectid);
}
elseif ($objecttype == 'usergroup')
{
    $hookmanager->initHooks(array('usergroupplanning'));
    $object = new Usergroup($db);
    $object->fetch($objectid);
}

$userplanning = new OperationOrderUserPlanning($db);
$res = $userplanning->fetchByObject($objectid, $objecttype);

if($res < 0){
    $userplanning->fk_object = $objectid;
    $userplanning->object_type = $objecttype;
    $res = $userplanning->save($user);
}

/* ACTIONS */

if($action == 'save'){

    if(!empty($datasplanning)){

        foreach ($datasplanning as $key=>$value){
            $userplanning->$key = $value;
        }

        $userplanning->active = 1;

        $userplanning->save($user);

    }

    header('Location: '.$_SERVER['PHP_SELF'].'?objectid='.$objectid.'&objecttype='.$objecttype);
}

/* VIEW */

llxHeader('', $langs->trans("UserPlanning"));


if($objecttype == 'user')
{
    $head = user_prepare_head($object);
    $title = $langs->trans("User");
    dol_fiche_head($head, 'userplanning', $title, -1, 'user');

    print getUserPlanning($object, $objecttype, $action, $usercanmodifyuserplanning);

} elseif ($objecttype == 'usergroup')
{

    $head = group_prepare_head($object);
    $title = $langs->trans("Group");
    dol_fiche_head($head, 'usergroupplanning', $title, -1, 'group');

    print getUserPlanning($object, 'usergroup', $action, $usercanmodifygroupplanning);

}
