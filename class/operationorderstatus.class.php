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
	const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/** @var array $TStatus Array of translate key for each const */
	public static $TStatus = array(
		self::STATUS_DRAFT => 'OperationOrderStatusShortDraft'
		,self::STATUS_VALIDATED => 'OperationOrderStatusShortValidated'
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
		'code' => array('type'=>'varchar(128)', 'label'=>'Code', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>4, 'default'=>'', 'index'=>1),
		'label' => array('type'=>'varchar(128)', 'label'=>'Label', 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'visible'=>1),
		'color' => array('type'=>'varchar(16)', 'label'=>'Color', 'enabled'=>1, 'position'=>30, 'notnull'=>1, 'visible'=>1, 'default' => '#3c8dbc'),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'visible'=>2, 'index'=>1, 'arrayofkeyval'=> array(-1 => 'OperationOrderStatusStatusShortCanceled', 0 => 'OperationOrderStatusStatusShortDraft', 1 => 'OperationOrderStatusStatusShortValidated')),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'position'=>1200, 'notnull'=>1, 'visible'=>0,),
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

		$this->status = self::STATUS_DRAFT;
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

		if ($this->status == self::STATUS_VALIDATED)
		{
			$this->status = self::STATUS_DRAFT;
			$this->withChild = false;

			$this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'OPERATIONORDER_DRAFT');
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
		global $conf, $langs;

		if ($this->status == self::STATUS_DRAFT)
		{
			$this->ref = $this->getRef();
			$this->status = self::STATUS_VALIDATED;
			$this->withChild = false;

			$ret =  $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'OPERATIONORDER_VALID');
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

		$link = dol_buildpath('/operationorder/operationorderstatus_card.php', 1).'?id='.$this->id.urlencode($moreparams);

		return $this->getBadge($link);
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
			'attr' => array(
				'style' => 'background-color: '.$this->color.'; color: '.(colorIsLight($this->color)?'#ffffff':'#000000'),
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

		if ($status==self::STATUS_VALIDATED) {
			$statusType='status4';
			$statusLabel=$langs->trans('OperationOrderStatusValidated');
			$statusLabelShort=$langs->trans('OperationOrderStatusShortValidate');
		}
		elseif ($status==self::STATUS_CANCELED) {
			$statusType='status9';
			$statusLabel=$langs->trans('OperationOrderStatusCancel');
			$statusLabelShort=$langs->trans('OperationOrderStatusShortCancel');
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
}
