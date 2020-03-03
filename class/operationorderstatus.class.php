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


class OperationOrderStatus extends SeedObject
{
	/**
	 * Draft status
	 */
	const STATUS_DISABLED = 0;
	/**
	 * Validated status
	 */
	const STATUS_ACTIVE = 1;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_DISABLED => 'OperationOrderStatusShortDraft'
		,self::STATUS_ACTIVE => 'OperationOrderStatusShortValidated'
	);

	/** @var string $table_element Table name in SQL */
	public $table_element = 'operationorder_status';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'operationorderstatus';

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
	public $isextrafieldmanaged = 0;

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
		'code' => array(
			'type'=>'varchar(128)',
			'label'=>'Code',
			'enabled'=>1,
			'position'=>10,
			'notnull'=>1,
			'required'=>1,
			'visible'=>1,
			'default'=>'',
			'index'=>1
		),
		'label' => array(
			'type'=>'varchar(128)',
			'label'=>'Label',
			'enabled'=>1,
			'position'=>20,
			'notnull'=>1,
			'required'=>1,
			'visible'=>1
		),
		'color' => array(
			'type'=>'varchar(16)',
			'label'=>'Color', 'enabled'=>1, 'position'=>30, 'notnull'=>1, 'visible'=>1,
			'default' => '#3c8dbc'
		),
