<?php

if (!class_exists('SeedObject'))
{

    define('INC_FROM_DOLIBARR', true);
    require_once dirname(__FILE__).'/../config.php';
}


class OperationOrderUserPlanning extends SeedObject
{

    /** @var string $table_element Table name in SQL */
    public $table_element = 'operationorderuserplanning';

    /** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
    public $element = 'operationorderuserplanning';

    /** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    /** @var string $picto a picture file in [@...]/img/object_[...@].png  */
    public $picto = 'operationorder@operationorder';

	/** @var int $fullday flag for event display like full day event */
	public $fullday = 1;

    public $fields=array(
        'fk_object' => array('type'=>'integer', 'label'=>'Object', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
        'object_type' => array('type'=>'varchar(255)', 'label'=>'ObjectType', 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'visible'=>1),
        'lundiam' => array('type'=>'integer', 'label'=>'lundiam', 'enabled'=>1, 'position'=>30, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'lundipm' => array('type'=>'integer', 'label'=>'lundipm', 'enabled'=>1, 'position'=>40, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'mardiam' => array('type'=>'integer', 'label'=>'mardiam', 'enabled'=>1, 'position'=>50, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'mardipm' => array('type'=>'integer', 'label'=>'mardipm', 'enabled'=>1, 'position'=>60, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'mercrediam' => array('type'=>'integer', 'label'=>'mercrediam', 'enabled'=>1, 'position'=>70, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'mercredipm' => array('type'=>'integer', 'label'=>'mercredipm', 'enabled'=>1, 'position'=>80, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'jeudiam' => array('type'=>'integer', 'label'=>'jeudiam', 'enabled'=>1, 'position'=>90, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'jeudipm' => array('type'=>'integer', 'label'=>'jeudipm', 'enabled'=>1, 'position'=>100, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'vendrediam' => array('type'=>'integer', 'label'=>'vendrediam', 'enabled'=>1, 'position'=>110, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'vendredipm' => array('type'=>'integer', 'label'=>'vendredipm', 'enabled'=>1, 'position'=>120, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'samediam' => array('type'=>'integer', 'label'=>'samediam', 'enabled'=>1, 'position'=>130, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'samedipm' => array('type'=>'integer', 'label'=>'samedipm', 'enabled'=>1, 'position'=>140, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'dimancheam' => array('type'=>'integer', 'label'=>'dimancheam', 'enabled'=>1, 'position'=>150, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'dimanchepm' => array('type'=>'integer', 'label'=>'dimanchepm', 'enabled'=>1, 'position'=>160, 'notnull'=>1, 'visible'=>1, 'default'=>0),
        'lundi_heuredam' => array('type'=>'varchar(5)', 'label'=>'lundiheuredam', 'enabled'=>1, 'position'=>170, 'notnull'=>1, 'visible'=>1),
        'lundi_heurefam' => array('type'=>'varchar(5)', 'label'=>'lundiheurefam', 'enabled'=>1, 'position'=>180, 'notnull'=>1, 'visible'=>1),
        'lundi_heuredpm' => array('type'=>'varchar(5)', 'label'=>'lundiheuredpm', 'enabled'=>1, 'position'=>190, 'notnull'=>1, 'visible'=>1),
        'lundi_heurefpm' => array('type'=>'varchar(5)', 'label'=>'lundiheurefpm', 'enabled'=>1, 'position'=>200, 'notnull'=>1, 'visible'=>1),
        'mardi_heuredam' => array('type'=>'varchar(5)', 'label'=>'mardiheuredam', 'enabled'=>1, 'position'=>210, 'notnull'=>1, 'visible'=>1),
        'mardi_heurefam' => array('type'=>'varchar(5)', 'label'=>'mardiheurefam', 'enabled'=>1, 'position'=>220, 'notnull'=>1, 'visible'=>1),
        'mardi_heuredpm' => array('type'=>'varchar(5)', 'label'=>'mardiheuredpm', 'enabled'=>1, 'position'=>230, 'notnull'=>1, 'visible'=>1),
        'mardi_heurefpm' => array('type'=>'varchar(5)', 'label'=>'mardiheurefpm', 'enabled'=>1, 'position'=>240, 'notnull'=>1, 'visible'=>1),
        'mercredi_heuredam' => array('type'=>'varchar(5)', 'label'=>'mercrediheuredam', 'enabled'=>1, 'position'=>250, 'notnull'=>1, 'visible'=>1),
        'mercredi_heurefam' => array('type'=>'varchar(5)', 'label'=>'mercrediheurefam', 'enabled'=>1, 'position'=>260, 'notnull'=>1, 'visible'=>1),
        'mercredi_heuredpm' => array('type'=>'varchar(5)', 'label'=>'mercrediheuredpm', 'enabled'=>1, 'position'=>270, 'notnull'=>1, 'visible'=>1),
        'mercredi_heurefpm' => array('type'=>'varchar(5)', 'label'=>'mercrediheurefpm', 'enabled'=>1, 'position'=>280, 'notnull'=>1, 'visible'=>1),
        'jeudi_heuredam' => array('type'=>'varchar(5)', 'label'=>'jeudiheuredam', 'enabled'=>1, 'position'=>290, 'notnull'=>1, 'visible'=>1),
        'jeudi_heurefam' => array('type'=>'varchar(5)', 'label'=>'jeudiheurefam', 'enabled'=>1, 'position'=>300, 'notnull'=>1, 'visible'=>1),
        'jeudi_heuredpm' => array('type'=>'varchar(5)', 'label'=>'jeudiheuredpm', 'enabled'=>1, 'position'=>310, 'notnull'=>1, 'visible'=>1),
        'jeudi_heurefpm' => array('type'=>'varchar(5)', 'label'=>'jeudiheurefpm', 'enabled'=>1, 'position'=>320, 'notnull'=>1, 'visible'=>1),
        'vendredi_heuredam' => array('type'=>'varchar(5)', 'label'=>'vendrediheuredam', 'enabled'=>1, 'position'=>330, 'notnull'=>1, 'visible'=>1),
        'vendredi_heurefam' => array('type'=>'varchar(5)', 'label'=>'vendrediheurefam', 'enabled'=>1, 'position'=>340, 'notnull'=>1, 'visible'=>1),
        'vendredi_heuredpm' => array('type'=>'varchar(5)', 'label'=>'vendrediheuredpm', 'enabled'=>1, 'position'=>350, 'notnull'=>1, 'visible'=>1),
        'vendredi_heurefpm' => array('type'=>'varchar(5)', 'label'=>'vendrediheurefpm', 'enabled'=>1, 'position'=>360, 'notnull'=>1, 'visible'=>1),
        'samedi_heuredam' => array('type'=>'varchar(5)', 'label'=>'samediheuredam', 'enabled'=>1, 'position'=>370, 'notnull'=>1, 'visible'=>1),
        'samedi_heurefam' => array('type'=>'varchar(5)', 'label'=>'samediheurefam', 'enabled'=>1, 'position'=>380, 'notnull'=>1, 'visible'=>1),
        'samedi_heuredpm' => array('type'=>'varchar(5)', 'label'=>'samediheuredpm', 'enabled'=>1, 'position'=>390, 'notnull'=>1, 'visible'=>1),
        'samedi_heurefpm' => array('type'=>'varchar(5)', 'label'=>'samediheurefpm', 'enabled'=>1, 'position'=>400, 'notnull'=>1, 'visible'=>1),
        'dimanche_heuredam' => array('type'=>'varchar(5)', 'label'=>'dimancheheuredam', 'enabled'=>1, 'position'=>410, 'notnull'=>1, 'visible'=>1),
        'dimanche_heurefam' => array('type'=>'varchar(5)', 'label'=>'dimancheheurefam', 'enabled'=>1, 'position'=>420, 'notnull'=>1, 'visible'=>1),
        'dimanche_heuredpm' => array('type'=>'varchar(5)', 'label'=>'dimancheheuredpm', 'enabled'=>1, 'position'=>430, 'notnull'=>1, 'visible'=>1),
        'dimanche_heurefpm' => array('type'=>'varchar(5)', 'label'=>'dimancheheurefpm', 'enabled'=>1, 'position'=>440, 'notnull'=>1, 'visible'=>1),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'position'=>450, 'notnull'=>1, 'visible'=>0,),
        'active' => array('type'=>'integer', 'label'=>'Active', 'enabled'=>1, 'position'=>460, 'notnull'=>1, 'visible'=>0, 'default'=>0),
    );

    public $fk_object;
    public $object_type;
    public $lundiam;
    public $lundipm;
    public $mardiam;
    public $mardipm;
    public $mercrediam;
    public $mercredipm;
    public $jeudiam;
    public $jeudipm;
    public $vendrediam;
    public $vendredipm;
    public $samediam;
    public $samedipm;
    public $dimancheam;
    public $dimanchepm;
    public $lundi_heuredam;
    public $lundi_heurefam;
    public $lundi_heuredpm;
    public $lundi_heurefpm;
    public $mardi_heuredam;
    public $mardi_heurefam;
    public $mardi_heuredpm;
    public $mardi_heurefpm;
    public $mercredi_heuredam;
    public $mercredi_heurefam;
    public $mercredi_heuredpm;
    public $mercredi_heurefpm;
    public $jeudi_heuredam;
    public $jeudi_heurefam;
    public $jeudi_heuredpm;
    public $jeudi_heurefpm;
    public $vendredi_heuredam;
    public $vendredi_heurefam;
    public $vendredi_heuredpm;
    public $vendredi_heurefpm;
    public $samedi_heuredam;
    public $samedi_heurefam;
    public $samedi_heuredpm;
    public $samedi_heurefpm;
    public $dimanche_heuredam;
    public $dimanche_heurefam;
    public $dimanche_heuredpm;
    public $dimanche_heurefpm;
    public $entity;
    public $active;


    /**
     * OperationOrder constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        global $conf;

        parent::__construct($db);

        $this->init();

        $this->entity = $conf->entity;
    }

    /**
     * @param User $user User object
     * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return int
     */
    public function save($user, $notrigger = false)
    {
        return parent::create($user, $notrigger);
    }

    /**
     * Function to update object or create or delete if needed
     *
     * @param   User    $user   user object
     * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return  int                < 0 if ko, > 0 if ok
     */
    public function update(User &$user, $notrigger = false)
    {
        return parent::update($user, $notrigger);
    }

    /**
     * @param User $user User object
     * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return int
     */
    public function delete(User &$user, $notrigger = false)
    {
        return parent::delete($user, $notrigger);
    }

    public function fetchByObject($fk_object, $object_type){

        global $db, $conf;

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE fk_object = '".$fk_object."' AND object_type = '".$object_type."'";
        $resql = $db->query($sql);
        if($resql){
            $obj = $db->fetch_object($resql);

            $res = $this->fetch($obj->rowid);

            return $res;

        } else {
            return -1;
        }

    }

}
