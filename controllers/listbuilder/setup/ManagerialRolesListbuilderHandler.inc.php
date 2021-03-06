<?php

/**
 * @file controllers/listbuilder/setup/ManagerialRolesListbuilderHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerialRolesListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for adding new managerial roles
 */

import('controllers.listbuilder.setup.SetupListbuilderHandler');

class ManagerialRolesListbuilderHandler extends SetupListbuilderHandler {
	/** @var boolean internal state variable, true if row handler has been instantiated */
	var $_rowInstantiated = false;

	/**
	 * Constructor
	 */
	function ManagerialRolesListbuilderHandler() {
		parent::SetupListbuilderHandler();
	}


	/* Load the list from an external source into the grid structure */
	function loadList(&$request) {
		$flexibleRoleDao =& DAORegistry::getDAO('FlexibleRoleDAO');
		$press =& $request->getPress();

		// Get items to populate listBuilder current item list
		$availableRoles = $flexibleRoleDao->getEnabledByPressId($press->getId());

		$items = array();
		foreach ($availableRoles as $availableRole) {
			if ($availableRole->getType() == FLEXIBLE_ROLE_CLASS_MANAGERIAL) {
				$id = $availableRole->getId();
				$items[$id] = array('item' => $availableRole->getLocalizedName(), 'attribute' => $availableRole->getLocalizedDesignation());
			}
		}
		$this->setData($items);
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);
		// Basic configuration
		$this->setTitle('manager.setup.managerialRole');
		$this->setSourceTitle('manager.setup.roleName');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_TEXT); // Free text input
		$this->setListTitle('manager.setup.currentRoles');
		$this->setAttributeNames(array('manager.setup.roleAbbrev'));

		$this->loadList($request);

		$this->addColumn(new GridColumn('item', 'manager.setup.roleName'));
		$this->addColumn(new GridColumn('attribute', 'manager.setup.roleAbbrev'));
	}

	//
	// Public AJAX-accessible functions
	//

	/*
	 * Handle adding an item to the list
	 */
	function addItem(&$args, &$request) {
		$this->setupTemplate();
		$flexibleRoleDao =& DAORegistry::getDAO('FlexibleRoleDAO');
		$press =& $request->getPress();

		$nameIndex = 'sourceTitle-' . $this->getId();
		$roleName = $args[$nameIndex];
		$abbrevIndex = 'attribute-1-' . $this->getId();
		$roleAbbrev = $args[$abbrevIndex];

		if(empty($roleName) || empty($roleAbbrev)) {
			$json = new JSON('false', Locale::translate('common.listbuilder.completeForm'));
			echo $json->getString();
		} else {
			// Make sure the role name or abbreviation doesn't already exist
			$availableRoles = $flexibleRoleDao->getEnabledByPressId($press->getId());
			foreach ($availableRoles as $availableRole) {
				if ($availableRole->getType() == FLEXIBLE_ROLE_CLASS_MANAGERIAL && ($roleName == $availableRole->getLocalizedName() || $roleAbbrev == $availableRole->getLocalizedDesignation())) {
					$json = new JSON('false', Locale::translate('common.listbuilder.itemExists'));
					echo $json->getString();
					return false;
				}
			}

			$locale = Locale::getLocale();

			$flexibleRole = $flexibleRoleDao->newDataObject();

			$flexibleRole->setPressId($press->getId());
			$flexibleRole->setName($roleName, $locale);
			$flexibleRole->setDesignation($roleAbbrev, $locale);
			$flexibleRole->setType(FLEXIBLE_ROLE_CLASS_MANAGERIAL);
			$flexibleRole->setEnabled(true);

			$flexibleRoleId = $flexibleRoleDao->insertObject($flexibleRole);

			// Return JSON with formatted HTML to insert into list
			$row =& $this->getRowInstance();
			$row->setGridId($this->getId());
			$row->setId($flexibleRoleId);
			$rowData = array('item' => $roleName, 'attribute' => $roleAbbrev);
			$row->setData($rowData);
			$row->initialize($request);

			$json = new JSON('true', $this->_renderRowInternally($request, $row));
			echo $json->getString();
		}
	}

	/*
	 * Handle deleting items from the list
	 */
	function deleteItems(&$args, &$request) {
		$flexibleRoleDao =& DAORegistry::getDAO('FlexibleRoleDAO');

		foreach($args as $flexibleRoleId) {
			$flexibleRoleDao->deleteById($flexibleRoleId);
			$itemIds[] = $flexibleRoleId;
		}

		$json = new JSON('true');
		echo $json->getString();
	}
}
?>
