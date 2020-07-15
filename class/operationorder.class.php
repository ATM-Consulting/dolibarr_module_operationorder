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

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once __DIR__ . '/unitstools.class.php';
require_once __DIR__ . '/operationorderstatus.class.php';
require_once __DIR__ . '/operationorderaction.class.php';
require_once __DIR__ . '/operationorderhistory.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';


class OperationOrder extends SeedObject
{


	/** @var string $table_element Table name in SQL */
	public $table_element = 'operationorder';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'operationorder';

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    /** @var $objStatus OperationOrderStatus used for cache */
    public $objStatus;

	/** @var string $picto a picture file in [@...]/img/object_[...@].png  */
	public $picto = 'operationorder@operationorder';

    /**
     *  'type' is the field format.
     *  'label' the translation key.
     *  'enabled' is a condition when the field must be managed.
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
     *  'noteditable' says if field is not editable (1 or 0)
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
     *  'default' is a default value for creation (can still be replaced by the global setup of default values)
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'position' is the sort order of field.
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
     *  'css' is the CSS style to use on field. For example: 'maxwidth200'
     *  'help' is a string visible as a tooltip on field
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
     */

    public $fields=array(
//        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'visible'=>-1, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
        'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
        'ref_client' => array('type'=>'varchar(128)', 'label'=>'RefCustomer', 'enabled'=>1, 'position'=>20, 'notnull'=>0, 'visible'=>1),
        'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1:status=1 AND entity IN (__SHARED_ENTITIES__)', 'label'=>'ThirdParty', 'enabled'=>1, 'position'=>50, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'help'=>"LinkToThirparty"),
        'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1', 'label'=>'Project', 'enabled'=>1, 'position'=>52, 'notnull'=>0, 'visible'=>1, 'index'=>1),
        'date_valid' => array('type'=>'datetime', 'label'=>'DateValid', 'enabled'=>1, 'position'=>56, 'notnull'=>0, 'visible'=>-2,),
        'date_cloture' => array('type'=>'datetime', 'label'=>'DateClose', 'enabled'=>1, 'position'=>57, 'notnull'=>0, 'visible'=>-2,),
        'date_operation_order' => array('type'=>'datetime', 'label'=>'DateOperationOrder', 'enabled'=>1, 'position'=>58, 'notnull'=>1, 'visible'=>-1, 'noteditable' => 0),
        'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>1, 'position'=>61, 'notnull'=>0, 'visible'=>0),
        'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>1, 'position'=>62, 'notnull'=>0, 'visible'=>0),

        'fk_c_operationorder_type' => array('type'=>'integer:OperationOrderDictType:operationorder/class/operationorder.class.php:1:entity IN (0, __ENTITY__)', 'label'=>'OperationOrderType', 'enabled'=>1, 'position'=>90, 'visible'=>1, 'foreignkey'=>'c_operationorder_type.rowid',),

        'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
        'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'position'=>511, 'notnull'=>0, 'visible'=>0,),
        'fk_user_valid' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserValid', 'enabled'=>1, 'position'=>512, 'notnull'=>0, 'visible'=>0,),
        'fk_user_cloture' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserClose', 'enabled'=>1, 'position'=>513, 'notnull'=>0, 'visible'=>0,),
        'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
        'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
        'status' => array('type'=>'int', 'label'=>'Status', 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'visible'=>2, 'index'=>1, 'arrayofkeyval'=> array(-1 => 'OperationOrderStatusShortCanceled', 0 => 'OperationOrderStatusShortDraft', 1 => 'OperationOrderStatusShortValidated')),
        'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>1, 'position'=>50, 'notnull'=>0, 'visible'=>0,),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'position'=>1200, 'notnull'=>1, 'visible'=>0,),
        'time_planned_t' => array ('type' => 'integer', 'label' => 'TimePlannedTheoretical', 'enabled' => 1, 'position' => 1300, 'notnull' => 1, 'visible' => 4, 'default' => 0, 'noteditable' => 1, 'help'=>"HoursMinFormat"),
        'time_planned_f' => array ('type' => 'integer', 'label' => 'TimePlannedForced', 'enabled' => 1, 'position' => 1400, 'notnull' => 0, 'visible' => 1, 'help'=>"HoursMinFormat"),
        'planned_date' => array ('type' => 'datetime', 'label' => 'PlannedDate', 'enabled' => 1, 'position' => 1500, 'notnull' => 0, 'visible' => 1),
		'date_creation' => array ('type' => 'datetime', 'label' => 'DateCreationOperationOrder', 'enabled' => 1, 'position' => 1600, 'notnull' => 1, 'visible' => 4,'noteditable'=>'1'),
    );

    public $ref;
    public $ref_client;
    public $fk_soc;
    public $fk_project;
    public $fk_contrat;
    public $date_valid;
    public $date_cloture;
    public $date_operation_order;
    public $note_public;
    public $note_private;
//    public $fk_multicurrency;
//    public $multicurrency_code;
//    public $multicurrency_subprice;
//    public $multicurrency_total_ht;
//    public $multicurrency_total_tva;
//    public $multicurrency_total_ttc;
    public $fk_user_creat;
    public $fk_user_modif;
    public $fk_user_valid;
    public $fk_user_cloture;
    public $import_key;
    public $model_pdf;
    public $modelpdf; /** @see $model_pdf  */
    public $status;
    public $last_main_doc;
    public $entity;
    public $overshot;
    public $time_planned_t;
    public $time_planned_f;
    public $planned_date;

    /**
     * @var int    Name of subtable line
     */
    public $table_element_line = 'operationorderdet';

    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    public $fk_element = 'fk_operation_order';

    /**
     * @var int    Name of subtable class that manage subtable lines
     */
    public $class_element_line = 'OperationOrderDet';

    /**
     * @var array	List of child tables. To test if we can delete object.
     */
    protected $childtables=array('operationorderdet'=>'OperationOrderDet');

    /**
     * @var OperationOrderDet[]   $lines  Array of subtable lines
     */
    public $lines = array();
    /**
     * @var OperationOrderDet[]   $TOperationOrderDet  Array of subtable lines
     */
    public $TOperationOrderDet = array();

    const OR_ALL_STOCK_NOT_ENOUGH = -2;
    const OR_ONLY_PHYSICAL_STOCK_NOT_ENOUGH = -1;
    const OR_STOCK_IS_ENOUGH = 1;

    /**
     * OperationOrder constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
		global $conf;

        parent::__construct($db);

		$this->init();

		$this->status = 0;
		$this->entity = $conf->entity;

		$this->lines = &$this->TOperationOrderDet;
		$this->modelpdf = &$this->model_pdf;
		$this->socid = &$this->fk_soc; // Compatibility with select ajax on formadd product
		$this->statut = &$this->status; // Compatibility with select ajax on formadd product
    }

    /**
     * @param User $user User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return int
     */
    public function save($user, $notrigger = false)
    {
        $this->time_planned_t = $this->getTimePlannedT();

        return $this->create($user, $notrigger);
    }

    /**
     * Function to create object in database
     *
     * @param   User    $user   user object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return  int                < 0 if ko, > 0 if ok
     */
    public function create(User &$user, $notrigger = false)
    {
		global $conf;

		if (!empty($this->is_clone))
		{
			// TODO determinate if auto generate
			// $this->ref = '(PROV'.$this->id.')';
			$this->ref = $this->getNextNumRef();
			// $this->fk_user_valid = $user->id;
		}



		if(!empty($this->is_clone) && !empty($conf->global->OPODER_STATUS_ON_CLONE))
		{
			// Set status by default conf
			$this->status = $conf->global->OPODER_STATUS_ON_CLONE;
		}
		else
		{
			if(empty($this->entity)){
				$this->entity = $conf->entity;
			}

			$status = new Operationorderstatus($this->db);
			$res = $status->fetchDefault($this->status, $this->entity);
			if($res>0){
				$this->status = $status->id;
			}
			else{
				return -1;
			}
		}
		$needCreate = empty($this->id);
        $id = parent::create($user, $notrigger);
		if($needCreate && $id > 0) {
            $oOHistory = new OperationOrderHistory($this->db);
            $oOHistory->saveCreationOrDeletion($this);
		}
        return $id;
    }

	/**
	 * @param 	User 	$user 		object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
	 * @return  int
	 */
	public function cloneObject($user, $notrigger = false)
	{
		global $conf;

		$this->clear();
		$this->is_clone = 1;

		$result = $this->create($user, $notrigger);

		if ($result > 0) {
			if(!empty($this->is_clone) && !empty($conf->global->OPODER_STATUS_ON_CLONE))
			{
				// Set status by default conf
				$this->setStatus($user,$conf->global->OPODER_STATUS_ON_CLONE);
			}




			if (!empty($this->lines))
			{
				foreach ($this->lines as $i =>$line)
				{
					if(empty($line->fk_parent_line)){

						$lineNeedUpdate = false;

						// search new price
						if(!empty($line->fk_product))
						{
							$product = new Product($this->db);
							$res = $product->fetch( $line->fk_product);
							if($res){
								$lineNeedUpdate = true;
							}
						}

						// Update line if needed
						if($lineNeedUpdate){
							$this->updateline(
								$line->id,
								$line->description,
								$line->qty,
                                $product->price,
								$line->fk_warehouse,
								$line->pc,
								$line->time_planned,
								$line->time_spent,
								$line->fk_product,
								$line->info_bits,
								$line->date_start,
								$line->date_end,
								$line->type,
								$line->fk_parent_line,
								$line->label,
								$line->special_code,
								$line->array_options
							);
						}
						// Add others products for lines
						$this->recurciveAddChildLines($line->id, $line->fk_product, $line->qty);
					}
				}
			}
		}

		return $result;
	}

	public function getSocName() {
        $sql = "SELECT nom FROM ".MAIN_DB_PREFIX."societe WHERE rowid = ".$this->fk_soc;
        $resql = $this->db->query($sql);
        if(!empty($resql)) {
            $obj = $this->db->fetch_object($resql);
            return $obj->nom;
        }
        return '';
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
        $this->time_planned_t = $this->getTimePlannedT();

        $res = $this->updateOperationOrderActions();
        if($res < 0) return -1;

        if(!empty($this->oldcopy)) {
            $oOHistory = new OperationOrderHistory($this->db);
            $oOHistory->compareAndSaveDiff($this->oldcopy, $this);
        }

        return parent::update($user, $notrigger); // TODO: Change the autogenerated stub
    }

    public function setValues(&$Tab)
    {
        $TFields = array('time_planned_t', 'time_planned_f');

        foreach($Tab as $key => $value){

            if(in_array($key, $TFields)){

                if(strstr($value, ':'))
                {
                    $THourMin = explode(':', $value);
                    $Tab[$key] = convertTime2Seconds($THourMin[0], $THourMin[1]);
                }
            }
        }

        if(strstr($this->time_planned_f, ':'))
        {
            $THourMin = explode(':', $this->time_planned_f);
            $this->time_planned_f = convertTime2Seconds($THourMin[0], $THourMin[1]);
        }

        return parent::setValues($Tab); // TODO: Change the autogenerated stub
    }


    /**
     *	Get object and children from database
     *
     *	@param      int			$id       		Id of object to load
     * 	@param		bool		$loadChild		used to load children from database
     *  @param      string      $ref            Ref
     *	@return     int         				>0 if OK, <0 if KO, 0 if not found
     */
    public function fetch($id, $loadChild = true, $ref = null)
    {
        $res = parent::fetch($id, $loadChild, $ref);

        usort($this->TOperationOrderDet, function ($a, $b) {
            return $a->rang - $b->rang;
        });

        $this->fetch_thirdparty();
        if(empty($this->objStatus)) $this->loadStatusObj();
        $this->oldcopy = clone $this;
        return $res;
    }

	public function fetchLines(){
		$TNested = $this->fetch_all_children_nested();
		$this->lines = array();
		$this->fetchNestedLines($TNested);
	}

	public function fetchNestedLines($TNested, $level = 0){
		if(!empty($TNested) && is_array($TNested)) {
			foreach ($TNested as $k => $v) {
				$v['object']->level = $level;
				$this->lines[] = $v['object'];
				$this->fetchNestedLines($v['children'], $level +1 );
			}
		}
	}


