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

/**
 * 	\defgroup   operationorder     Module OperationOrder
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/operationorder/core/modules directory.
 *  \file       htdocs/operationorder/core/modules/modOperationOrder.class.php
 *  \ingroup    operationorder
 *  \brief      Description and activation file for module OperationOrder
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module OperationOrder
 */
class modOperationOrder extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;

		$this->editor_name = 'ATM-Consulting';
		$this->editor_url = 'https://www.atm-consulting.fr';

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 104158; // 104000 to 104999 for ATM CONSULTING // ancien numéro 104088
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'operationorder';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "ATM";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Description of module OperationOrder";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.9.1';
		// Key used in llx_const table to save module status enabled/disabled (where OPERATIONORDER is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='operationorder@operationorder';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /operationorder/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /operationorder/core/modules/barcode)
		// for specific css file (eg: /operationorder/css/operationorder.css.php)
		//$this->module_parts = array(
		//                        	'triggers' => 0,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
		//							'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
		//							'substitutions' => 0,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
		//							'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
		//							'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
		//                        	'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
		//							'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
		//							'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
		//							'css' => array('/operationorder/css/operationorder.css.php'),	// Set this to relative path of css file if module has its own css file
	 	//							'js' => array('/operationorder/js/operationorder.js'),          // Set this to relative path of js file if module must load a js on all pages
		//							'hooks' => array('hookcontext1','hookcontext2')  	// Set here all hooks context managed by module
		//							'dir' => array('output' => 'othermodulename'),      // To force the default directories names
		//							'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@operationorder')) // Set here all workflow context managed by module
		//                        );
		$this->module_parts = array(
		    'models' => 1,
			'triggers' => 1,
			'hooks' => array(
				'ordersuppliercard',
                'productdao'
			)
        );

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/operationorder/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into operationorder/admin directory, to use to setup module.
		$this->config_page_url = array("operationorder_setup.php@operationorder");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->conflictwith = array();	// List of modules id this module is in conflict with
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("operationorder@operationorder");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('OPERATIONORDER_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('OPERATIONORDER_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:operationorder@operationorder:$user->rights->operationorder->read:/operationorder/mynewtab1.php?id=__ID__',  	// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:operationorder@operationorder:$user->rights->othermodule->read:/operationorder/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                     						// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view
        $this->tabs = array('user:+userplanning:Planning:operationorder@operationorder:$user->rights->operationorder->read:/operationorder/operationorder_userplanning.php?objectid=__ID__&objecttype=user',
            'group:+usergroupplanning:Planning:operationorder@operationorder:$user->rights->operationorder->read:/operationorder/operationorder_userplanning.php?objectid=__ID__&objecttype=usergroup',
            );

        // Dictionaries
	    if (! isset($conf->operationorder->enabled))
        {
        	$conf->operationorder=new stdClass();
        	$conf->operationorder->enabled=0;
        }
		$this->dictionaries=array(
            'langs' => 'operationorder@operationorder',
            'tabname' => array(
                MAIN_DB_PREFIX.'c_operationorder_type'
            ),
            'tablib' => array(
                'OperationOrderDictTypeLabel'
            ),                                                    // Label of tables
            'tabsql' => array(
                'SELECT f.rowid, f.code, f.label, f.position, f.active, f.entity FROM '.MAIN_DB_PREFIX.'c_operationorder_type as f WHERE f.entity IN (0, '.$conf->entity.')'
            ),
            'tabsqlsort' => array(
                'position ASC, label ASC'
            ),
            'tabfield' => array(
                'code,label,position'
            ),
            'tabfieldvalue' => array(
                'code,label,position,entity'
            ),
            'tabfieldinsert' => array(
                'code,label,position,entity'
            ),
            'tabrowid' => array(
                'rowid'
            ),
            'tabcond' => array(
                $conf->operationorder->enabled
            )
        );
        /* Example:
        if (! isset($conf->operationorder->enabled)) $conf->operationorder->enabled=0;	// This is to avoid warnings
        $this->dictionaries=array(
            'langs'=>'operationorder@operationorder',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->operationorder->enabled,$conf->operationorder->enabled,$conf->operationorder->enabled)												// Condition to show each dictionary
        );
        */

        // Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		// Example:
		//$this->boxes=array(array(0=>array('file'=>'myboxa.php','note'=>'','enabledbydefaulton'=>'Home'),1=>array('file'=>'myboxb.php','note'=>''),2=>array('file'=>'myboxc.php','note'=>'')););

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'operationorder_read';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'operationorder_write';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'operationorder_delete';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';		    // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'operationorder_read';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'operationorder_status_read';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'status';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'read';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'operationorder_status_write';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'status';		    // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'write';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

        $this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'operationorder_planning_read';	// Permission label
        $this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
        $this->rights[$r][4] = 'planning';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $this->rights[$r][5] = 'read';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $r++;

        $this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'operationorder_userplanning_write';	// Permission label
        $this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
        $this->rights[$r][4] = 'userplanning';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $this->rights[$r][5] = 'write';
        $r++;

        // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $r++;$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'operationorder_usergroupplanning_write';	// Permission label
        $this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
        $this->rights[$r][4] = 'usergroupplanning';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $this->rights[$r][5] = 'write';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $r++;

        // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $r++;$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'operationorder_manager_read';	// Permission label
        $this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
        $this->rights[$r][4] = 'manager';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $this->rights[$r][5] = 'read';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        $r++;

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus
		//
		// Example to declare a new Top Menu entry and its Left menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=operationorder',		// Put 0 if this is a single top menu or keep fk_mainmenu to give an entry on left
									'type'=>'top',			                // This is a Top menu entry
									'titre'=>'OperationOrderTopMenu',
									'mainmenu'=>'operationorder',
									'leftmenu'=>'operationorder_left',			// This is the name of left menu for the next entries
			 						'url'=>'/operationorder/list.php',
									'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->operationorder->enabled',	// Define condition to show or hide menu entry. Use '$conf->operationorder->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->operationorder->read',			                // Use 'perms'=>'$user->rights->operationorder->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		 $r++;

		// Example to declare a Left Menu entry into an existing Top menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		//							'type'=>'left',			                // This is a Left menu entry
		//							'titre'=>'OperationOrder left menu',
		//							'mainmenu'=>'operationorder',
		//							'leftmenu'=>'operationorder_left',			// Goes into left menu previously created by the mainmenu
		//							'url'=>'/operationorder/pagelevel2.php',
		//							'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'enabled'=>'$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->operationorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//							'perms'=>'1',			                // Use 'perms'=>'$user->rights->operationorder->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		// $r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>$langs->trans('LeftMenuOperationOrderCreate'),
			'mainmenu'=>'operationorder',
			'leftmenu'=>'operationorder_left_create',
			'url'=>'/operationorder/operationorder_card.php?action=create',
			'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=> '$user->rights->operationorder->write',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0
		);				                // 0=Menu for internal users, 1=external users, 2=both
		$r++;

