<?php

if (!class_exists('SeedObject'))
{

    define('INC_FROM_DOLIBARR', true);
    require_once dirname(__FILE__).'/../config.php';
}


class OperationOrderHistory extends SeedObject
{

    /** @var string $table_element Table name in SQL */
    public $table_element = 'operationorderhistory';

    /** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
    public $element = 'operationorderhistory';

    /** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 0;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    /** @var string $picto a picture file in [@...]/img/object_[...@].png  */
    public $picto = 'operationorder@operationorder';

    public $fields=array(
        'date' => array('type'=>'date', 'label'=>'Date', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
        'fk_operationorder' => array('type'=>'text', 'label'=>'OperationOrder', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'desc' => array('type'=>'desc', 'label'=>'Description', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'fk_user_author' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'position'=>50, 'notnull'=>1, 'visible'=>1, 'foreignkey'=>'user.rowid',),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'position'=>60, 'notnull'=>1, 'visible'=>0,),
    );

    public $date;

    public $type;
    public $desc;

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
     * @param int  $limit       Limit element returned
     * @param bool $loadChild   used to load children from database
     * @param array $TFilter 	array off filters ('date' => '2020-11-13' OR 'date' => array('field' => 'date', 'operator' => '>', 'value' => '2020-11-13'))
     * @return self[]
     */
    public function fetchAll($limit = 0, $loadChild = true, $TFilter = array())
    {
        $TRes = array();

        $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE 1';
        if (!empty($TFilter))
        {
            foreach ($TFilter as $field => $value)
            {
                if (!is_array($value)) $sql.= ' AND '.$field.' = '.$this->quote($value, $this->fields[$field]);
                else
                {
                    if (!empty($value['field']) && !empty($value['operator']) && !empty($value['value'])) $sql.=  ' AND '.$value['field']. ' ' . $value['operator'] . ' ' . $this->quote($value['value'], $this->fields[$field]);
                }
            }
        }
        if ($limit) $sql.= ' LIMIT '.$limit;

        $sql.= ' ORDER BY date DESC';

        $resql = $this->db->query($sql);
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $o = new static($this->db);
                $o->fetch($obj->rowid, $loadChild);

                $TRes[] = $o;
            }
        }

        return $TRes;
    }

    public function compareAndSaveDiff($oldcopy, $object) {
        global $langs;
        $this->desc = '';
        $TDiff = $this->recursiveArrayDiff((array) $oldcopy, (array) $object);
        if(!empty($TDiff)) {
            if(array_key_exists('array_options', $TDiff)) {
                $extrafields = new ExtraFields($this->db);
                $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
            }
            foreach($TDiff as $keyDiff => $diff) {
                if($keyDiff == 'fields' || $keyDiff == 'oldcopy' || ($object->element == 'operationorderdet' && $keyDiff == 'label')) continue;
                if(!is_array($diff)) {
                    if(!empty($object->fields[$keyDiff])) {
                        $oldvalue = $oldcopy->showOutputFieldQuick($keyDiff);
                        $newvalue = $object->showOutputFieldQuick($keyDiff);
                    }
                    else {
                        $oldvalue = $diff;
                        $newvalue = $object->{$keyDiff};
                    }
                    $this->desc .= $langs->transnoentitiesnoconv($object->fields[$keyDiff]['label']).' : '.$oldvalue.' => '.$newvalue .' <br/>';
                }
                if($keyDiff == 'array_options') {
                    foreach($diff as $keyExtra => $diffExtra) {
                        $keyExtra = str_replace('options_', '', $keyExtra);
                        $oldvalue = $extrafields->showOutputField($keyExtra, $diffExtra);
                        $newvalue = $extrafields->showOutputField($keyExtra, $object->array_options[$diffExtra]);
                        $this->desc .= $langs->transnoentitiesnoconv($extralabels[$keyExtra]).' : '.$oldvalue.' => '.$newvalue .' <br/>';
                    }
                }
            }
        }
    }


    public function recursiveArrayDiff($a1, $a2) {
        $r = array();
        foreach ($a1 as $k => $v) {
            if (array_key_exists($k, $a2)) {
                if (is_array($v)) {
                    $rad = $this->recursiveArrayDiff($v, $a2[$k]);
                    if (count($rad)) { $r[$k] = $rad; }
                } else {
                    if ($v != $a2[$k] && (!empty($v) || !empty($a2[$k]))) {
                        $r[$k] = $v;
                    }
                }
            } else if(!empty($v)){
                $r[$k] = $v;
            }
        }
        return $r;
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
