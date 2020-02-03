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


class OperationOrder extends SeedObject
{
    /**
     * Canceled status
     */
    const STATUS_CANCELED = -1;
    /**
     * Draft status
     */
    const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Refused status
	 */
	const STATUS_REFUSED = 3;
	/**
	 * Accepted status
	 */
	const STATUS_ACCEPTED = 4;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_CANCELED => 'OperationOrderStatusShortCanceled'
		,self::STATUS_DRAFT => 'OperationOrderStatusShortDraft'
		,self::STATUS_VALIDATED => 'OperationOrderStatusShortValidated'
//		,self::STATUS_REFUSED => 'OperationOrderStatusShortRefused'
//		,self::STATUS_ACCEPTED => 'OperationOrderStatusShortAccepted'
	);

	/** @var string $table_element Table name in SQL */
	public $table_element = 'operationorder';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'operationorder';

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

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
        'ref_client' => array('type'=>'varchar(128)', 'label'=>'RefClient', 'enabled'=>1, 'position'=>20, 'notnull'=>0, 'visible'=>1,),
        'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1:status=1 AND entity IN (__SHARED_ENTITIES__)', 'label'=>'ThirdParty', 'enabled'=>1, 'position'=>50, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'help'=>"LinkToThirparty",),
        'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1', 'label'=>'Project', 'enabled'=>1, 'position'=>52, 'notnull'=>0, 'visible'=>-1, 'index'=>1,),
        'fk_contrat' => array('type'=>'integer:Contrat:contrat/class/contrat.class.php:1', 'label'=>'Contract', 'enabled'=>1, 'position'=>54, 'notnull'=>0, 'visible'=>-1, 'index'=>1,),
        'date_valid' => array('type'=>'datetime', 'label'=>'DateValid', 'enabled'=>1, 'position'=>56, 'notnull'=>0, 'visible'=>-2,),
        'date_cloture' => array('type'=>'datetime', 'label'=>'DateClose', 'enabled'=>1, 'position'=>57, 'notnull'=>0, 'visible'=>-2,),
        'date_operation_order' => array('type'=>'datetime', 'label'=>'DateOperationOrder', 'enabled'=>1, 'position'=>58, 'notnull'=>1, 'visible'=>-1,),
        'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>1, 'position'=>61, 'notnull'=>0, 'visible'=>0),
        'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>1, 'position'=>62, 'notnull'=>0, 'visible'=>0),

        'localtax1' => array('type'=>'real', 'label'=>'Localtax1', 'enabled'=>1, 'position'=>70, 'notnull'=>0, 'visible'=>0),
        'localtax2' => array('type'=>'real', 'label'=>'Localtax2', 'enabled'=>1, 'position'=>72, 'notnull'=>0, 'visible'=>0),
        'total_ht' => array('type'=>'real', 'label'=>'TotalHT', 'enabled'=>1, 'position'=>74, 'notnull'=>0, 'visible'=>1),
        'tva' => array('type'=>'real', 'label'=>'VAT', 'enabled'=>1, 'position'=>76, 'notnull'=>0, 'visible'=>1),
        'total_ttc' => array('type'=>'real', 'label'=>'TotalTTC', 'enabled'=>1, 'position'=>78, 'notnull'=>0, 'visible'=>1),


        'fk_multicurrency' => array('type'=>'integer', 'label'=>'MulticurrencyId', 'enabled'=>1, 'position'=>120, 'notnull'=>0, 'visible'=>0),
        'multicurrency_code' => array('type'=>'varchar(14)', 'length' => 14, 'label'=>'MulticurrencyCode', 'enabled'=>1, 'position'=>122, 'notnull'=>0, 'visible'=>0),
        'multicurrency_subprice' => array('type'=>'real', 'label'=>'MulticurrencySubprice', 'enabled'=>1, 'position'=>124, 'notnull'=>0, 'visible'=>0),
        'multicurrency_total_ht' => array('type'=>'real', 'label'=>'MulticurrencyTotalHT', 'enabled'=>1, 'position'=>126, 'notnull'=>0, 'visible'=>0),
        'multicurrency_total_tva' => array('type'=>'real', 'label'=>'MulticurrencyTotalVAT', 'enabled'=>1, 'position'=>128, 'notnull'=>0, 'visible'=>0),
        'multicurrency_total_ttc' => array('type'=>'real', 'label'=>'MulticurrencyTotalTTC', 'enabled'=>1, 'position'=>130, 'notnull'=>0, 'visible'=>0),
        'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
        'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'position'=>511, 'notnull'=>0, 'visible'=>-2,),
        'fk_user_valid' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserValid', 'enabled'=>1, 'position'=>512, 'notnull'=>0, 'visible'=>-2,),
        'fk_user_cloture' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserClose', 'enabled'=>1, 'position'=>513, 'notnull'=>0, 'visible'=>-2,),
        'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
        'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
        'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'visible'=>2, 'index'=>1, 'arrayofkeyval'=> array(-1 => 'OperationOrderStatusShortCanceled', 0 => 'OperationOrderStatusShortDraft', 1 => 'OperationOrderStatusShortValidated')),
        'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>1, 'position'=>50, 'notnull'=>0, 'visible'=>0,),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'position'=>1200, 'notnull'=>1, 'visible'=>0,),
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
    public $fk_multicurrency;
    public $multicurrency_code;
    public $multicurrency_subprice;
    public $multicurrency_total_ht;
    public $multicurrency_total_tva;
    public $multicurrency_total_ttc;
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

    /**
     * OperationOrder constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
		global $conf;

        parent::__construct($db);

		$this->init();

		$this->status = self::STATUS_DRAFT;
		$this->entity = $conf->entity;

		$this->lines = &$this->TOperationOrderDet;
		$this->modelpdf = &$this->model_pdf;
		$this->socid = &$this->fk_soc; // Compatibility with select ajax on formadd product
    }

    /**
     * @param User $user User object
     * @return int
     */
    public function save($user)
    {
        if (!empty($this->is_clone))
        {
            // TODO determinate if auto generate
            $this->ref = '(PROV'.$this->id.')';
        }

        return $this->create($user);
    }

    /**
     * Function to create object in database
     *
     * @param   User    $user   user object
     * @return  int                < 0 if ko, > 0 if ok
     */
    public function create(User &$user)
    {
        $this->fk_user_creat = $user->id;

        return parent::create($user);
    }

    /**
     * Function to update object or create or delete if needed
     *
     * @param   User    $user   user object
     * @return  int                < 0 if ko, > 0 if ok
     */
    public function update(User &$user)
    {
        $this->fk_user_modif = $user->id;

        return parent::update($user); // TODO: Change the autogenerated stub
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

        $this->fetch_thirdparty();

        return $res;
    }


    /**
     * @see cloneObject
     * @return void
     */
    public function clearUniqueFields()
    {
        $this->ref = 'Copy of '.$this->ref;
    }


    /**
     * @param User $user User object
     * @return int
     */
    public function delete(User &$user)
    {
        $this->deleteObjectLinked();

        unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
        return parent::delete($user);
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
     * @param User  $user   User object
     * @param int	$notrigger		1=Does not execute triggers, 0=Execute triggers
     * @return int
     */
    public function setDraft($user, $notrigger = 0)
    {
        if ($this->status === self::STATUS_VALIDATED)
        {
            $this->status = self::STATUS_DRAFT;
            $this->withChild = false;

            if (method_exists($this, 'setStatusCommon')) return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'OPERATIONORDER_UNVALIDATE');
            else return $this->update($user);
        }

        return 0;
    }

    /**
     * @param User  $user   User object
     * @param int	$notrigger		1=Does not execute triggers, 0=Execute triggers
     * @return int
     */
    public function setValid($user, $notrigger = 0)
    {
        if ($this->status === self::STATUS_DRAFT)
        {
            $this->ref = $this->getRef();
            $this->fk_user_valid = $user->id;
            $this->status = self::STATUS_VALIDATED;
            $this->withChild = false;

            $this->update($user);
        }

        return 0;
    }

    /**
     * @param User  $user   User object
     * @param int	$notrigger		1=Does not execute triggers, 0=Execute triggers
     * @return int
     */
    public function setAccepted($user, $notrigger = 0)
    {
        if ($this->status === self::STATUS_VALIDATED)
        {
            $this->fk_user_cloture = $user->id;
            $this->status = self::STATUS_ACCEPTED;
            $this->withChild = false;

            $this->update($user);
        }

        return 0;
    }

    /**
     * @param User  $user   User object
     * @param int	$notrigger		1=Does not execute triggers, 0=Execute triggers
     * @return int
     */
    public function setRefused($user, $notrigger = 0)
    {
        if ($this->status === self::STATUS_VALIDATED)
        {
            $this->status = self::STATUS_REFUSED;
            $this->withChild = false;

            if (method_exists($this, 'setStatusCommon')) return $this->setStatusCommon($user, self::STATUS_REFUSED, $notrigger, 'OPERATIONORDER_REFUSE');
            else return $this->update($user);
        }

        return 0;
    }

    /**
     * @param User  $user   User object
     * @param int	$notrigger		1=Does not execute triggers, 0=Execute triggers
     * @return int
     */
    public function setReopen($user, $notrigger = 0)
    {
        if ($this->status === self::STATUS_ACCEPTED || $this->status === self::STATUS_REFUSED)
        {
            $this->status = self::STATUS_VALIDATED;
            $this->withChild = false;

            if (method_exists($this, 'setStatusCommon')) return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'OPERATIONORDER_REOPEN');
            else return $this->update($user);
        }

        return 0;
    }


    /**
     * @param int    $withpicto     Add picto into link
     * @param string $moreparams    Add more parameters in the URL
     * @return string
     */
    public function getNomUrl($withpicto = 0, $moreparams = '')
    {
		global $langs;

        $result='';
        $label = '<u>' . $langs->trans("ShowOperationOrder") . '</u>';
        if (! empty($this->ref)) $label.= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $link = '<a href="'.dol_buildpath('/operationorder/card.php', 1).'?id='.$this->id.urlencode($moreparams).$linkclose;

        $linkend='</a>';

        $picto='generic';
//        $picto='operationorder@operationorder';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';

        $result.=$link.$this->ref.$linkend;

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
        return self::LibStatut($this->status, $mode);
    }

    /**
     * @param int       $status   Status
     * @param int       $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @return string
     */
    public static function LibStatut($status, $mode)
    {
		global $langs;

		$langs->load('operationorder@operationorder');
        $res = '';

        if ($status==self::STATUS_CANCELED) { $statusType='status9'; $statusLabel=$langs->trans('OperationOrderStatusCancel'); $statusLabelShort=$langs->trans('OperationOrderStatusShortCancel'); }
        elseif ($status==self::STATUS_DRAFT) { $statusType='status0'; $statusLabel=$langs->trans('OperationOrderStatusDraft'); $statusLabelShort=$langs->trans('OperationOrderStatusShortDraft'); }
        elseif ($status==self::STATUS_VALIDATED) { $statusType='status1'; $statusLabel=$langs->trans('OperationOrderStatusValidated'); $statusLabelShort=$langs->trans('OperationOrderStatusShortValidate'); }
        elseif ($status==self::STATUS_REFUSED) { $statusType='status5'; $statusLabel=$langs->trans('OperationOrderStatusRefused'); $statusLabelShort=$langs->trans('OperationOrderStatusShortRefused'); }
        elseif ($status==self::STATUS_ACCEPTED) { $statusType='status6'; $statusLabel=$langs->trans('OperationOrderStatusAccepted'); $statusLabelShort=$langs->trans('OperationOrderStatusShortAccepted'); }

        if (function_exists('dolGetStatus'))
        {
            $res = dolGetStatus($statusLabel, $statusLabelShort, '', $statusType, $mode);
        }
        else
        {
            if ($mode == 0) $res = $statusLabel;
            elseif ($mode == 1) $res = $statusLabelShort;
            elseif ($mode == 2) $res = img_picto($statusLabel, $statusType).$statusLabelShort;
            elseif ($mode == 3) $res = img_picto($statusLabel, $statusType);
            elseif ($mode == 4) $res = img_picto($statusLabel, $statusType).$statusLabel;
            elseif ($mode == 5) $res = $statusLabelShort.img_picto($statusLabel, $statusType);
            elseif ($mode == 6) $res = $statusLabel.img_picto($statusLabel, $statusType);
        }

        return $res;
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

    public function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $fk_product = 0, $remise_percent = 0, $info_bits = 0, $fk_remise_except = 0, $price_base_type = 'HT', $pu_ttc = 0, $date_start = '', $date_end = '', $type = 0, $rang = -1, $special_code = 0, $fk_parent_line = 0, $fk_fournprice = null, $pa_ht = 0, $label = '', $array_options = 0, $fk_unit = null, $origin = '', $origin_id = 0, $pu_ht_devise = 0)
    {
        global $mysoc, $conf, $langs, $user;

        $logtext = "::addline commandeid=$this->id, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_percent=$remise_percent";
        $logtext .= ", info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc, date_start=$date_start";
        $logtext .= ", date_end=$date_end, type=$type special_code=$special_code, fk_unit=$fk_unit, origin=$origin, origin_id=$origin_id, pu_ht_devise=$pu_ht_devise";
        dol_syslog(get_class($this).$logtext, LOG_DEBUG);

        if ($this->status == self::STATUS_DRAFT)
        {
            include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

            // Clean parameters
            if (empty($remise_percent)) $remise_percent = 0;
            if (empty($qty)) $qty = 0;
            if (empty($info_bits)) $info_bits = 0;
            if (empty($rang)) $rang = 0;
            if (empty($txtva)) $txtva = 0;
            if (empty($txlocaltax1)) $txlocaltax1 = 0;
            if (empty($txlocaltax2)) $txlocaltax2 = 0;
            if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line = 0;
            if (empty($this->fk_multicurrency)) $this->fk_multicurrency = 0;

            $remise_percent = price2num($remise_percent);
            $qty = price2num($qty);
            $pu_ht = price2num($pu_ht);
            $pu_ht_devise = price2num($pu_ht_devise);
            $pu_ttc = price2num($pu_ttc);
            $pa_ht = price2num($pa_ht);
            if (!preg_match('/\((.*)\)/', $txtva)) {
                $txtva = price2num($txtva); // $txtva can have format '5,1' or '5.1' or '5.1(XXX)', we must clean only if '5,1'
            }
            $txlocaltax1 = price2num($txlocaltax1);
            $txlocaltax2 = price2num($txlocaltax2);
            if ($price_base_type == 'HT')
            {
                $pu = $pu_ht;
            }
            else
            {
                $pu = $pu_ttc;
            }
            $label = trim($label);
            $desc = trim($desc);

            // Check parameters
            if ($type < 0) return -1;

//            if ($date_start && $date_end && $date_start > $date_end) {
//                $langs->load("errors");
//                $this->error = $langs->trans('ErrorStartDateGreaterEnd');
//                return -1;
//            }

            $this->db->begin();

            $product_type = $type;
            if (!empty($fk_product))
            {
//                $product = new Product($this->db);
//                $result = $product->fetch($fk_product);
//                $product_type = $product->type;
//
//                if (!empty($conf->global->STOCK_MUST_BE_ENOUGH_FOR_ORDER) && $product_type == 0 && $product->stock_reel < $qty)
//                {
//                    $langs->load("errors");
//                    $this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnOrder', $product->ref);
//                    $this->errors[] = $this->error;
//                    dol_syslog(get_class($this)."::addline error=Product ".$product->ref.": ".$this->error, LOG_ERR);
//                    $this->db->rollback();
//                    return self::STOCK_NOT_ENOUGH_FOR_ORDER;
//                }
            }
            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

            // Clean vat code
            $reg = array();
            $vat_src_code = '';
            if (preg_match('/\((.*)\)/', $txtva, $reg))
            {
                $vat_src_code = $reg[1];
                $txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
            }

            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $product_type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

            /*var_dump($txlocaltax1);
             var_dump($txlocaltax2);
             var_dump($localtaxes_type);
             var_dump($tabprice);
             var_dump($tabprice[9]);
             var_dump($tabprice[10]);
             exit;*/

            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];
            $pu_ht = $tabprice[3];

            // MultiCurrency
            $multicurrency_total_ht  = $tabprice[16];
            $multicurrency_total_tva = $tabprice[17];
            $multicurrency_total_ttc = $tabprice[18];
            $pu_ht_devise = $tabprice[19];

            // Rang to use
            $ranktouse = $rang;
            if ($ranktouse == -1)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $ranktouse = $rangmax + 1;
            }

            // TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $price = $pu;
            $remise = 0;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }

            // Insert line
            // TODO init le bon objet
            $k = $this->addChild('OperationOrderDet');
            $this->line = $this->TOperationOrderDet[$k];

            $this->line->context = $this->context;

            $this->line->fk_operation_order = $this->id;
            $this->line->fk_product = $fk_product;
            $this->line->description = $desc;
            $this->line->qty = $qty;

            $this->line->time_planned = 0; // TODO
            $this->line->time_spent = 0; // TODO



            $this->line->label=$label;

