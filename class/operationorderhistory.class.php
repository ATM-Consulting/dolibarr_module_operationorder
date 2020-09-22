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
        'date_creation' => array('type'=>'datetime', 'label'=>'Date', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
        'fk_operationorder' => array('type'=>'integer', 'label'=>'OperationOrderId', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'fk_operationorderdet' => array('type'=>'integer', 'label'=>'OperationOrderDetId', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'title' => array('type'=>'varchar', 'label'=>'Title', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'position'=>50, 'notnull'=>1, 'visible'=>1, 'foreignkey'=>'user.rowid',),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'position'=>60, 'notnull'=>1, 'visible'=>0,),
    );

    public $date_creation;

    public $fk_operationorder;
    public $fk_operationorderdet;
    public $title;
    public $description;

    public $fk_user_creat;

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

    public function stockMvt($object, $product, $qty) {
	    global $langs;
	    $langs->load('operationorder@operationorder');
	    require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
	    $warehouse = new Entrepot($this->db);
	    $warehouse->fetch($product->fk_default_warehouse);
	    $this->title = $langs->transnoentitiesnoconv('OOStockMvt', $object->ref);
	    $this->description = $langs->transnoentitiesnoconv('OOStockMvtDetail', $product->ref, $qty, $warehouse->ref);
	    $this->fk_operationorder = $object->id;
	    $useradm=new User($this->db);
	    $useradm->fetch(1);
	    return $this->save($useradm);
    }

    public function compareAndSaveDiff($oldcopy, &$object) {
        global $langs, $user;
        if(strpos($object->element, 'det') !== false) $this->title = $langs->transnoentitiesnoconv('OOLineUpdate', OperationOrder::getStaticRef($object->fk_operation_order), $object->getProductRef());
        else $this->title = $langs->transnoentitiesnoconv('OOUpdate', $object->ref);
        $this->description = '';
        if($object->is_clone) return;
        $TDiff = $this->recursiveArrayDiff((array) $oldcopy, (array) $object);
        if(!empty($TDiff)) {
            if(array_key_exists('array_options', $TDiff)) {
                $extrafields = new ExtraFields($this->db);
                $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
            }
            foreach($TDiff as $keyDiff => $diff) {
            	if($keyDiff == 'objStatus' && is_object($diff)) $diff = $diff->label;
                if($keyDiff == 'fields' || $keyDiff == 'oldcopy' || ($object->element == 'operationorderdet' && $keyDiff == 'label') || is_object($diff)) continue;
                if(!is_array($diff)) {
                    if(!empty($object->fields[$keyDiff])) {
                        $oldvalue = $oldcopy->showOutputFieldQuick($keyDiff);
                        $newvalue = $object->showOutputFieldQuick($keyDiff);
                    }
                    else if($keyDiff == 'objStatus') {
	                    $object->fields[$keyDiff]['label'] = 'Status';
                        $oldvalue = $diff;
                        $newvalue = $object->objStatus->label;
                    } else {
	                    $oldvalue = $diff;
	                    $newvalue = $object->{$keyDiff};
                    }
                    $this->description .= $langs->transnoentitiesnoconv($object->fields[$keyDiff]['label']).' : '.$oldvalue.' => '.$newvalue .' <br/>';
                }
                if($keyDiff == 'array_options') {
                    foreach($diff as $keyExtra => $diffExtra) {
                        $keyExtra = str_replace('options_', '', $keyExtra);
                        $oldvalue = $extrafields->showOutputField($keyExtra, $diffExtra);
                        $newvalue = $extrafields->showOutputField($keyExtra, $object->array_options['options_'.$keyExtra]);
                        $this->description .= $langs->transnoentitiesnoconv($extralabels[$keyExtra]).' : '.$oldvalue.' => '.$newvalue .' <br/>';
                    }
                }
            }
        }
        if(!empty($this->description)) { // Something has been updated
            if(strpos($object->element, 'det') !== false) {
                $this->fk_operationorder = $object->fk_operation_order;
                $this->fk_operationorderdet = $object->id;
            } else {
                $this->fk_operationorder = $object->id;
            }
            $this->save($user);
        }
        $object->oldcopy->time_planned_t = $object->time_planned_t;
    }

    public function saveCreationOrDeletion(&$object, $type = 'create') {
        global $langs, $user;
        if(strpos($object->element, 'det') !== false) {
            if(!empty($object->parent->ref)) $parentref = $object->parent->ref;
            else $parentref = OperationOrder::getStaticRef($object->fk_operation_order);
            if($type == 'create') $this->title = $langs->transnoentitiesnoconv('OOLineCreate', OperationOrder::getStaticRef($object->fk_operation_order), $object->getProductRef());
            else $this->title = $langs->transnoentitiesnoconv('OOLineDelete', $parentref, $object->getProductRef());
            $this->description = $langs->transnoentitiesnoconv($object->fields['qty']['label']). ' : '.$object->showOutputFieldQuick('qty');
            $this->fk_operationorder = $object->fk_operation_order;
            $this->fk_operationorderdet = $object->id;
        }
        else {
            if($type == 'create') $this->title = $langs->transnoentitiesnoconv('OOCreate', $object->ref);
            else $this->title = $langs->transnoentitiesnoconv('OODelete', $object->ref);

            $this->description = $langs->transnoentitiesnoconv($object->fields['fk_soc']['label']). ' : '.$object->showOutputFieldQuick('fk_soc');
            if(!empty($object->array_options)) {
                $extrafields = new ExtraFields($this->db);
                $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
                foreach($object->array_options as $keyExtra => $valExtra) {
                    if(!empty($valExtra)) {
                        $keyExtra = str_replace('options_', '', $keyExtra);
                        $this->description .= '</br>';
                        $this->description .= $langs->transnoentitiesnoconv($extralabels[$keyExtra]).' : '.$extrafields->showOutputField($keyExtra, $valExtra);
                    }
                }

            }
            $this->fk_operationorder = $object->id;
        }
        $this->save($user);
        $object->oldcopy = $object;
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