//    public function lineLevel($id, $level = 0){
//    	// init pour gagner en temps de traitement
//    	if(empty($this->cacheLineIdNumb)){
//			$this->cacheLineParent = array();
//
//			foreach ($this->TOperationOrderDet as $i => $det){
//				$this->cacheLineParent[$det->id] = $det->fk_parent_line;
//			}
//		}
//	}


	/**
	 * Load object in memory from database
	 *
	 * @param int $fk_parent_line object
	 * @param int $fk_parent_level id of parent
	 * @return array array of object
	 */
	public function fetch_all_children_nested($fk_parent_line = 0) {

		$TNested = array();

		$sql = "SELECT";
		$sql .= " line.rowid,";
		$sql .= " line.rang,";
		$sql .= " line.fk_parent_line";
		$sql .= " FROM " . MAIN_DB_PREFIX . "operationorderdet as line";
		$sql .= " WHERE line.fk_operation_order=" . intval($this->id);
		if(empty($fk_parent_line)){
			$sql .= " AND ( line.fk_parent_line = 0 OR line.fk_parent_line IS NULL ) ";
		}
		else{
			$sql .= " AND line.fk_parent_line=" . intval($fk_parent_line);
		}

		$sql .= " ORDER BY line.rang ASC";

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new OperationOrderDet($this->db);
				$line->fetch($obj->rowid);

				$TNested[$i] = array(
					'object' => $line,
					'children' => $this->fetch_all_children_nested($obj->rowid)
				);
				$i ++;
			}
			$this->db->free($resql);

			return $TNested;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

    /**
     * @see cloneObject
     * @return void
     */
    public function clearUniqueFields()
    {

    }

    public function deleteline($lineid) {
        global $user;

        $this->db->begin();
        $line = new OperationOrderDet($this->db);

        // For triggers
        $line->fetch($lineid);

        if($line->delete($user) > 0) {
            $this->db->commit();
            return 1;
        }
        else {
            $this->db->rollback();
            return -1;
        }
    }


    /**
     * @param User $user User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
     * @return int
     */
    public function delete(User &$user, $notrigger = false)
    {
        $this->deleteObjectLinked();

        $res = $this->deleteORAction();

        $oOHistory = new OperationOrderHistory($this->db);
        $oOHistory->saveCreationOrDeletion($this, 'delete');

        if($res < 0) return -1;

        unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
        return parent::delete($user, $notrigger);
    }

    /**
     * @return string
     */
    public function getRef()
    {
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))
		{
//			return $this->getNextRef();
			return $this->getNextNumRef();
		}

		return $this->ref;
    }

    public static function getStaticRef($fk_operationorder) {
        global $db;

        $sql = "SELECT ref FROM ".MAIN_DB_PREFIX."operationorder WHERE rowid = ".$fk_operationorder;
        $resql = $db->query($sql);
        if(!empty($resql)) {
            $obj = $db->fetch_object($resql);
            return $obj->ref;
        }
        return '';
    }

    /**
     * @return string
     */
    private function getNextRef()
    {
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$mask = !empty($conf->global->OPERATIONORDER_REF_MASK) ? $conf->global->OPERATIONORDER_REF_MASK : 'OR{yy}{mm}-{0000}';
		$ref = get_next_value($db, $mask, 'operationorder', 'ref');

		return $ref;
    }

    /**
     *  Returns the reference to the following non used object depending on the active numbering module.
     *
     *  @return string      		Object free reference
     */
    public function getNextNumRef()
    {
        global $langs, $conf;
        $langs->load("operationorder@operationorder");

        if (empty($conf->global->OPERATIONORDER_ADDON)) {
            $conf->global->OPERATIONORDER_ADDON = 'mod_operationorder_standard';
        }

        if (!empty($conf->global->OPERATIONORDER_ADDON))
        {
            $mybool = false;

            $file = $conf->global->OPERATIONORDER_ADDON.".php";
            $classname = $conf->global->OPERATIONORDER_ADDON;

            // Include file with class
            $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
            foreach ($dirmodels as $reldir)
            {
                $dir = dol_buildpath($reldir."core/modules/operationorder/");

                // Load file with numbering class (if found)
                $mybool |= @include_once $dir.$file;
            }

            if ($mybool === false)
            {
                dol_print_error('', "Failed to include file ".$file);
                return '';
            }

            $obj = new $classname();
            $numref = $obj->getNextValue($this);

            if ($numref != "")
            {
                return $numref;
            }
            else
            {
                $this->error = $obj->error;
                //dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
                return "";
            }
        }
        else
        {
            print $langs->trans("Error")." ".$langs->trans("Error_OPERATIONORDER_ADDON_NotDefined");
            return "";
        }
    }

	/**
	 * @param $user User
	 * @return bool
	 */
	public function isEditable($user){
		return $this->userCan($user, 'edit');
	}

	/**
	 * @param $user User
	 * @param string $action
	 * @return bool
	 */
	public function userCan($user, $action = ''){

		if($this->loadStatusObj()){
			return $this->objStatus->userCan($user, $action);
		}

		return false;
	}



	/**
	 * @param $user User
	 * @param bool $forceReload false = use cache ; true = force reload status
	 * @return bool
	 */
	public function loadStatusObj($forceReload = false){

		if(empty($this->objStatus) || is_object($this->objStatus) || $forceReload){
			$this->objStatus = new Operationorderstatus($this->db);
			$res = $this->objStatus->fetchDefault($this->status, $this->entity);
			if($res>0){
				return true;
			}
		}
		elseif($this->status != $this->objStatus->id){
			return $this->loadStatusObj(true);
		}

		return true;
	}


	/**
	 *    Set to a status
	 *
	 * @param User $user Object user that modify
	 * @param int $fk_status New status to set (often a constant like self::STATUS_XXX)
	 * @param int $notrigger 1=Does not execute triggers, 0=Execute triggers
	 * @param string $triggercode Trigger code to use
	 * @return    int                        <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function setStatus($user, $fk_status, $notrigger = 0, $triggercode = 'OPERATIONORDER_STATUS_CHANGE')
	{
		global $conf, $langs;

		$error = 0;

		$this->loadStatusObj();

		$newStatus = new OperationOrderStatus($this->db);
		$resNewStatus = $newStatus->fetch($fk_status);
		if($resNewStatus>0)
		{
			if($this->objStatus->checkStatusTransition($user, $fk_status))
			{
				$this->status = intval($fk_status);
				$this->withChild = false;

				$this->db->begin();
				$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
				$sql .= " SET status = ".$this->status;

				$newref = $this->getRef();
				if($this->ref != $newref)
				{
					$this->ref = $newref;
					$sql .= " , ref = '".$this->db->escape($this->ref)."' ";
				}

				if(!empty($newStatus->clean_event)){
					$this->planned_date = '';
					$sql .= " , planned_date = NULL ";
				}

				$sql .= " WHERE rowid = ".$this->id;

				if ($this->db->query($sql))
				{
					if(!empty($newStatus->clean_event)){
						if($this->deleteORAction() < 0){
							$this->error = 'Error cleaning operation order events';
							$error++;
						}
					}

					if (!$error)
					{
						$this->oldcopy = clone $this;
						$this->objStatus = $newStatus;
					}

					if (!$error && !$notrigger) {
						// Call trigger
						$result = $this->call_trigger($triggercode, $user);
						if ($result < 0) $error++;
					}

					if (!$error) {
						$this->db->commit();
						$ret = 1;
					} else {
						$this->db->rollback();
						$ret = -1;
					}
				}
				else
				{
					$this->error = $this->db->error();
					$this->db->rollback();
					$ret = -1;
				}

				if($ret  > 0 )
				{

					// Agenda Hack to replace standard agenda trigger event
//					$actionTriggerKey = 'MAIN_AGENDA_ACTIONAUTO_OPERATIONORDER_STATUS';
//					if(!empty($conf->agenda->enabled) && !empty($conf->global->{$actionTriggerKey})){
//
//						$newStatus = new OperationOrderStatus($this->db);
//						if($newStatus->fetch($fk_status) > 0)
//						{
//							$langs->load('operationorder@operationorder');
//							$eventLabel = $langs->transnoentities('OperationOrderSetStatus', '"'.$this->objStatus->label . '" => "' . $newStatus->label.'"' , $this->ref );
//							$this->addActionComEvent($eventLabel);
//						}
//					}

					if(!empty($this->oldcopy)) {
						$oOHistory = new OperationOrderHistory($this->db);
						$oOHistory->compareAndSaveDiff($this->oldcopy, $this);
					}

					return 1;
				}
			}
			else{
				$this->error = $langs->trans('Status');
			}
		}

        return 0;
    }


    /**
     * @param int    $withpicto     Add picto into link
     * @param string $moreparams    Add more parameters in the URL
     * @param  int  $notooltip		1=Disable tooltip
     * @return string
     */
    public function getNomUrl($withpicto = 0, $moreparams = '', $notooltip = 0)
    {
		global $langs, $conf;

	    if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

	    $label='';
	    $linkclose = '>';
	    if (empty($notooltip)) {
		    $label = '<u>' . $langs->trans("ShowOperationOrder") . '</u>';
		    if (! empty($this->ref)) $label.= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		    $linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
	    }
	    $link = '<a href="'.dol_buildpath('/operationorder/operationorder_card.php', 1).'?id='.$this->id.urlencode($moreparams).'"'.$linkclose;
        $linkend='</a>';

	    if ($withpicto) $picto=img_picto($label, 'setup', ($notooltip ? '' : 'class="classfortooltip"'));

        $result = $link.$picto.$this->ref.$linkend;

        global $action, $hookmanager;
        $hookmanager->initHooks(array('operationorderdao'));
        $parameters = array('id'=>$this->id, 'getnomurl'=>$result);
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) $result = $hookmanager->resPrint;
        else $result .= $hookmanager->resPrint;

        return $result;
    }

    /**
     * @param int       $id             Identifiant
     * @param null      $ref            Ref
     * @param int       $withpicto      Add picto into link
     * @param string    $moreparams     Add more parameters in the URL
     * @return string
     */
    public static function getStaticNomUrl($id, $ref = null, $withpicto = 0, $moreparams = '')
    {
		global $db;

		$object = new OperationOrder($db);
		$object->fetch($id, false, $ref);

		return $object->getNomUrl($withpicto, $moreparams);
    }


    /**
     * @param int $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string
     */
    public function getLibStatut($mode = 0)
    {
        return self::LibStatut($this->status, $mode, $this->entity);
    }

    /**
     * @param int       $fk_status
     * @param int       $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string
     */
    public static function LibStatut($fk_status, $mode, $force_entity  = 0)
    {
		global $langs,$db;
		$langs->load('operationorder@operationorder');

		$status = new Operationorderstatus($db);
		$res = $status->fetchDefault($fk_status, $force_entity );
		if($res>0){
			return $status->getBadge();
		}

		return 'err';
    }

    /**
     *  Create a document onto disk according to template module.
     *
     *  @param	    string		$modele			Force template to use ('' to not force)
     *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
     *  @param      int			$hidedetails    Hide details of lines
     *  @param      int			$hidedesc       Hide description
     *  @param      int			$hideref        Hide ref
     *  @param      null|array  $moreparams     Array to provide more information
     *  @return     int         				0 if KO, 1 if OK
     */
    public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf, $langs;

        $langs->load("operationorder@operationorder");

        if (!dol_strlen($modele)) {
            $modele = 'standard';

            if ($this->modelpdf) {
                $modele = $this->modelpdf;
            } elseif (!empty($conf->global->OPERATIONORDER_ADDON_PDF)) {
                $modele = $conf->global->OPERATIONORDER_ADDON_PDF;
            }
        }

        $modelpath = "core/modules/operationorder/doc/";

        return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
    }

	/**
	 * @param $desc
	 * @param $qty
	 * @param $price
	 * @param $fk_warehouse
	 * @param $pc
	 * @param $time_planned
	 * @param $time_spent
	 * @param int $fk_product
	 * @param int $info_bits
	 * @param string $date_start
	 * @param string $date_end
	 * @param int $type
	 * @param int $rang
	 * @param int $special_code
	 * @param int $fk_parent_line
	 * @param string $label
	 * @param int $array_options
	 * @param string $origin
	 * @param int $origin_id
	 * @return int
	 * @throws Exception
	 */
    public function addline($desc, $qty, $price, $fk_warehouse, $pc, $time_planned, $time_spent, $fk_product = 0, $info_bits = 0, $date_start = '', $date_end = '', $type = 0, $rang = -1, $special_code = 0, $fk_parent_line = 0, $label = '', $array_options = 0, $origin = '', $origin_id = 0)
    {
        global $user;

        $logtext = "::addline commandeid=$this->id, desc=$desc, fk_product=$fk_product";
        $logtext .= ", info_bits=$info_bits, date_start=$date_start";
        $logtext .= ", date_end=$date_end, type=$type special_code=$special_code, origin=$origin, origin_id=$origin_id";
        dol_syslog(get_class($this).$logtext, LOG_DEBUG);

        if ($this->isEditable($user))
        {
//            include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

            // Clean parameters
            if (empty($qty)) $qty = 0;
            if (empty($time_planned)) $time_planned = 0;
            if (empty($time_spent)) $time_spent = 0;
            if (empty($info_bits)) $info_bits = 0;
            if (empty($rang)) $rang = 0;
            if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line = 0;
            if ($type === '') $type = 0;

            $qty = price2num($qty);
            $time_planned = price2num($time_planned);
            $time_spent = price2num($time_spent);
            $label = trim($label);
            $desc = trim($desc);

            // Check parameters
            if ($type < 0) return -1;

            $this->db->begin();

            $product_type = $type;

            // Rang to use
            $ranktouse = $rang;
            if ($ranktouse == -1)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $ranktouse = $rangmax + 1;
            }

            // Insert line
            $k = $this->addChild('OperationOrderDet');
            $this->line = $this->TOperationOrderDet[$k];

            $this->line->context = $this->context;

            $this->line->fk_operation_order = $this->id;
            $this->line->fk_product = $fk_product;
            $this->line->description = $desc;
            $this->line->qty = $qty;
            $this->line->fk_warehouse = $fk_warehouse;
            $this->line->pc = $pc;
			$this->line->price = $price;

            $this->line->time_planned = $time_planned; // TODO
            $this->line->time_spent = $time_spent; // TODO

            $this->line->label=$label;

            $this->line->product_type=$product_type;
            $this->line->rang=$ranktouse;
            $this->line->info_bits=$info_bits;
            $this->line->origin=$origin;
            $this->line->origin_id=$origin_id;
            $this->line->fk_parent_line=$fk_parent_line;

            if (is_array($array_options) && count($array_options)>0) {
                $this->line->array_options=$array_options;
            }

            $result=$this->line->create($user);
            if ($result > 0)
            {
                $oOHistory = new OperationOrderHistory($this->db);
                $oOHistory->saveCreationOrDeletion($this->line);
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true, 'DESC');

                // Mise a jour informations denormalisees au niveau de la commande meme
//                $result=$this->update_price(1, 'auto', 0, $mysoc);	// This method is designed to add line from user input so total calculation must be done using 'auto' mode.
                if ($result > 0)
                {
                    $this->db->commit();
                    $this->setTimePlannedT();
                    return $this->line->id;
                }
                else
                {
                    $this->db->rollback();
                    return -1;
                }
            }
            else
            {
                $this->error = $this->line->error;
                dol_syslog(get_class($this)."::addline error=".$this->error, LOG_ERR);
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::addline status of order must be Draft to allow use of ->addline()", LOG_ERR);
            return -3;
        }
    }

	/**
	 * @param $rowid
	 * @param $desc
	 * @param $qty
	 * @param $price
	 * @param $fk_warehouse
	 * @param $pc
	 * @param $time_planned
	 * @param $time_spent
	 * @param $fk_product
	 * @param int $info_bits
	 * @param string $date_start
	 * @param string $date_end
	 * @param int $type
	 * @param int $fk_parent_line
	 * @param string $label
	 * @param int $special_code
	 * @param int $array_options
	 * @param int $notrigger
	 * @return int
	 * @throws Exception
	 */
    public function updateline($rowid, $desc, $qty, $price, $fk_warehouse, $pc, $time_planned, $time_spent, $fk_product, $info_bits = 0, $date_start = '', $date_end = '', $type = 0, $fk_parent_line = 0, $label = '', $special_code = 0, $array_options = 0, $notrigger = 0)
    {
        global $langs, $user;

        dol_syslog(get_class($this)."::updateline id=$rowid, desc=$desc, info_bits=$info_bits, date_start=$date_start, date_end=$date_end, type=$type, fk_parent_line=$fk_parent_line, special_code=$special_code");

        if ($this->isEditable($user))
        {
            // Clean parameters
            if (empty($qty)) $qty = 0;
            if (empty($time_planned)) $time_planned = 0;
            if (empty($time_spent)) $time_spent = 0;
            if (empty($info_bits)) $info_bits = 0;
            if (empty($special_code) || $special_code == 3) $special_code = 0;

            if ($date_start && $date_end && $date_start > $date_end) {
                $langs->load("errors");
                $this->error = $langs->trans('ErrorStartDateGreaterEnd');
                return -1;
            }

            $qty = price2num($qty);
            $time_planned = price2num($time_planned);
            $time_spent = price2num($time_spent);

            $this->db->begin();

            //Fetch current line from the database and then clone the object and set it in $oldline property
            $k = $this->addChild('OperationOrderDet', $rowid);
            $line = $this->TOperationOrderDet[$k];

            $staticline = clone $line;

            $line->oldline = $staticline;
            $this->line = $line;
            $this->line->context = $this->context;

            // Reorder if fk_parent_line change
            if (! empty($fk_parent_line) && ! empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $this->line->rang = $rangmax + 1;
            }

            $this->line->id=$rowid;
            $this->line->label=$label;
            $this->line->description=$desc;
            $this->line->qty=$qty;
            $this->line->fk_warehouse=$fk_warehouse;
            $this->line->pc=$pc;
            $this->line->price=$price;
			$this->line->fk_product = $fk_product;


            $this->line->time_planned = $time_planned;
            $this->line->time_spent = $time_spent;

            $this->line->info_bits      = $info_bits;

            $this->line->date_start     = $date_start;
            $this->line->date_end       = $date_end;

            $this->line->product_type   = $type;
            $this->line->fk_parent_line = $fk_parent_line;

            if (is_array($array_options) && count($array_options) > 0) {
                // We replace values in this->line->array_options only for entries defined into $array_options
                foreach($array_options as $key => $value) {
                    $this->line->array_options[$key] = $array_options[$key];
                }
            }

            $result = $this->line->update($user, $notrigger);
            if ($result > 0)
            {
                // Reorder if child line
                if (!empty($fk_parent_line)) $this->line_order(true, 'DESC');

                $this->db->commit();
				$this->setTimePlannedT();

                if(!empty($this->line->oldcopy)) {
                    $oOHistory = new OperationOrderHistory($this->db);
                    $oOHistory->compareAndSaveDiff($this->line->oldcopy, $this->line);
                }

                return $result;
            }
            else
            {
				$this->error = $this->line->error;
				$this->errors = $this->line->errors;

                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->error = get_class($this)."::updateline Order status makes operation forbidden";
            $this->errors = array('OrderStatusMakeOperationForbidden');
            return -2;
        }
    }

    /**
     * Initialise object with example values
     * Id must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        $this->thirdparty = new Societe($this->db);
        $this->initAsSpecimenCommon();
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * 	Update position of line with ajax (rang)
     *
     * 	@param	array	$rows	Array of rows
     * 	@return	void
     */
    public function line_ajaxorder($rows)
    {
        $TId = array();
        foreach ($this->TOperationOrderDet as $operationOrderDet)
        {
            if (empty($operationOrderDet->fk_parent_line)) $TId[$operationOrderDet->id] = array();
            else $TId[$operationOrderDet->fk_parent_line][] = $operationOrderDet->id;
        }

        // phpcs:enable
        $i = 1;
        foreach ($rows as $id)
        {
            // Si id parent
            if (isset($TId[$id]))
            {
                $this->updateRangOfLine($id, $i++);
                foreach ($TId[$id] as $fk_child_line)
                {
                    $this->updateRangOfLine($fk_child_line, $i++);
                }
            }
        }
    }

	/**
	 * @return bool
	 */
	protected function clear()
	{
		// backup origins lines
		$this->originLines = $this->lines;
		$this->status = 0;

		if (!empty($this->lines) && !empty($this->fk_element))
		{
			foreach ($this->lines as $i =>& $line)
			{
				if(!empty($line->fk_parent_line)){
					unset($this->lines[$i]);
				}
				else{
					$line->{$this->fk_element} = 0;
					$line->clear();
				}
			}

			sort($this->lines);
		}

		return parent::clear();;
	}

	public function recurciveAddChildLines($fk_line_parent, $fk_product, $qty){
		global $conf, $langs, $hookmanager;

		if (!empty($conf->global->PRODUIT_SOUSPRODUITS) && !empty($fk_line_parent) && !empty($fk_product))
		{
			$product = new Product($this->db);
			$res = $product->fetch($fk_product);
			if($res){
				$arbo = $product->getChildsArbo($product->id, 1);
				if (!empty($arbo))
				{
					foreach ($arbo as $productid => $product_info)
					{
						$childLineProduct = new Product($this->db);
						$res = $childLineProduct->fetch( $productid);
						if($res){

							$nb = doubleval(!empty($product_info[1]) ? $product_info[1] : 0);

							$newLineQty = $nb*$qty;

							// Convertion des temps planifier
							$time_plannedhour = 0;
							$time_plannedmin = 0;
							$timePlanned = 0;

							if(!empty($childLineProduct->duration_unit) && !empty($childLineProduct->duration_value))
							{
								$fk_duration_unit = UnitsTools::getUnitFromCode($childLineProduct->duration_unit, 'short_label');
								if($fk_duration_unit<1) {
									$this->errors[] =  $langs->transnoentities('UnitCodeNotFound', $childLineProduct->duration_unit);
								}

								if(!empty($childLineProduct->duration_value) && $fk_duration_unit > 0){
									$fk_unit_hours = UnitsTools::getUnitFromCode('H', 'code');
									if($fk_unit_hours>0) {
										$durationHours = UnitsTools::unitConverteur($childLineProduct->duration_value, $fk_duration_unit, $fk_unit_hours);

										$time_plannedhour = floor($durationHours);
										$time_plannedmin = floor(($durationHours-floor($durationHours)) * 60);
									}
									else{
										$this->errors[] = $langs->transnoentities('UnitCodeNotFound', 'H');
									}
								}

								// set time planned after time conversion according to qty
								$timePlanned = ($time_plannedhour * 60 * 60 + $time_plannedmin * 60) * $newLineQty;
							}

							// Pas le choix de passer par un hook et pas par un trigger
							$parameters=array(
								'parent_product' =>& $product,
								'product_info' => $product_info,
								'childLineProduct' =>& $childLineProduct,
								'fk_line_parent' => $fk_line_parent,
								'fk_product' => $fk_product,
								'qty' => $qty,
								'newLineQty' => $newLineQty,
								'nb' => $nb,
								'timePlanned' => $timePlanned,
							);
							$reshook=$hookmanager->executeHooks('recurciveAddChildLines',$parameters,$this);    // Note that $action and $object may have been modified by hook
							if ($reshook < 0){
								return $reshook;
							}elseif ($reshook>0){
								continue;
							}
							else{
								// Ajout de la ligne
								$newLineRes = $this->addline(
									'',
									$newLineQty,
									$childLineProduct->price,
									$childLineProduct->fk_default_warehouse,
									0,
									$timePlanned,
									0,
									$childLineProduct->id,
									0,
									'',
									'',
									$childLineProduct->type,
									-1,
									0,
									$fk_line_parent,
									'',
									array(),
									'',
									0
								);


								if($newLineRes>0){
									$recusiveRes = $this->recurciveAddChildLines($newLineRes, $childLineProduct->id, $newLineQty);
									if($recusiveRes<0){
										$this->errors[] = $langs->transnoentities('RecurciveLineaddFail');
										return -2;
									}
								}
								else
								{
									$this->errors[] = $langs->transnoentities('LineaddFail');
									return -1;
								}
							}
						}
					}
					return 1;
				}
			}
		}

		return 0;
	}

	/**
	 * @param $label
	 * @param string $note
	 * @return int
	 * @throws Exception
	 */
	function addActionComEvent($label, $note = ''){
		global $user;

		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

		$object = new ActionComm($this->db);
		$object->code = 'AC_OTH_AUTO';
		$object->type_code = $object->code; // if missing there is an error
		$object->label = $label;
		$object->note_private = $note;

		$object->datep = time();

		$object->fk_element = $this->id;    // Id of record
		$object->elementid = 0;    // Id of record alternative for API
		$object->elementtype = 'operationorder';   // Type of record. This if property ->element of object linked to.

		$object->socid = $this->fk_soc;
		$object->userownerid = $user->id;
		$object->percentage = -1;


		$newEventId = $object->create($user);
		if($newEventId < 1)
		{
			dol_syslog(__CLASS__ . ":".__METHOD__." launched by " . __FILE__ . ". id=" . $this->id.' error code : '.$object->error, LOG_ERR);
			return -1;
		}

	}

    /**
     * Return HTML string to show a field into a page
     * Code very similar with showOutputField of extra fields
     *
     * @param  array   $val		       Array of properties of field to show
     * @param  string  $key            Key of attribute
     * @param  string  $value          Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
     * @param  string  $moreparam      To add more parametes on html input tag
     * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
     * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
     * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
     * @return string
     */
    public function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
    {
        global $conf, $langs, $db;
        $out = '';
        if ($key == 'fk_contrat')
        {
            if(!empty($value)){
                include_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                $contract = new Contrat($db);
                if($contract->fetch($value)>0){
                    $out = $contract->getNomUrl(1);
                }
            }
        }
        else{
            if($key == 'time_planned_t' || $key == 'time_planned_f'){
                $val['type'] = 'duration';
            }
            $out.= parent::showOutputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);
        }

        return $out;
    }

    public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
    {

        if ($key == 'time_planned_f')
        {
            $out = '<input  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.convertSecondToTime($value).'" >';
        }
        else
        {
            $out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
        }

        return $out;
    }

    /**
     * Return HTML string to show a field into a page
     *
     * @param  string  $key            Key of attribute
     * @param  string  $moreparam      To add more parameters on html input tag
     * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
     * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
     * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
     * @return string
     */
    public function showOutputFieldQuick($key, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = ''){
        return $this->showOutputField($this->fields[$key], $key, $this->{$key}, $moreparam, $keysuffix, $keyprefix, $morecss);
    }

    public function getOvershoot($useCache = true){

        if($useCache && is_object($this->overshot)){
            return $this->overshot;
        }

        $sql = ' SELECT SUM(l.time_planned) sum_time_planned,  SUM(l.time_spent) sum_time_spent';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'operationorderdet l ';
        $sql.= ' WHERE l.fk_operation_order = '.$this->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->overshot = $this->db->fetch_object($resql);
        }else{
            $this->overshot = false;
        }

        return $this->overshot;
    }

    public function getOvershootStatus($useCache = true){
        global $langs;

        $out='';

        if($this->getOvershoot($useCache)){
            if (!empty($this->overshot->sum_time_planned) && !empty($this->overshot->sum_time_spent)){
                $ecart = intval($this->overshot->sum_time_planned) - intval($this->overshot->sum_time_spent);
                $sign = '';
                if($ecart>0){
                    $textClass = "text-success";
                    $iconClass = "fa-caret-down";
                    $sign = '-';
                }elseif($ecart==0){
                    $textClass = "text-warning";
                    $iconClass = "fa-caret-left";
                }else{
                    $textClass = "text-danger";
                    $iconClass = "fa-caret-up";
                    $sign = '+';
                }

                $out.= '<span class="'.$textClass.' classfortooltip paddingrightonly" title="'.$langs->trans('TimeDifference').'" ><i class="fa '.$iconClass.'"></i> '.$sign. dol_print_date(abs($ecart), '%HH%M', true).'</span>';

            }else{
                $out .= ' -- ';
            }
        }
        else{
            $out='error';
        }


        return $out;
    }

	/**
	 * @return self[]
	 */
    public static function getPlannableOperationOrder() {
        global $db;
        $TPlanableOO = array();
        $sql = "SELECT oo.rowid, oo.ref
                FROM ".MAIN_DB_PREFIX."operationorder as oo
                INNER JOIN ".MAIN_DB_PREFIX."operationorder_status as oos ON (oo.status = oos.rowid)
                WHERE oos.planable = 1 AND oo.entity IN (".getEntity('operationorder').")";
        $resql = $db->query($sql);
        if(!empty($resql) && $resql > 0) {
            while($obj = $db->fetch_object($resql)) {

            	$operationOrder = new self($db);
				$operationOrder->fetch($obj->rowid);
                $TPlanableOO[$obj->rowid] = $operationOrder;
            }
        }

        return $TPlanableOO;
    }

    public function setTimePlannedT(){
        global $user;

        $total_time = 0;

        $this->fetchLines();

        foreach ($this->lines as $line)
        {
            $total_time = +$line->time_planned;
        }

        $this->time_planned_t = $total_time;

        $res = $this->update($user);

        return $res;
    }

    public function getTimePlannedT(){

        $total_time = 0;

        $this->fetchLines();

        if(!empty($this->lines))
        {
            foreach ($this->lines as $line)
            {
                $total_time += $line->time_planned;
            }
        }

        return $total_time;
    }

    public function getTimeSpent(){

        $total_time = 0;

        $this->fetchLines();

        if(!empty($this->lines))
        {
            foreach ($this->lines as $line)
            {
                $total_time += $line->time_spent;
            }
        }

        return $total_time;
    }

    public function deleteORAction(){

        $resql = $this->db->query("DELETE FROM ".MAIN_DB_PREFIX."operationorderaction WHERE fk_operationorder = '".$this->id."'");

        if($resql) return 1;
        else return -1;
    }

    public function updateOperationOrderActions() {

        global $user;

        $operationorderaction = new OperationOrderAction($this->db);
        $TORActions = $operationorderaction->fetchByOR($this->id);

        if($TORActions) {

            $operationorderaction = $TORActions[0];

            //update operationorderaction
            $operationorderaction->dated = $this->planned_date;
            if(!empty($this->time_planned_f)) $operationorderaction->datef = calculateEndTimeEventByBusinessHours($operationorderaction->dated, $this->time_planned_f);
            else $operationorderaction->datef = calculateEndTimeEventByBusinessHours($operationorderaction->dated, $this->time_planned_t);

            $res = $operationorderaction->save($user);

            if($res < 0) return -1;
            else return $res;

        } else {
            return 0;
        }

    }

    public function isStockAvailable() {
        if($this->planned_date < strtotime('today midnight')) return 1; // Pas besoin de vérifier pour les ORs passés
        $return = $this::OR_STOCK_IS_ENOUGH;
        foreach($this->lines as $line) {
            if(empty($line->product) && !empty($line->fk_product)) $line->fetch_product();
            if($line->product->type == Product::TYPE_PRODUCT) {
                if(empty($line->product->stock_reel)) $line->product->load_stock();
                if($line->product->stock_reel < $line->qty) { //Si on a pas assez de stock physique il faut vérifier le stock virtuel en tenant compte des dates de livraisons des CFs
                    if($line->isVirtualStockAvailableForDate($this->planned_date)) {
                        $return = $this::OR_ONLY_PHYSICAL_STOCK_NOT_ENOUGH; //virtual stock available but not physical
                    }
                    else { // On break dans ce cas là car ça signifie qu'au moins une ligne n'a pas assez de stocks
                        $return = $this::OR_ALL_STOCK_NOT_ENOUGH;
                        break;
                    }//not enough virtual stock
                }
            }
        }
        return $return;
    }
}