//            $this->line->vat_src_code=$vat_src_code;
            $this->line->tva_tx=$txtva;
            $this->line->localtax1_tx=($total_localtax1?$localtaxes_type[1]:0);
            $this->line->localtax2_tx=($total_localtax2?$localtaxes_type[3]:0);
            $this->line->localtax1_type=$localtaxes_type[0];
            $this->line->localtax2_type=$localtaxes_type[2];
//            $this->line->fk_product=$fk_product;
            $this->line->product_type=$product_type;
//            $this->line->fk_remise_except=$fk_remise_except;
            $this->line->remise_percent=$remise_percent;
            $this->line->subprice=$pu_ht;
            $this->line->rang=$ranktouse;
            $this->line->info_bits=$info_bits;
            $this->line->total_ht=$total_ht;
            $this->line->total_tva=$total_tva;
            $this->line->total_localtax1=$total_localtax1;
            $this->line->total_localtax2=$total_localtax2;
            $this->line->total_ttc=$total_ttc;
//            $this->line->special_code=$special_code;
            $this->line->origin=$origin;
            $this->line->origin_id=$origin_id;
            $this->line->fk_parent_line=$fk_parent_line;
//            $this->line->fk_unit=$fk_unit;

//            $this->line->date_start=$date_start;
//            $this->line->date_end=$date_end;

