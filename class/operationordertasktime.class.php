<?php

if (!class_exists('SeedObject'))
{

    define('INC_FROM_DOLIBARR', true);
    require_once dirname(__FILE__).'/../config.php';
}


class OperationOrderTaskTime extends SeedObject
{

    /** @var string $table_element Table name in SQL */
    public $table_element = 'operationordertasktime';

    /** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
    public $element = 'operationordertasktime';

    /** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    /** @var string $picto a picture file in [@...]/img/object_[...@].png  */
    public $picto = 'operationorder@operationorder';

	/** @var int $fullday flag for event display like full day event */
	public $fullday = 1;

    public $fields=array(
        'label' => array('type'=>'string', 'label'=>'Label', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
        'task_datehour_d' => array('type'=>'datetime', 'label'=>'DateD', 'enabled'=>1, 'position'=>170, 'notnull'=>1, 'visible'=>1),
        'task_datehour_f' => array('type'=>'datetime', 'label'=>'DateF', 'enabled'=>1, 'position'=>180, 'notnull'=>0, 'visible'=>1),
        'task_duration' => array('type'=>'int', 'label'=>'Duration', 'enabled'=>1, 'position'=>190, 'notnull'=>0, 'visible'=>1),
        'fk_user' => array('type'=>'int', 'label'=>'User', 'enabled'=>1, 'position'=>200, 'notnull'=>1, 'visible'=>1),
        'fk_orDet' => array('type'=>'int', 'label'=>'ORDet', 'enabled'=>1, 'position'=>210, 'notnull'=>1, 'visible'=>1),
        'entity' => array('type'=>'int', 'label'=>'Entity', 'enabled'=>1, 'position'=>210, 'notnull'=>1, 'visible'=>1),
    );

    public $label;
    public $task_datehour_d;
    public $task_datehour_f;
    public $task_duration;
    public $fk_user;
    public $fk_orDet;
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
        $this->task_datehour_f = null;

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

	/**
	 * @param int $user_id ID du user dont on veut le compteur courant
	 * @return int
	 */
    public function fetchCourantCounter($user_id)
	{
		global $conf;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE fk_user = ".$user_id;
		$sql.= " AND task_datehour_f IS NULL";
		$sql.= " AND entity = " . $conf->entity;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->fetch($obj->rowid);

				if ($this->id)
				{
					return $this->id;
				}
				else return -2;
			}
			else return 0;
		}
		else return -1;
	}

}