//		'edit' => array(
//			'type'=>'smallint',
//			'label'=>'CouldEdit',
//			'help' => 'CouldEditHelp',
//			'enabled'=>1,
//			'position'=>30,
//			'visible'=>1
//		),
		'rank' => array(
			'type'=>'int',
			'label'=>'Rank',
			'help' => 'RankHelp',
			'enabled'=>1,
			'position'=>40,
			'visible'=>0
		),
		'import_key' => array(
			'type'=>'varchar(14)',
			'label'=>'ImportId',
			'enabled'=>1,
			'position'=>1000,
			'notnull'=>-1,
			'visible'=>-2,
		),
		'status' => array(
			'type'=>'smallint',
			'label'=>'Status',
			'enabled'=>1,
			'position'=>1000,
			'notnull'=>1,
			'visible'=>2,
			'index'=>1,
			'default'=>1,
			'arrayofkeyval'=> array(
				0 => 'OperationOrderStatusStatusShortDraft',
				1 => 'OperationOrderStatusStatusShortValidated'
			)
		),
		'entity' => array(
			'type'=>'integer',
			'label'=>'Entity',
			'enabled'=>1,
			'position'=>1200,
			'notnull'=>1,
			'visible'=>0,
		),
	);

	public $code;
	public $label;
	public $import_key;
	public $status;
	public $entity;


	/**
	 * OperationOrderStatus constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		global $conf;

		parent::__construct($db);

		$this->init();

		$this->status = self::STATUS_DISABLED;
		$this->entity = $conf->entity;
	}

	/**
	 * @param User $user User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
	 * @return int
	 */
	public function save($user, $notrigger = false)
	{
		return $this->create($user, $notrigger);
	}

	/**
	 * @param User $user User object
	 * @param	bool	$notrigger	false=launch triggers after, true=disable triggers
	 * @return int
	 */
	public function delete(User &$user, $notrigger = false)
	{
		$this->deleteObjectLinked();

		unset($this->fk_element); // avoid conflict with standard Dolibarr comportment
		return parent::delete($user, $notrigger);
	}

	/**
	 * @param User  $user   User object
	 * @param int	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 * @return int
	 */
	public function setDraft($user, $notrigger = 0)
	{
		global $conf, $langs;

		if ($this->status == self::STATUS_ACTIVE)
		{
			$this->status = self::STATUS_DISABLED;
			$this->withChild = false;

			$this->setStatusCommon($user, self::STATUS_DISABLED, $notrigger, 'OPERATIONORDER_DRAFT');
		}

		return 0;
	}

	/**
	 * @param User  $user   User object
	 * @param int	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 * @return int
	 */
	public function setActive($user, $notrigger = 0)
	{
		global $conf, $langs;

		if ($this->status == self::STATUS_DISABLED)
		{
			$this->status = self::STATUS_ACTIVE;
			$this->withChild = false;

			$ret =  $this->setStatusCommon($user, self::STATUS_ACTIVE, $notrigger, 'OPERATIONORDER_ACTIVE');
		}

		return 0;
	}



	/**
	 * @param int    $withpicto     Add picto into link (disabled but keep for dolibarr compatibility)
	 * @param string $moreparams    Add more parameters in the URL
	 * @return string
	 */
	public function getNomUrl($withpicto = 0, $moreparams = '')
	{
		global $langs;

		$link = $this->getCardUrl($moreparams);

		return $this->getBadge($link);
	}

	/**
	 * @param string $moreparams    Add more parameters in the URL
	 * @return string
	 */
	public function getCardUrl($moreparams = '')
	{
		return dol_buildpath('/operationorder/operationorderstatus_card.php', 1).'?id='.$this->id.urlencode($moreparams);
	}



	/**
	 * Function getBadge
	 *
	 * @param   string  $url        the url for link
	 * @return  string              Html badge
	 */
	function getBadge($url = '')
	{
		$params = array(
			'css' => 'badge-status',
			'attr' => array(
				'style' => 'background-color: '.$this->color.'; color: '.(!colorIsLight($this->color)?'#ffffff':'#000000'),
			),
		);
		return dolGetBadge($this->label, '', '', '', $url, $params);
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

		$object = new self($db);
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

		if ($status==self::STATUS_ACTIVE) {
			$statusType='status4';
			$statusLabel=$langs->trans('OperationOrderStatusActivated');
			$statusLabelShort=$langs->trans('OperationOrderStatusShortActivated');
		}
		elseif ($status==self::STATUS_DISABLED) {
			$statusType='status9';
			$statusLabel=$langs->trans('OperationOrderStatusDisabled');
			$statusLabelShort=$langs->trans('OperationOrderStatusShortDisabled');
		}

		$res = dolGetStatus($statusLabel, $statusLabelShort, '', $statusType, $mode);
		return $res;
	}

	/**
	 * @param User $user
	 * @param $newStatusId
	 * @return bool
	 */
	public function checkStatusTransition($user, $newStatusId){
		return true;
	}

	/**
	 * @param User $user
	 * @param $curentStatus
	 * @param $newStatusId
	 * @return bool
	 */
	public static function staticCheckStatusTransition($user, $curentStatus, $newStatusId){
		global $db;

		$object = new self($db);
		$res = $object->fetch($curentStatus);
		if($res>0)
		{
			return $object->checkStatusTransition($user, $newStatusId);
		}

		return false;
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

		if($key == 'color')
		{
			$out = '<input disabled type="color" class="flat '.$morecss.'"  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'>';
		}
		elseif($key == 'status')
		{
			$out = $this->getLibStatut(2);
		}
		elseif($key == 'edit')
		{
			$checked = !empty($value)?' checked ':'';
			$out ='<input disabled '.$checked.' type="checkbox" class="flat '.$morecss.'"  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="1" '.($moreparam?$moreparam:'').'>';
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

		if($key == 'color')
		{
			$out ='<input type="color" class="flat '.$morecss.'"  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'>';
		}
		elseif($key == 'edit')
		{
			$checked = !empty($value)?' checked ':'';
			$out ='<input'.$checked.' type="checkbox" class="flat '.$morecss.'"  name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="1" '.($moreparam?$moreparam:'').'>';
		}
		else{
			$out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
		}

		return $out;
	}

	/**
	 * @param int  $limit       Limit element returned
	 * @param bool $loadChild   used to load children from database
	 * @return array
	 */
	public function fetchAll($limit = 0, $loadChild = true, $TFilter = array())
	{
		$TRes = array();

		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE 1';
		if (!empty($TFilter))
		{
			foreach ($TFilter as $field => $value)
			{
				$sql.= ' AND '.$field.' = '.$this->quote($value, $this->fields[$field]);
			}
		}

		$sql.= ' ORDER BY rank ASC';

		if ($limit) $sql.= ' LIMIT '.$limit;

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


	static public function updateRank($rowid,$rank)
	{
		global $db;

		$status = new self($db);

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$status->table_element.' SET rank = '.intval($rank);
		$sql.= ' WHERE rowid = '.intval($rowid);

		if (! $db->query($sql))
		{
			dol_print_error($db->db);
			return false;
		}

		return true;
	}

}