//            $this->line->fk_fournprice = $fk_fournprice;
//            $this->line->pa_ht = $pa_ht;

            // Multicurrency
            $this->line->fk_multicurrency			= $this->fk_multicurrency;
            $this->line->multicurrency_code			= $this->multicurrency_code;
            $this->line->multicurrency_subprice		= $pu_ht_devise;
            $this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
            $this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

            if (is_array($array_options) && count($array_options)>0) {
                $this->line->array_options=$array_options;
            }

            $result=$this->line->create($user);
            if ($result > 0)
            {
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true, 'DESC');

                // Mise a jour informations denormalisees au niveau de la commande meme
                $result=$this->update_price(1, 'auto', 0, $mysoc);	// This method is designed to add line from user input so total calculation must be done using 'auto' mode.
                if ($result > 0)
                {
                    $this->db->commit();
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
}


class OperationOrderDet extends SeedObject
{
    public $table_element = 'operationorderdet';

    public $element = 'operationorderdet';

    /** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    public $fields=array(
//        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'visible'=>-1, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
        'fk_operation_order' => array('type'=>'integer', 'label'=>'OperationOrder', 'enabled'=>1, 'position'=>5, 'notnull'=>1, 'visible'=>0,),
        'fk_product' => array('type'=>'integer:Product:product/class/product.class.php:1', 'label'=>'Product', 'enabled'=>1, 'position'=>35, 'notnull'=>-1, 'visible'=>-1, 'index'=>1,),
        'fk_parent_line' => array('type'=>'integer'),
        'label' => array('type'=>'varchar(255)', 'length' => 255, 'label'=>'Label', 'enabled'=>1, 'position'=>35, 'notnull'=>0, 'visible'=>3),
        'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>3,),
        'qty' => array('type'=>'real', 'label'=>'Qty', 'enabled'=>1, 'position'=>45, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1', 'css'=>'maxwidth75imp'),
        'time_planned' => array('type'=>'integer', 'label'=>'TimePlanned', 'enabled'=>1, 'position'=>50, 'notnull'=>0, 'visible'=>1),
        'time_spent' => array('type'=>'integer', 'label'=>'TimePlanned', 'enabled'=>1, 'position'=>50, 'notnull'=>0, 'visible'=>1),
        'subprice' => array('type'=>'real', 'label'=>'Subprice', 'enabled'=>1, 'position'=>52, 'notnull'=>0, 'visible'=>1),
        'tva_tx' => array('type'=>'real', 'label'=>'VAT', 'enabled'=>1, 'position'=>54, 'notnull'=>0, 'visible'=>1),
        'localtax1_tx' => array('type'=>'real', 'label'=>'Localtax1Tx', 'enabled'=>1, 'position'=>56, 'notnull'=>0, 'visible'=>1),
        'localtax1_type' => array('type'=>'varchar', 'length' => 128, 'label'=>'Localtax1Type', 'enabled'=>1, 'position'=>58, 'notnull'=>0, 'visible'=>1),
        'localtax2_tx' => array('type'=>'real', 'label'=>'Localtax2Tx', 'enabled'=>1, 'position'=>60, 'notnull'=>0, 'visible'=>1),
        'localtax2_type' => array('type'=>'varchar', 'length' => 128, 'label'=>'Localtax1Type', 'enabled'=>1, 'position'=>62, 'notnull'=>0, 'visible'=>1),
        'remise_percent' => array('type'=>'real', 'label'=>'Discount', 'enabled'=>1, 'position'=>64, 'notnull'=>0, 'visible'=>1),
        'total_ht' => array('type'=>'real', 'label'=>'TotalHT', 'enabled'=>1, 'position'=>70, 'notnull'=>0, 'visible'=>1),
        'total_tva' => array('type'=>'real', 'label'=>'TotalVAT', 'enabled'=>1, 'position'=>72, 'notnull'=>0, 'visible'=>1),
        'total_localtax1' => array('type'=>'real', 'label'=>'TotalTTC', 'enabled'=>1, 'position'=>74, 'notnull'=>0, 'visible'=>1),
        'total_localtax2' => array('type'=>'real', 'label'=>'TotalTTC', 'enabled'=>1, 'position'=>76, 'notnull'=>0, 'visible'=>1),
        'total_ttc' => array('type'=>'real', 'label'=>'TotalTTC', 'enabled'=>1, 'position'=>78, 'notnull'=>0, 'visible'=>1),
        'product_type' => array('type'=>'integer', 'label'=>'ProductType', 'enabled'=>1, 'position'=>90, 'notnull'=>1, 'visible'=>0),
        'rang' => array('type'=>'integer', 'label'=>'Rank', 'enabled'=>1, 'position'=>92, 'notnull'=>0, 'visible'=>0),
        'fk_multicurrency' => array('type'=>'integer', 'label'=>'MulticurrencyId', 'enabled'=>1, 'position'=>120, 'notnull'=>0, 'visible'=>0),
        'multicurrency_code' => array('type'=>'varchar(14)', 'length' => 14, 'label'=>'MulticurrencyCode', 'enabled'=>1, 'position'=>122, 'notnull'=>0, 'visible'=>0),
        'multicurrency_subprice' => array('type'=>'real', 'label'=>'MulticurrencySubprice', 'enabled'=>1, 'position'=>124, 'notnull'=>0, 'visible'=>0),
        'multicurrency_total_ht' => array('type'=>'real', 'label'=>'MulticurrencyTotalHT', 'enabled'=>1, 'position'=>126, 'notnull'=>0, 'visible'=>0),
        'multicurrency_total_tva' => array('type'=>'real', 'label'=>'MulticurrencyTotalVAT', 'enabled'=>1, 'position'=>128, 'notnull'=>0, 'visible'=>0),
        'multicurrency_total_ttc' => array('type'=>'real', 'label'=>'MulticurrencyTotalTTC', 'enabled'=>1, 'position'=>130, 'notnull'=>0, 'visible'=>0),
        'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
        'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'position'=>511, 'notnull'=>0, 'visible'=>-2,),
        'import_key' => array('type'=>'varchar(14)', 'length' => 14, 'label'=>'ImportId', 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
        'info_bits' => array('type'=>'int', 'visible'=>0),
    );

    public $fk_operation_order;
    public $fk_product;
    public $fk_parent_line;
    public $description;
    public $qty;
    public $time_planned;
    public $time_spent;
    public $subprice;
    public $tva_tx;
    public $localtax1_tx;
    public $localtax1_type;
    public $localtax2_tx;
    public $localtax2_type;
    public $remise_percent;
    public $total_ht;
    public $total_tva;
    public $total_localtax1;
    public $total_localtax2;
    public $total_ttc;
    public $product_type;
    public $rang;
    public $fk_multicurrency;
    public $multicurrency_code;
    public $multicurrency_subprice;
    public $multicurrency_total_ht;
    public $multicurrency_total_tva;
    public $multicurrency_total_ttc;
    public $fk_user_creat;
    public $fk_user_modif;
    public $import_key;

    /**
     * OperationOrderDet constructor.
     * @param DoliDB    $db    Database connector
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->init();
    }
}