//        $this->menu[$r]=array(
//			'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left',			                // Put 0 if this is a top menu
//            'type'=>'left',			                // This is a Top menu entry
//            'titre'=>$langs->trans('LeftMenuOperationOrder'),
//            'mainmenu'=>'operationorder',
//            'leftmenu'=>'operationorder_left_list',
//            'url'=>'/operationorder/list.php',
//            'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//            'position'=>1000+$r,
//            'enabled'=>'$conf->operationorder->enabled',	// Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled.
//            'perms'=>'$user->rights->operationorder->read',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
//            'target'=>'',
//            'user'=>0
//        );
//        $r++;



        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>$langs->trans('LeftMenuOperationOrderList'),
            'mainmenu'=>'operationorder',
            'leftmenu'=>'operationorder_left_list',
            'url'=>'/operationorder/list.php',
            'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=> '$user->rights->operationorder->read',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0
        );				                // 0=Menu for internal users, 1=external users, 2=both
        $r++;

        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>$langs->trans('LeftMenuOperationOrderListHistory'),
            'mainmenu'=>'operationorder',
            'leftmenu'=>'operationorder_left_list_history',
            'url'=>'/operationorder/operationorder_history.php',
            'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=> '$user->rights->operationorder->read',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0
        );				                // 0=Menu for internal users, 1=external users, 2=both
        $r++;

        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>$langs->trans('LeftMenuOperationOrderPlanning'),
            'mainmenu'=>'operationorder',
            'leftmenu'=>'operationorder_left_planning',
            'url'=>'/operationorder/operationorder_planning.php',
            'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=> '$user->rights->operationorder->planning->read',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0
        );				                // 0=Menu for internal users, 1=external users, 2=both
        $r++;

        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>$langs->trans('LeftMenuOperationOrderManager'),
            'mainmenu'=>'operationorder',
            'leftmenu'=>'operationorder_left_manager',
            'url'=>'/operationorder/operationorder_manager.php',
            'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=> '$user->rights->operationorder->manager->read',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0
        );				                // 0=Menu for internal users, 1=external users, 2=both
        $r++;



		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=operationorder', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',	// This is a Left menu entry
			'titre'=>$langs->trans('LeftMenuOperationOrderStatusMenu'),
			'mainmenu'=>'operationorder',
			'leftmenu'=>'operationorder_left_status',
			'url'=>'/operationorder/operationorderstatus_list.php',
			'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=> '$user->rights->operationorder->status->read',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0
		);				                // 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left_status',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>$langs->trans('LeftMenuOperationOrderStatusCreate'),
			'mainmenu'=>'operationorder',
			'leftmenu'=>'operationorder_left_status_create',
			'url'=>'/operationorder/operationorderstatus_card.php?action=create',
			'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=> '$user->rights->operationorder->status->write',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0
		);				                // 0=Menu for internal users, 1=external users, 2=both
		$r++;

        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left_status',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>$langs->trans('LeftMenuOperationOrderStatusList'),
			'mainmenu'=>'operationorder',
			'leftmenu'=>'operationorder_left_status_list',
            'url'=>'/operationorder/operationorderstatus_list.php',
            'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=> '$user->rights->operationorder->status->read',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0
        );				                // 0=Menu for internal users, 1=external users, 2=both
        $r++;

        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=operationorder,fk_leftmenu=operationorder_left_status',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>$langs->trans('LeftMenuOperationOrderStatusAdmin'),
			'mainmenu'=>'operationorder',
			'leftmenu'=>'operationorder_left_status_admin',
            'url'=>'/operationorder/admin/operationorderstatus_setup.php',
            'langs'=>'operationorder@operationorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=> '$conf->operationorder->enabled',  // Define condition to show or hide menu entry. Use '$conf->missionorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=> '$user->rights->operationorder->status->write',			                // Use 'perms'=>'$user->rights->missionorder->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0
        );				                // 0=Menu for internal users, 1=external users, 2=both
        $r++;


		// Exports
		$r=1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        // $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_permission[$r]=array(array("facture","facture","export"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $this->export_sql_order[$r] .=' ORDER BY s.nom';
		// $r++;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $langs;
		$sql = array();

		$langs->load('operationorder@operationorder');

		define('INC_FROM_DOLIBARR', true);

		require __DIR__ .'/../../script/create-maj-base.php';

		$result=$this->_load_tables('/operationorder/sql/');

		// Création des extrafields
		dol_include_once('/core/class/extrafields.class.php');

		// product
		$e = new ExtraFields($this->db);
		$res = $e->addExtraField('oorder_available_for_supplier_order', "oorder_available_for_supplier_order", 'boolean', 0, 1, 'product', 0, 0, '', '', 1, 1, 1, "oorder_available_for_supplier_order_help", "", 0, 'operationorder@operationorder');


		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
    public function remove($options = '')
    {
		$sql = array();

		return $this->_remove($sql, $options);
    }

}