class OperationOrderDet extends SeedObject
{
    public $table_element = 'operationorderdet';

    public $element = 'operationorderdet';

    /** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    public $fields=array(
		'fk_operation_order' => array (
			'type' => 'integer',
			'label' => 'OperationOrder',
			'enabled' => 1,
			'position' => 5,
			'notnull' => 1,
			'visible' => 0,
		),
		'fk_product' => array (
			'type' => 'integer:Product:product/class/product.class.php:1',
			'required' => 1,
			'label' => 'Product',
			'enabled' => 1,
			'position' => 35,
			'notnull' => -1,
			'visible' => -1,
			'index' => 1,
		),
		'fk_parent_line' => array (
			'type' => 'integer',
			'enabled' => 1,
			'visible' => 0,
		),
		'price' => array (
			'type' => 'real',
			'label' => 'UnitPrice',
			'enabled' => 1,
			'position' => 40,
			'notnull' => 0,
			'required' => 1,
			'visible' => 1,
		),
		'description' => array (
			'type' => 'html',
			'label' => 'Description',
			'enabled' => 1,
			'position' => 40,
			'notnull' => 0,
			'visible' => 3,
		),
		'qty' => array (
			'type' => 'real',
			'required' => 1,
			'label' => 'Qty',
			'enabled' => 1,
			'position' => 45,
			'notnull' => 0,
			'visible' => 1,
			'isameasure' => '1',
			'css' => 'maxwidth75imp',
		),
		'fk_warehouse' => array (
			'type' => 'varchar(255)',
			'label' => 'StockPlace',
			'length' => 255,
			'enabled' => 1,
			'position' => 47,
			'visible' => 1,
		),
/*
// En fait c'est les pieces .... donc la qty
'pc' => array (
			'type' => 'varchar(255)',
			'label' => 'OperationOrderDetPc',
			'length' => 255,
			'enabled' => 1,
			'position' => 49,
			'visible' => 1,
		),
*/
		'time_planned' => array (
			'type' => 'integer',
			'label' => 'TimePlanned',
			'enabled' => 1,
			'position' => 70,
			'notnull' => 0,
			'visible' => 1,
		),
		'time_spent' => array (
			'type' => 'integer',
			'label' => 'TimeSpent',
			'enabled' => 1,
			'position' => 80,
			'notnull' => 0,
			'visible' => 1,
		),
		'product_type' => array (
			'type' => 'integer',
			'label' => 'ProductType',
			'enabled' => 1,
			'position' => 90,
			'notnull' => 1,
			'visible' => 0,
		),
		'rang' => array (
			'type' => 'integer',
			'label' => 'Rank',
			'enabled' => 1,
			'position' => 92,
			'notnull' => 0,
			'visible' => 0,
		),
		'fk_user_creat' => array (
			'type' => 'integer:User:user/class/user.class.php',
			'label' => 'UserAuthor',
			'enabled' => 1,
			'position' => 510,
			'notnull' => 1,
			'visible' => -2,
			'foreignkey' => 'user.rowid',
		),
		'fk_user_modif' => array (
			'type' => 'integer:User:user/class/user.class.php',
			'label' => 'UserModif',
			'enabled' => 1,
			'position' => 511,
			'notnull' => 0,
			'visible' => -2,
		),
		'import_key' => array (
			'type' => 'varchar(14)',
			'length' => 14,
			'label' => 'ImportId',
			'enabled' => 1,
			'position' => 1000,
			'notnull' => -1,
			'visible' => -2,
		),
		'info_bits' => array (
			'type' => 'int',
			'visible' => 0,
		)
    );

    public $fk_operation_order;
    public $fk_product;
    public $fk_parent_line;
    public $description;
    public $qty;
    public $fk_warehouse;
    public $pc;
    public $time_planned;
    public $time_spent;
    public $product_type;
    public $rang;
    public $fk_user_creat;
    public $fk_user_modif;
    public $import_key;
    public $price;
    public $total_ht;

	/**
	 * @var $product Product
	 */
    public $product;

    /**
     * OperationOrderDet constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->init();
    }

    /**
     *	Get object and children from database
     *
     *	@param      int			$id       		Id of object to load
     * 	@param		bool		$loadChild		used to load children from database
     *  @param      string      $ref            Ref
     *	@return     int         				>0 if OK, <0 if KO, 0 if not found
     */
    public function fetch($id, $loadChild = true, $ref = null)
    {
        $res = parent::fetch($id, $loadChild, $ref);

        $this->product = new Product($this->db);
        if ($this->fk_product > 0)
        {
            // Pour palier à l'absence de méthode getLinesArray
            $this->product->fetch($this->fk_product);
            $this->ref = $this->product->ref;
            $this->product_ref = $this->product->ref;
            $this->label = $this->product->label;
        }
        else{
			$this->product = false;
		}

        // désactivation de l'entrepot pour les services
        if($this->product_type != 0){
        	$this->fields['fk_warehouse']['visible'] = 0;
		}


		$this->calcPrices();
        $this->oldcopy = clone $this;
        return $res;
    }

    public function getProductRef() {
        $sql = "SELECT ref FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".$this->fk_product;
        $resql = $this->db->query($sql);
        if(!empty($resql)) {
            $obj = $this->db->fetch_object($resql);
            return $obj->ref;
        }
        return '';
    }

    function calcPrices(){

    	/* Sur spéc
    	 * Règle de calcul du Total HT
    	 * Si Quantité/Temps utilisé = 0(vide)
    	 * Total HT = Quantité commandée * P.U. H.T.
    	 * Sinon
    	 * Total HT = Quantité/Temps utilisé * P.U. H.T.
    	 */
		$hours = 0;
		if(!empty($this->time_spent)) {
			$hours = round($this->time_spent / 3600, 2);
		}

		if($hours>0){

			$this->total_ht = $hours * $this->price;
		}
		else{
			$this->total_ht = $this->qty * $this->price;
		}
	}



	function stockStatus($mode = '', $url = '', $params = array()){
    	global $langs;

    	$langs->loadLangs(array('operationorder@operationorder', 'stocks'));

		$out = '';
		if ($this->fk_product > 0 && empty($this->product_type) && $this->product) {

			$this->product->load_stock();
            if(!empty($params['planned_date'])) $this->isVirtualStockAvailableForDate($params['planned_date']);


            $tooltipLabel = $langs->trans('RealStock').' : '.$this->product->stock_reel.'</br>';
			$tooltipLabel.= $langs->trans('VirtualStock').' : '.$this->product->stock_theorique;

			if(empty($params['attr']['title'])){
				$params['attr']['title']=$tooltipLabel;
			}

			if($this->product->stock_reel >= $this->qty){
				$out .= dolGetBadge($langs->trans('StockAvailable').' '.$this->product->stock_reel, '','success classfortooltip', $mode, $url, $params);
			}
			elseif($this->product->stock_reel < $this->qty && $this->product->stock_theorique >= $this->qty){
				$out .= dolGetBadge($langs->trans('VirtualStockAvailable').' '.$this->product->stock_reel, '', 'warning classfortooltip', $mode, $url, $params);
			}
			else{
				$out .= dolGetBadge($langs->trans('NotEnoughStockAvailable').' '.$this->product->stock_reel,'', 'danger classfortooltip', $mode, $url, $params);
			}
		}

		return $out;
	}

	/**
	 * Return HTML string to show a field into a page
	 *
	 * @param  string  $key            Key of attribute
	 * @param  string  $moreparam      To add more parameters on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputFieldQuick($key, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = ''){
		return $this->showOutputField($this->fields[$key], $key, $this->{$key}, $moreparam, $keysuffix, $keyprefix, $morecss);
	}

	/**
	 * Return HTML string to show a field into a page
	 * Code very similar with showOutputField of extra fields
	 *
	 * @param  array   $val		       Array of properties of field to show
	 * @param  string  $key            Key of attribute
	 * @param  string  $value          Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $moreparam      To add more parametes on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $conf, $langs, $db;
		$out = '';
		if ($key == 'fk_warehouse')
		{
			$warehouse = new Entrepot($db);
			$res = $warehouse->fetch($value);
			if($res>0){
				$out.= $warehouse->getNomUrl(1);
			}
		}
		elseif ($key == 'time_planned' || $key == 'time_spent')
		{
			if (!empty($this->time_planned)){
				if(!function_exists('convertSecondToTime')){
					include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
				}

				$out.= convertSecondToTime(intval($this->time_planned), 'allhourmin') ;
			}else{
				$out .= ' -- ';
			}
		}
		else{
			$out.= parent::showOutputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);
		}

		return $out;
	}


	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param array $val Array of properties for field to show
	 * @param string $key Key of attribute
	 * @param string $value Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param string $moreparam To add more parameters on html input tag
	 * @param string $keysuffix Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param string $keyprefix Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param string|int $morecss Value for css to define style/length of field. May also be a numeric.
	 * @param int $nonewbutton
	 * @return string
	 * @throws Exception
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $langs, $db, $conf, $user;


		if(!empty($this->fields[$key]['required'])){ $moreparam.= " required"; }

		// for cache
		if(empty($this->form)){
			$this->form = new Form($db);
		}

		if(empty($this->formproduct)){
			include_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
			$this->formproduct = new FormProduct($db);
		}

		if($key == 'fk_product')
		{
			if($this->{$key} > 0){
				// désactivation de l'affichage en mode edition
				$out ='<input type="hidden" class="flat '.$morecss.'"  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'>';
				$out.= $this->showOutputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);
			}
			else{
				$out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);

				$out.= '<script type="text/javascript">
					$(function()
					{
					    if($("#'.$keyprefix . $key . $keysuffix.'").length>0){
							$("#' . $keyprefix . $key . $keysuffix . '").change(function(){
								$.ajax({
									url: "' . dol_buildpath('operationorder/scripts/interface.php?action=getProductInfos', 1) . '",
									method: "POST",
									data: {
										\'fk_product\' : $( this ).val(),
										\'element\' : \'operationorder\',
										\'element_id\' : '.intval($this->fk_operation_order).'
									},
									dataType: "json",

									// La fonction à apeller si la requête aboutie
									success: function (data) {
									$("#unitaire_timehour").remove();
									$("#unitaire_timemin").remove();
										// Loading data
										console.log(data);
										if(data.result > 0 ){
										   // ok case
										   $("#' . $keyprefix . 'fk_warehouse' . $keysuffix . '").val(data.fk_default_warehouse).change();
										   $("#' . $keyprefix . 'price' . $keysuffix . '").val(data.price);
										   $("[name=' . $keyprefix . 'time_plannedhour' . $keysuffix . ']").val(data.time_plannedhour);
										   $("[name=' . $keyprefix . 'time_plannedhour' . $keysuffix . ']").after("<input type=\'hidden\' id=\'unitaire_timehour\' value=\'"+data.time_plannedhour+"\' />");
										   $("[name=' . $keyprefix . 'time_plannedmin' . $keysuffix . ']").val(data.time_plannedmin);
										   $("[name=' . $keyprefix . 'time_plannedmin' . $keysuffix . ']").after("<input type=\'hidden\' id=\'unitaire_timemin\' value=\'"+data.time_plannedmin+"\' />");


										}
										else{
										   // nothing to do ?
										   $("#' . $keyprefix . 'fk_warehouse' . $keysuffix . '").val(-1).change();
										   $("#' . $keyprefix . 'price' . $keysuffix . '").val("");
										   $("[name=' . $keyprefix . 'time_plannedhour' . $keysuffix . ']").val("");
										   $("[name=' . $keyprefix . 'time_plannedmin' . $keysuffix . ']").val("");
										}

										if(data.errorMsg.length > 0){
											$.jnotify(data.errorMsg, "error", true);
										}

									},
									// La fonction à appeler si la requête n\'a pas abouti
									error: function( jqXHR, textStatus ) {
										alert( "Request failed: " + textStatus );
									}
								});
							});
						}
					});
					</script>
				';
			}
		}
		elseif($key == 'qty')
		{
			$out ='<input type="number" min="0" step="any" class="flat '.$morecss.'"  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'>';
		}
		elseif ($key == 'time_planned')
		{
			$out = $this->form->select_duration($keyprefix.$key.$keysuffix, $value, 0, 'text', 0, 1);
		}
		elseif ($key == 'time_spent')
		{
			$out = $this->form->select_duration($keyprefix.$key.$keysuffix, $value, 0, 'text', 0, 1);
		}
		elseif ($key == 'fk_warehouse')
		{
			if (!empty($conf->stock->enabled)) {
				$out = $this->formproduct->selectWarehouses($value, $keyprefix . $key . $keysuffix, 'warehouseopen', 1);
			}
			else{
				$out ='<input type="hidden"  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" >';
			}
		}
		else{
			$out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
		}

		return $out;
	}


	/**
	 * Function to delete object in database
	 *
	 * @param   User    $user   	user object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
	 * @return  int                 < 0 if ko, > 0 if ok
	 */
	public function delete(User &$user, $notrigger = false)
	{
		if ($this->id <= 0) return 0;

		$Tlines = $this->fetch_all_children_lines();
		if(is_array($Tlines)){
			foreach ($Tlines as $line){
				/**
				 * @var $line OperationOrderDet
				 */
				if(!empty($this->parent))$line->parent = $this->parent;
				$res = $line->delete($user, $notrigger);
				if($res < 0){
					return -2;
				}
			}
		}
        $oOHistory = new OperationOrderHistory($this->db);
        $oOHistory->saveCreationOrDeletion($this, 'delete');
		return parent::delete($user, $notrigger);
	}


	/**
	 * Load object in memory from database
	 *
	 * @param int $fk_parent_line object
	 * @param bool $nested 0 = return simple array of lines , 1 = return recusive table of object need recursive nested
	 * @param bool $flat 0 = return nested array , 1 = return flat array
	 * @param array $TNested
	 * @return array array of object
	 * @throws Exception
	 */
	public function fetch_all_children_lines($fk_parent_line = 0, $nested = false, $flat = false, &$TNested = array()) {

		$sql = "SELECT";
		$sql .= " line.rowid,";
		$sql .= " line.rang,";
		$sql .= " line.fk_parent_line";
		$sql .= " FROM " . MAIN_DB_PREFIX . "operationorderdet as line";
		$sql .= " WHERE line.fk_operation_order=" . intval($this->fk_operation_order);
		if(empty($fk_parent_line)){
			$sql .= " AND line.fk_parent_line=" . intval($this->id);
		}
		else{
			$sql .= " AND line.fk_parent_line=" . intval($fk_parent_line);
		}

		$sql .= " ORDER BY line.rang ASC";

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new OperationOrderDet($this->db);
				$line->fetch($obj->rowid);

				if($nested){
				    if(!$flat) {
                        $TNested[$i] = array(
                            'object' => $line,
                            'children' => $this->fetch_all_children_lines($obj->rowid, true)
                        );
                    } else {
				        $TNested[$obj->rowid] = $line;
                        $this->fetch_all_children_lines($obj->rowid, true, true, $TNested);
                    }

				}
				else{
					$TNested[$i] = $line;
				}
				$i ++;
			}
			$this->db->free($resql);

			return $TNested;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

    public function isVirtualStockAvailableForDate($date) {
        global $conf;
        $virtualStock = 0;
        if(! empty($date) && ! empty($this->product)) {

            $virtualStock = $this->product->stock_reel;
            $orderQty = 0;
            $sendingQty = 0;
            $supplierQty = 0;
            $receptionQty = 0;

            //Load qtys
            if(!empty($conf->fournisseur->enabled)) {
                $supplierQty = $this->loadSupplierOrderQty($date);
                $receptionQty = $this->loadSupplierOrderReceptionQty($date); //On retire ce qui a déjà été réceptionné car c'est contenu dans le stock reel
            }
            if(! empty($conf->commande->enabled)) $orderQty = $this->loadOrderQty($date);
            if(! empty($conf->expedition->enabled) && (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE))) {
                require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
                if(! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)) {
                    $filterShipmentStatus = Expedition::STATUS_VALIDATED.','.Expedition::STATUS_CLOSED;
                }
                else if(! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
                    $filterShipmentStatus = Expedition::STATUS_CLOSED;
                }
                $sendingQty = $this->loadSendingQty($date, $filterShipmentStatus);
            }
            $ooQty = $this->loadOperationOrderQty($date);
            if(!empty($ooQty)) $virtualStock -= $ooQty;

            // Stock decrease mode
            if(! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
                $virtualStock -= ($orderQty - $sendingQty);
            }
            else if(! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER)) {
                $virtualStock += 0;
            }
            else if(! empty($conf->global->STOCK_CALCULATE_ON_BILL)) {
                $virtualStock -= $orderQty;
            }

            // Stock Increase mode
            if(! empty($conf->global->STOCK_CALCULATE_ON_RECEPTION) || ! empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE)) {
                $virtualStock += ($supplierQty - $receptionQty);
            }
            else if(! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) {
                $virtualStock += ($supplierQty - $receptionQty);
            }
            else if(! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER)) {
                $virtualStock -= $receptionQty;
            }
            else if(! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL)) {
                $virtualStock += ($supplierQty - $receptionQty);
            }
            $this->product->stock_theorique = $virtualStock;
            if($virtualStock >= $this->qty) return true;
            else return false;
        }
    }

    public function loadOperationOrderQty($date = '') {
	    $qty = 0;
        $oOStatus = new OperationOrderStatus($this->db);
        $TStatus = $oOStatus->fetchAll(0, false, array("check_virtual_stock"=> 1));
        $TStatusId = array();
        if(!empty($TStatus)) {
            foreach($TStatus as $status) $TStatusId[] = $status->id;
            $sql = "SELECT SUM(ood.qty) as qty
                    FROM ".MAIN_DB_PREFIX."operationorderdet as ood
                    LEFT JOIN ".MAIN_DB_PREFIX."operationorder as oo ON (oo.rowid = ood.fk_operation_order)
                    WHERE ood.fk_product = ".$this->product->id."
                    AND oo.entity IN (".getEntity('operationorder').")
                    AND oo.status IN (".implode(',',$TStatusId).") ";
            if(!empty($date)) $sql .= "AND oo.planned_date < '".date('Y-m-d', $date)."'";
            $resql = $this->db->query($sql);
            if(! empty($resql) && $this->db->num_rows($resql) > 0) {
                $obj = $this->db->fetch_object($resql);
                if(! empty($obj->qty)) return $obj->qty;
            }
        }

        return $qty;
    }

    public function loadSendingQty($date, $filterShipmentStatus = array()) {
        $sql = "SELECT SUM(ed.qty) as qty";
        $sql .= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
        $sql .= ", ".MAIN_DB_PREFIX."commandedet as cd";
        $sql .= ", ".MAIN_DB_PREFIX."commande as c";
        $sql .= ", ".MAIN_DB_PREFIX."expedition as e";
        $sql .= ", ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE e.rowid = ed.fk_expedition";
        $sql .= " AND c.rowid = cd.fk_commande";
        $sql .= " AND e.fk_soc = s.rowid";
        $sql .= " AND e.entity IN (".getEntity('expedition').")";
        $sql .= " AND ed.fk_origin_line = cd.rowid";
        $sql .= " AND cd.fk_product = ".$this->product->id;
        $sql .= " AND c.fk_statut in (1,2)";
        if(! empty($filterShipmentStatus)) $sql .= " AND e.fk_statut IN (".$filterShipmentStatus.")";
        $sql .= " AND e.date_delivery < '".date('Y-m-d', $date)."'";

        $resql = $this->db->query($sql);
        if(! empty($resql) && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            if(! empty($obj->qty)) return $obj->qty;
        }
        return 0;
    }

    public function loadOrderQty($date) {
        global $conf;
        $tmpqty = 0;
        $sql = "SELECT SUM(cd.qty) as qty";
        $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
        $sql .= ", ".MAIN_DB_PREFIX."commande as c";
        $sql .= ", ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE c.rowid = cd.fk_commande";
        $sql .= " AND c.fk_soc = s.rowid";
        $sql .= " AND c.entity IN (".getEntity('commande').")";
        $sql .= " AND cd.fk_product = ".$this->product->id;
        $sql .= " AND c.fk_statut in (1,2)";
        $sql .= " AND c.date_livraison < '".date('Y-m-d', $date)."'";
        $resql = $this->db->query($sql);
        if(! empty($resql) && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            $tmpqty = $obj->qty;
        }
        else return 0;

        if(empty($tmpqty)) $tmpqty = 0;

        // If stock decrease is on invoice validation, the theorical stock continue to
        // count the orders to ship in theorical stock when some are already removed b invoice validation.
        // If option DECREASE_ONLY_UNINVOICEDPRODUCTS is on, we make a compensation.
        if(! empty($conf->global->STOCK_CALCULATE_ON_BILL)) {
            if(! empty($conf->global->DECREASE_ONLY_UNINVOICEDPRODUCTS)) {
                $adeduire = 0;
                $sql = "SELECT sum(fd.qty) as count FROM ".MAIN_DB_PREFIX."facturedet fd ";
                $sql .= " JOIN ".MAIN_DB_PREFIX."facture f ON fd.fk_facture = f.rowid ";
                $sql .= " JOIN ".MAIN_DB_PREFIX."element_element el ON el.fk_target = f.rowid and el.targettype = 'facture' and sourcetype = 'commande'";
                $sql .= " JOIN ".MAIN_DB_PREFIX."commande c ON el.fk_source = c.rowid ";
                $sql .= " WHERE c.fk_statut IN (1,2) AND c.facture = 0 AND fd.fk_product = ".$this->product->id;
                $sql .= " AND c.date_livraison < '".date('Y-m-d', $date)."'";

                $resql = $this->db->query($sql);
                if($resql) {
                    if($this->db->num_rows($resql) > 0) {
                        $obj = $this->db->fetch_object($resql);
                        $adeduire += $obj->count;
                    }
                }
                $tmpqty -= $adeduire;
            }
        }

        return $tmpqty;
    }

    public function loadSupplierOrderQty($date) {
        $sql = "SELECT SUM(cd.qty) as qty";
        $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
        $sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as c";
        $sql .= ", ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE c.rowid = cd.fk_commande";
        $sql .= " AND c.fk_soc = s.rowid";
        $sql .= " AND c.entity IN (".getEntity('supplier_order').")";
        $sql .= " AND cd.fk_product = ".$this->product->id;
        $sql .= " AND c.fk_statut in (1,2,3,4)";
        $sql .= " AND c.date_livraison < '".date('Y-m-d', $date)."'";
        $resql = $this->db->query($sql);
        if(! empty($resql) && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            if(! empty($obj->qty)) return $obj->qty;
        }
        return 0;
    }

    public function loadSupplierOrderReceptionQty($date) {
        $sql = "SELECT SUM(fd.qty) as qty";
        $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as fd";
        $sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
        $sql .= ", ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE cf.rowid = fd.fk_commande";
        $sql .= " AND cf.fk_soc = s.rowid";
        $sql .= " AND cf.entity IN (".getEntity('supplier_order').")";
        $sql .= " AND fd.fk_product = ".$this->product->id;
        $sql .= " AND cf.fk_statut in (4)";
        $sql .= " AND cf.date_livraison < '".date('Y-m-d', $date)."'";
        $resql = $this->db->query($sql);
        if(! empty($resql) && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            if(! empty($obj->qty)) return $obj->qty;
        }
        return 0;
    }
}


class OperationOrderDictType extends SeedObject
{
    public $table_element = 'c_operationorder_type';

    public $element = 'operationorder_type';

    public $fields = array(
        'code' => array('varchar(30)', 'length' => 30),
        'label' => array('varchar(255)', 'length' => 255, 'showoncombobox' => 1),
        'position' => array('integer'),
        'active' => array('integer'),
        'entity' => array('integer', 'index' => true)
    );

    /**
     * OperationOrderDet constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->init();
    }

    public function getNomUrl($getnomurlparam = '')
    {
        return $this->label;
    }
}
