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
 * \file    class/actions_operationorder.class.php
 * \ingroup operationorder
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsOperationOrder
 */
class ActionsOperationOrder
{
    /**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
    public $db;

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
     * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter
		$resprints = '';
		$results = array();
		$replace = 0;
		$errors = array();


		if (in_array('ordersuppliercard', explode(':', $parameters['context'])))
		{

			/*
			 * Ajout du element/element entre operation order et commande fourn
			 * Voir la partie form post dans formObjectOptions
			 */
			if($action == "add"){
				include_once __DIR__ . '/operationorder.class.php';
				$operationOrder = new OperationOrder($object->db);
				// origin et originid n'est pas géré en dehors de certains elements, il faut donc le gérer à part pour opération order
				$origin = GETPOST('operation_order_origin', 'alpha');
				$originid = GETPOST('operation_order_originid', 'int'); // For backward compatibility

				// Add form element to bypass origin and origin id from operation order
				if (!empty($origin) && !empty($originid) && $origin == $operationOrder->element) {
					// if operation order exist set it for trigger
					if($operationOrder->fetch($originid)>0){
						$object->linked_objects[$operationOrder->element] = $originid;
					}
				}
			}
		}


		// retours
		if (! $error)
		{
			$this->results = $results;
			$this->resprints = $resprints;

			return $replace; // 0 or return 1 to replace standard code
		}
		else
		{
			array_merge($this->errors, $errors);
			return -1;
		}
	}


	/**
	 * Overloading the formObjectOptions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter
		$resprints = '';
		$results = array();
		$replace = 0;
		$errors = array();

		if (in_array('ordersuppliercard', explode(':', $parameters['context'])))
		{
			/*
			 * Ajout du element/element entre operation order et commande fourn
			 * Voir la partie ajout dans doActions
			 */

			$resprints .=  "\n".'<!-- BEGIN OperationOrder formObjectOptions -->'."\n";
			// rend possible de passer une commande fournisseur depuis un OR. Cette commande fournisseur est lié à l’OR

			include_once __DIR__ . '/operationorder.class.php';
			$operationOrder = new OperationOrder($object->db);
			// origin et originid n'est pas géré en dehors de certains elements, il faut donc le gérer à part pour opération order
			$origin = GETPOST('operation_order_origin', 'alpha');
			$originid = GETPOST('operation_order_originid', 'int');

			// Add form element to bypass origin and origin id from operation order
			if (!empty($origin) && !empty($originid)) {
				if ($origin == $operationOrder->element) {
					$resprints .= '<input type="hidden" name="operation_order_origin" value="'.$operationOrder->element.'" >'."\n";
					$resprints .= '<input type="hidden" name="operation_order_originid" value="'.$originid.'" >'."\n";
				}
			}

			$resprints .=  '<!-- END OperationOrder formObjectOptions -->'."\n";
		}

		if (! $error)
		{
			$this->results = $results;
			$this->resprints = $resprints;

			return $replace; // 0 or return 1 to replace standard code
		}
		else
		{
			array_merge($this->errors, $errors);
			return -1;
		}
	}

    public function loadvirtualstock($parameters, &$object, &$action, $hookmanager) {
        //On écrase le stock virtuel
        if (in_array('productdao', explode(':', $parameters['context']))) {
            dol_include_once('/operationorder/class/operationorder.class.php');
            $ooStatus = new OperationOrderDet($this->db);
            $ooStatus->product = $object;
            $object->stock_theorique -= $ooStatus->loadOperationOrderQty();
        }
    }

    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
        if (in_array('stockproductcard', explode(':', $parameters['context']))) {
            global $langs, $conf, $db;
            dol_include_once('/operationorder/class/operationorder.class.php');
            $langs->load('operationorder@operationorder');
            $ooStatus = new OperationOrderDet($this->db);
            $ooStatus->product = $object;
            ?>
            <script type="text/javascript">
                $(document).ready(function(){
                    let tdVirtual = $('td:contains("<?php echo $langs->trans('VirtualStock'); ?>")').next();
                    let content = tdVirtual.find('.classfortooltip').attr('title') + "<br/>" + "<?php echo $langs->trans('ProductQtyInOperationOrder') .' : '.$ooStatus->loadOperationOrderQty(); ?>";
                    tdVirtual.find('.classfortooltip').attr('title', content);
                });
            </script>
            <?php

        }
    }

	/**
	 * @param bool   $parameters
	 * @param        $object
	 * @param string $action
	 * @return int
	 */
	public function moreHtmlRef($parameters=false, &$object, &$action='')
	{
		global $conf;
		global $mc;

		// Add entity badges on Operation order
		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)
			&& ! empty($conf->global->MULTICOMPANY_OPERATIONORDER_SHARING_ENABLED)
			&& $object->element == 'operationorder'
			&& ! empty($conf->operationorder->enabled)
			&& ! empty($mc->sharings['operationorder'])
			&& $object->entity!=$conf->entity)
		{
				dol_include_once('/multicompany/class/actions_multicompany.class.php');
				$actMulticomp= new ActionsMulticompany($this->db);
				$actMulticomp->getInfo($object->entity);

				$this->resprints = "\n" . '<!-- BEGIN operationOrder moreHtmlRef -->' . "\n";

				$this->resprints .= '<div class="refidno modify-entity multicompany-entity-container">';
				$this->resprints .= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">' . $actMulticomp->label . '</span>';
				$this->resprints .= '</div>';

				$this->resprints .= "\n" . '<!-- END operationOrder moreHtmlRef -->' . "\n";
		}
		//Add entities badges on wharehouses
		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)
			&& ! empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED)
			&& $object->element == 'stock'
			&& ! empty($conf->stock->enabled)
			&& ! empty($mc->sharings['stock'])
			&& $object->entity!=$conf->entity)
		{

			dol_include_once('/multicompany/class/actions_multicompany.class.php');
			$actMulticomp= new ActionsMulticompany($this->db);
			$actMulticomp->getInfo($object->entity);

			$this->resprints = "\n" . '<!-- BEGIN Stock moreHtmlRef -->' . "\n";

			$this->resprints .= '<div class="refidno modify-entity multicompany-entity-container">';
			$this->resprints .= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">' . $actMulticomp->label . '</span>';
			$this->resprints .= '</div>';

			$this->resprints .= "\n" . '<!-- END Stock moreHtmlRef -->' . "\n";
		}
		return 0;
		return 0;
	}
}
