<?php
/* Copyright (c) 2005       Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2018	Laurent Destailleur	 <eldy@users.sourceforge.net>
 * Copyright (c) 2005-2018	Regis Houssin		 <regis.houssin@inodbox.com>
 * Copyright (C) 2012		Florian Henry		 <florian.henry@open-concept.pro>
 * Copyright (C) 2014		Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2014		Alexis Algoud		 <alexis@atm-consulting.fr>
 * Copyright (C) 2018       Nicolas ZABOURI		 <info@inovea-conseil.com>
 * Copyright (C) 2019       Abbes Bahfir            <dolipar@dolipar.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	 \file       htdocs/user/class/usergroup.class.php
 *	 \brief      File of class to manage user groups
 */

require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

/**
 *	Class to manage user groups
 */
class UserGroupOperationOrder extends UserGroup
{
	/**
	 * 	Return array of User objects for group this->id (or all if this->id not defined)
	 *
	 * 	@param	string	$excludefilter		Filter to exclude
	 *  @param	int		$mode				0=Return array of user instance, 1=Return array of users id only
	 * 	@return	mixed						Array of users or -1 on error
	 */
	public function listUsersForGroup($excludefilter = '', $mode = 0)
	{
		global $conf, $user;

		$ret=array();

		$sql = "SELECT u.rowid";
		if (! empty($this->id)) $sql.= ", ug.entity as usergroup_entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		if (! empty($this->id)) $sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
		$sql.= " WHERE 1 = 1";
		if (! empty($this->id)) $sql.= " AND ug.fk_user = u.rowid";
		if (! empty($this->id)) $sql.= " AND ug.fk_usergroup = ".$this->id;
		if (! empty($conf->multicompany->enabled) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
		{
			$sql.= " AND u.entity IN (0,1,".$conf->entity.") AND ug.entity='".$conf->entity."'";
		}
		if (! empty($excludefilter)) $sql.=' AND ('.$excludefilter.')';

		dol_syslog(get_class($this)."::listUsersForGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				if (! array_key_exists($obj->rowid, $ret))
				{
					if ($mode != 1)
					{
						$newuser=new User($this->db);
						$newuser->fetch($obj->rowid);
						$ret[$obj->rowid]=$newuser;
					}
					else $ret[$obj->rowid]=$obj->rowid;
				}
				if ($mode != 1 && ! empty($obj->usergroup_entity))
				{
					$ret[$obj->rowid]->usergroup_entity[]=$obj->usergroup_entity;
				}
			}

			$this->db->free($resql);

			return $ret;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}
}
