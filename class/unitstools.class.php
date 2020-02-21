<?php

class UnitsTools{

	/**
	 * @param string $code
	 * @param string $mode 0= id , short_label=Use short label as value, code=use code
	 * @return int            <0 if KO, Id of code if OK
	 */
	static public function getUnitFromCode($code, $mode = 'code')
	{
		global $db;

		if($mode == 'short_label'){
			return dol_getIdFromCode($db, $code, 'c_units', 'short_label', 'rowid');
		}
		elseif($mode == 'code'){
			return dol_getIdFromCode($db, $code, 'c_units', 'code', 'rowid');
		}

		return $code;
	}

	/**
	 * @param double $value
	 * @param int $fk_unit
	 * @param int $fk_new_unit
	 * @return double
	 */
	static public function unitConverteur($value, $fk_unit, $fk_new_unit = 0){
		global $db;

		$value  = doubleval(price2num($value));
		$fk_unit = intval($fk_unit);

		// Calcul en unité de base
		$scaleUnitPow = self::scaleOfUnitPow($fk_unit);

		// convert to standard unit
		$value  = $value * $scaleUnitPow;
		if($fk_new_unit !=0 ){
			// Calcul en unité de base
			$scaleUnitPow = self::scaleOfUnitPow($fk_new_unit);
			if(!empty($scaleUnitPow))
			{
				// convert to new unit
				$value  = $value / $scaleUnitPow;
			}
		}
		return round($value, 2);
	}



	/**
	 * @param $id int
	 * @return float|int
	 */
	static public function scaleOfUnitPow($id)
	{
		$unit = self::dbGetRow('SELECT scale, unit_type from '.MAIN_DB_PREFIX.'c_units WHERE rowid = '.intval($id));
		if($unit){

			if($unit->unit_type == 'time'){
				return doubleval($unit->scale);
			}

			return pow ( 10, doubleval($unit->scale));
		}

		return 0;
	}

	/**
	 * return first result from query
	 * @param string sql
	 * @return bool| var
	 */
	static public function dbGetvalue($sql)
	{
		global $db;
		$sql .= ' LIMIT 1;';

		$res = $db->query($sql);
		if ($res)
		{
			$Tresult = $db->fetch_row($res);
			return $Tresult[0];
		}

		return false;
	}

	/**
	 * return first result from query
	 * @param string sql
	 * @return bool| var
	 */
	static public function dbGetRow($sql)
	{
		global $db;
		$sql .= ' LIMIT 1;';

		$res = $db->query($sql);
		if ($res)
		{
			return $db->fetch_object($res);
		}

		return false;
	}

}
