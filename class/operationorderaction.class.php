<?php

if (!class_exists('SeedObject'))
{

    define('INC_FROM_DOLIBARR', true);
    require_once dirname(__FILE__).'/../config.php';
}


class OperationOrderAction extends SeedObject
{

    /** @var string $table_element Table name in SQL */
    public $table_element = 'operationorderaction';

    /** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
    public $element = 'operationorderaction';

    /** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    /** @var string $picto a picture file in [@...]/img/object_[...@].png  */
    public $picto = 'operationorder@operationorder';

    public $fields=array(
        'date_operationorder' => array('type'=>'date', 'label'=>'Date', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1,),
        'heured' => array('type'=>'datetime', 'label'=>'DateD', 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'visible'=>1,),
        'heurf' => array('type'=>'datetime', 'label'=>'DateF', 'enabled'=>1, 'position'=>30, 'notnull'=>1, 'visible'=>1,),
        'note_private' => array('type'=>'text', 'label'=>'NotePrivate', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'fk_operationorder' => array('type'=>'integer:OperationOrder:operationorder/class/operationorder.class.php:1:entity IN (0, __ENTITY__)', 'label'=>'OperationOrder', 'enabled'=>1, 'position'=>90, 'visible'=>1, 'foreignkey'=>'operationorder.rowid',),
        'fk_user_author' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'position'=>50, 'notnull'=>1, 'visible'=>1, 'foreignkey'=>'user.rowid',),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'position'=>60, 'notnull'=>1, 'visible'=>0,),
    );

    public $fk_operationorder;

    public $date_operationorder;

    public $heured;

    public $heuref;

    public $note_private;

    public $fk_user_author;

    public $entity;


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

}