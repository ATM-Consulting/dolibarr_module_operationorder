<?php

if (!class_exists('SeedObject'))
{

    define('INC_FROM_DOLIBARR', true);
    require_once dirname(__FILE__).'/../config.php';
}


class OperationOrderJoursOff extends SeedObject
{

    /** @var string $table_element Table name in SQL */
    public $table_element = 'operationorderjoursoff';

    /** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
    public $element = 'operationorderjoursoff';

    /** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
    public $isextrafieldmanaged = 1;

    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
    public $ismultientitymanaged = 1;

    /** @var string $picto a picture file in [@...]/img/object_[...@].png  */
    public $picto = 'operationorder@operationorder';

	/** @var int $fullday flag for event display like full day event */
	public $fullday = 1;

    public $fields=array(
        'date' => array('type'=>'date', 'label'=>'Date', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>1),
        'label' => array('type'=>'text', 'label'=>'Label', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'fk_user_author' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'position'=>50, 'notnull'=>1, 'visible'=>1, 'foreignkey'=>'user.rowid',),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'position'=>60, 'notnull'=>1, 'visible'=>0,),
    );

    public $date;

    public $label;

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
	 * @param array $TFilter 	array off filters ('date' => '2020-11-13' OR 'date' => array('operator' => '>', 'value' => '2020-11-13'))
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
				if (!is_array($value)) $sql.= ' AND '.$field.' = '.$this->quote($value, $this->fields[$field]);
				else
				{
					if (!empty($value['operator']) && !empty($value['value'])) $sql.=  ' AND '.$field. ' ' . $value['operator'] . ' ' . $this->quote($value, $this->fields[$field]);
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

	function synchronizeFromURL($url='') {
		global $user;

		// default url
		if (empty($url)) $url = "https://calendar.google.com/calendar/ical/fr.french%23holiday%40group.v.calendar.google.com/public/basic.ics";

		dol_include_once('/abricot/includes/class/class.iCalReader.php');
		$iCal = new ICalReader( $url );

		$TListDays[strtoupper(trim("Noël"))] = true;
		$TListDays[strtoupper(trim("L'Armistice"))] = true;
		$TListDays[strtoupper(trim("La Toussaint"))] = true;
		$TListDays[strtoupper(trim("L'Assomption"))] = true;
		$TListDays[strtoupper(trim("La fête nationale"))] = true;
		$TListDays[strtoupper(trim("Le lundi de Pentecôte"))] = true;
		$TListDays[strtoupper(trim("Pentecôte"))] = true;
		$TListDays[strtoupper(trim("L'Ascension"))] = true;
		$TListDays[strtoupper(trim("Fête de la Victoire 1945"))] = true;
		$TListDays[strtoupper(trim("La fête du Travail"))] = true;
		$TListDays[strtoupper(trim("Le lundi de Pâques"))] = true;
		$TListDays[strtoupper(trim("Pâques"))] = true;
		$TListDays[strtoupper(trim("Jour de l'an"))] = true;

		foreach($iCal->cal['VEVENT'] as $event) {
			$label = strtoupper(trim($event['SUMMARY']));
			if($event['STATUS']=='CONFIRMED' && !empty($TListDays[$label])) {

				$jf = new OperationOrderJoursOff($this->db);
				$jf->label = $event['SUMMARY'];
				$jf->fk_user_author = $user->id;

				$aaaa = substr($event['DTSTART'], 0,4);
				$mm = substr($event['DTSTART'], 4,2);
				$jj = substr($event['DTSTART'], 6,2);

				$jf->set_date('date', $aaaa.'-'.$mm.'-'.$jj.' 00:00:00');

				if (!$jf->alreadyExists())
				{
					$ret = $jf->save($user);
				}

			}


		}

	}

	function alreadyExists(){
		global $conf;
		//on récupère toutes les dates de jours fériés existant
		$sql="SELECT count(*) as 'nb'  FROM ".MAIN_DB_PREFIX.$this->table_element."
             WHERE date='".$this->db->idate($this->date)."'";
		$res = $this->db->query($sql);
		$obj = $this->db->fetch_object($res);

		//on teste si l'un d'eux est égal à celui que l'on veut créer
		if($obj->nb > 0){
			return 1;
		}

		return 0;
	}

	/**
	 * @param string date sous la forme Y-m-d 00:00:00
	 * @return bool|mixed
	 */
	function isOff($date) {
		global $conf, $TCacheTFerie;

		if(empty($TCacheTFerie))$TCacheTFerie=array();

		if(!empty($TCacheTFerie[$date])) return $TCacheTFerie[$date];

		//on récupère toutes les dates de jours fériés existant
		$sql="SELECT count(*) as 'nb'  FROM ".MAIN_DB_PREFIX.$this->table_element."
             WHERE entity IN (0,".(! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)?"1,":"").$conf->entity.")
             AND  date='".$this->db->escape($date)."'";

		$res = $this->db->query($sql);

		if ($res) $obj = $this->db->fetch_object($res);

		//on teste si l'un d'eux est égal à celui que l'on veut créer
		if($obj->nb > 0){
			$TCacheTFerie[$date] = true;
		}
		else {
			$TCacheTFerie[$date] = false;
		}

		return $TCacheTFerie[$date];
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
