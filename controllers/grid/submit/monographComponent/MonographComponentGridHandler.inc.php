<?php

/**
 * @file controllers/grid/submit/monographComponent/MonographComponentGridHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographComponentGridHandler
 * @ingroup controllers_grid_submit_monographComponent
 *
 * @brief Handle Monograph Component grid requests.
 */

import('controllers.grid.GridHandler');
import('controllers.grid.DataObjectGridCellProvider');
import('controllers.grid.submit.monographComponent.MonographComponentGridRow');

class MonographComponentGridHandler extends GridHandler {

	/** @var Monograph */
	var $_monograph;

	/**
	 * Constructor
	 */
	function MonographComponentGridHandler() {
		parent::GridHandler();
	}

	/**
	 * @see lib/pkp/classes/handler/PKPHandler#getRemoteOperations()
	 */
	function getRemoteOperations() {
		return array_merge(parent::getRemoteOperations(), array('addMonographComponent', 'editMonographComponent', 'updateMonographComponent', 'deleteMonographComponent'));
	}

	/**
	 * Get the monograph associated with this monograph component grid.
	 * @return Monograph
	 */
	function &getMonograph() {
		return $this->_monograph;
	}

	//
	// Overridden methods from PKPHandler
	//

	/**
	 * Make sure the monograph exists.
	 * @param $requiredContexts array
	 * @param $request PKPRequest
	 * @return boolean
	 */
	function validate($requiredContexts, $request) {
		// Retrieve and validate the monograph id
		$monographId =& $request->getUserVar('monographId');
		if (!is_numeric($monographId)) return false;

		// Retrieve the monograph associated with this citation grid
		$monographDAO =& DAORegistry::getDAO('MonographDAO');
		$monograph =& $monographDAO->getMonograph($monographId);

		// Monograph and editor validation
		if (!is_a($monograph, 'Monograph')) return false;

		// Validation successful
		$this->_monograph =& $monograph;
		return true;
	}

	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);

		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_MANAGER, LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APPLICATION_COMMON));

		// Basic grid configuration
		$this->setTitle('grid.monographComponent.title');

		// Get the monograph id
		$monograph =& $this->getMonograph();
		assert(is_a($monograph, 'Monograph'));
		$monographId = $monograph->getId();

		// Elements to be displayed in the grid
		$monographComponentDao =& DAORegistry::getDAO('MonographComponentDAO');
		$monographComponents =& $monographComponentDao->getMonographComponents($monographId);
		$this->setData($monographComponents);

		// Add grid-level actions
		$router =& $request->getRouter();
		$actionArgs = array('gridId' => $this->getId(), 'monographId' => $monographId);
		$this->addAction(
			new GridAction(
				'addMonographComponent',
				GRID_ACTION_MODE_MODAL,
				GRID_ACTION_TYPE_APPEND,
				$router->url($request, null, null, 'addMonographComponent', null, $actionArgs),
				'grid.action.addItem'
			),
			GRID_ACTION_POSITION_ABOVE
		);

		// Columns
		$emptyActions = array();
		$cellProvider = new DataObjectGridCellProvider();
		$cellProvider->setLocale(Locale::getLocale());

		// Basic grid row configuration
		$this->addColumn(new GridColumn('title', 'grid.monographComponent.componentTitle', $emptyActions, 'controllers/grid/gridCellInSpan.tpl', $cellProvider));
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * Get the row handler - override the default row handler
	 * @return MonographComponentGridRow
	 */
	function &getRowInstance() {
		$row = new MonographComponentGridRow();
		return $row;
	}

	//
	// Public Monograph Component Grid Actions
	//
	/**
	 * An action to manually add a new monograph component
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addMonographComponent(&$args, &$request) {
		// Calling editMonographComponent() with an empty monographComponentId will add
		// a new monograph component.
		$this->editMonographComponent($args, $request);
	}

	/**
	 * Edit a monograph component
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editMonographComponent(&$args, &$request) {
		// Identify the monograph component to be updated
		$monographComponent =& $this->_getMonographComponentFromArgs($args, true);

		$monograph =& $this->getMonograph();

		// Form handling
		import('controllers.grid.submit.monographComponent.form.MonographComponentForm');
		$monographComponentForm = new MonographComponentForm($monographComponent, $monograph);

		if ($monographComponentForm->isLocaleResubmit()) {
			$monographComponentForm->readInputData();
		} else {
			$monographComponentForm->initData($args, $request);
		}
		$monographComponentForm->display();
	}

	/**
	 * Edit a monograph component
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateMonographComponent(&$args, &$request) {
		// Identify the monograph component to be updated
		$monographComponent =& $this->_getMonographComponentFromArgs($args, true);

		$monograph =& $this->getMonograph();

		// Form handling
		import('controllers.grid.submit.monographComponent.form.MonographComponentForm');
		$monographComponentForm = new MonographComponentForm($monographComponent, $monograph);
		$monographComponentForm->readInputData();
		if ($monographComponentForm->validate()) {
			$monographComponentForm->execute();

			$monographComponent =& $monographComponentForm->getMonographComponent();

			$row =& $this->getRowInstance();
			$row->setGridId($this->getId());
			$row->setData($monographComponent);
			$row->setId($monographComponent->getId());
			$row->initialize($request);

			$json = new JSON('true', $this->_renderRowInternally($request, $row));
		} else {
			$json = new JSON('false', Locale::translate('error'));
		}
		return $json->getString();
	}

	/**
	 * Delete a monograph component
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function deleteMonographComponent(&$args, &$request) {
		// Identify the submissionContributor to be deleted
		$monographComponent =& $this->_getMonographComponentFromArgs($args);

		$monographComponentDAO = DAORegistry::getDAO('MonographComponentDAO');
		$result = $monographComponentDAO->deleteObject($monographComponent);

		if ($result) {
			$json = new JSON('true');
		} else {
			$json = new JSON('false', Locale::translate('error'));
		}
		return $json->getString();
	}

	//
	// Private helper function
	//
	/**
	 * This will retrieve a MonographComponent object from the
	 * grids data source based on the request arguments.
	 * @param $args array
	 * @param $createIfMissing boolean
	 * @return MonographComponent
	 */
	function &_getMonographComponentFromArgs(&$args, $createIfMissing = false) {

		// Identify the monograph component id and retrieve the
		// corresponding element from the grid's data source.
		if (!isset($args['monographComponentId'])) {
			if ($createIfMissing) {
				$monographComponentDao =& DAORegistry::getDAO('MonographComponentDAO');
				$monographComponent =& $monographComponentDao->newDataObject();
			} else {
				fatalError('Missing monograph component id!');
			}
		} else {
			$monographComponent =& $this->getRowDataElement($args['monographComponentId']);
			if (is_null($monographComponent)) fatalError('Invalid monograph component id!');
		}

		return $monographComponent;
	}
}