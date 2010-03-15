<?php

/**
 * @file controllers/grid/submit/monographComponent/MonographComponentGridRow.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographComponentGridRow
 * @ingroup controllers_grid_submit_monographComponent
 *
 * @brief Handle monograph component grid row requests.
 */

import('controllers.grid.GridRow');

class MonographComponentGridRow extends GridRow {

	/**
	 * Constructor
	 */
	function MonographComponentGridRow() {
		parent::GridRow();
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid row
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// add Grid Row Actions
		$this->setTemplate('controllers/grid/gridRowWithActions.tpl');

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			// Actions
			$router =& $request->getRouter();
			$actionArgs = array(
				'gridId' => $this->getGridId(),
				'monographComponentId' => $rowId,
				'monographId' => $request->getUserVar('monographId')
			);

			$this->addAction(
				new GridAction(
					'editMonographComponent',
					GRID_ACTION_MODE_MODAL,
					GRID_ACTION_TYPE_REPLACE,
					$router->url($request, null, null, 'editMonographComponent', null, $actionArgs),
					'grid.action.edit',
					'edit'
				)
			);

			$this->addAction(
				new GridAction(
					'deleteMonographComponent',
					GRID_ACTION_MODE_CONFIRM,
					GRID_ACTION_TYPE_REMOVE,
					$router->url($request, null, null, 'deleteMonographComponent', null, $actionArgs),
					'grid.action.delete',
					'delete'
				)
			);
		}
	}
}