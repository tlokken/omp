<?php

/**
 * @file controllers/grid/series/SeriesGridHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SeriesGridHandler
 * @ingroup controllers_grid_series
 *
 * @brief Handle series grid requests.
 */

import('controllers.grid.setup.SetupGridHandler');
import('controllers.grid.setup.series.SeriesGridRow');

class SeriesGridHandler extends SetupGridHandler {
	/**
	 * Constructor
	 */
	function SeriesGridHandler() {
		parent::SetupGridHandler();
	}

	//
	// Getters/Setters
	//
	/**
	 * @see lib/pkp/classes/handler/PKPHandler#getRemoteOperations()
	 */
	function getRemoteOperations() {
		return array_merge(parent::getRemoteOperations(), array('addSeries', 'editSeries', 'updateSeries', 'deleteSeries'));
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
		$press =& $request->getPress();
		$router =& $request->getRouter();

		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_MANAGER, LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_APPLICATION_COMMON));

		// Basic grid configuration
		$this->setTitle('series.series');

		// Elements to be displayed in the grid
		$seriesDao =& DAORegistry::getDAO('SeriesDAO');
		$divisionDao =& DAORegistry::getDAO('DivisionDAO');
		$series = $seriesDao->getByPressId($press->getId());

		$seriesArray = array();
		while ($seriesItem =& $series->next()) {
			$division = $divisionDao->getById($seriesItem->getDivisionId(), $press->getId());
			if (isset($division)) {
				$divisionTitle = $division->getLocalizedTitle();
			} else {
				$divisionTitle = Locale::translate('common.none');
			}

			$seriesEditorsDao =& DAORegistry::getDAO('SeriesEditorsDAO');
			$assignedSeriesEditors =& $seriesEditorsDao->getEditorsBySeriesId($seriesItem->getId(), $press->getId());
			if(empty($assignedSeriesEditors)) {
				$editorsString = Locale::translate('common.none');
			} else {
				$editors = array();
				foreach ($assignedSeriesEditors as $seriesEditor) {
					$user = $seriesEditor['user'];
					$editors[] = $user->getLastName();
				}
				$editorsString = implode(',', $editors);
			}

			$seriesId = $seriesItem->getId();
			$seriesArray[$seriesId] = array('title' => $seriesItem->getLocalizedTitle(),
							'division' => $divisionTitle,
							'editors' => $editorsString,
							'affiliation' => $seriesItem->getLocalizedAffiliation());
			unset($seriesItem);
			unset($editorsString);
		}

		$this->setData($seriesArray);

		// Add grid-level actions
		$this->addAction(
			new GridAction(
				'addSeries',
				GRID_ACTION_MODE_MODAL,
				GRID_ACTION_TYPE_APPEND,
				$router->url($request, null, null, 'addSeries', null, array('gridId' => $this->getId())),
				'grid.action.addItem'
			),
			GRID_ACTION_POSITION_ABOVE
		);

		// Columns
		$emptyActions = array();
		// Basic grid row configuration
		$this->addColumn(new GridColumn('title', 'common.title', $emptyActions, 'controllers/grid/gridCellInSpan.tpl'));
		$this->addColumn(new GridColumn('division', 'manager.setup.division'));
		$this->addColumn(new GridColumn('editors', 'user.role.editors'));
		$this->addColumn(new GridColumn('affiliation', 'user.affiliation'));
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * Get the row handler - override the default row handler
	 * @return SeriesGridRow
	 */
	function &getRowInstance() {
		$row = new SeriesGridRow();
		return $row;
	}

	//
	// Public Series Grid Actions
	//
	/**
	 * An action to add a new series
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addSeries(&$args, &$request) {
		// Calling editSeries with an empty row id will add
		// a new series.
		$this->editSeries($args, $request);
	}

	/**
	 * An action to edit a series
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editSeries(&$args, &$request) {
		$seriesId = isset($args['rowId']) ? $args['rowId'] : null;
		
		//FIXME: add validation here?
		$this->setupTemplate();

		import('controllers.grid.setup.series.form.SeriesForm');
		$seriesForm = new SeriesForm($seriesId);

		if ($seriesForm->isLocaleResubmit()) {
			$seriesForm->readInputData();
		} else {
			$seriesForm->initData($args, $request);
		}
		$seriesForm->display();
	}

	/**
	 * Update a series
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function updateSeries(&$args, &$request) {
		$seriesId = Request::getUserVar('rowId');
		
		//FIXME: add validation here?
		// -> seriesId must be present and valid
		// -> htmlId must be present and valid
		$press =& $request->getPress();

		import('controllers.grid.setup.series.form.SeriesForm');
		$seriesForm = new SeriesForm($seriesId);
		$seriesForm->readInputData();

		$router =& $request->getRouter();
		$context =& $router->getContext($request);

		if ($seriesForm->validate()) {
			$seriesForm->execute($args, $request);

			$divisionDao =& DAORegistry::getDAO('DivisionDAO');
			$division = $divisionDao->getById($seriesForm->getData('division'), $press->getId());
			if (isset($division)) {
				$divisionTitle = $division->getLocalizedTitle();
			} else {
				$divisionTitle = Locale::translate('common.none');
			}

			$seriesEditorsDao =& DAORegistry::getDAO('SeriesEditorsDAO');
			$assignedSeriesEditors =& $seriesEditorsDao->getEditorsBySeriesId($seriesId, $press->getId());
			if(isset($assignedSeriesEditors)) {
				$editorsString = Locale::translate('common.none');
			} else {
				foreach ($assignedSeriesEditors as $seriesEditor) {
					$user = $seriesEditor['user'];
					$editorsString .= $user->getInitials() . '  ';
				}
			}

			// prepare the grid row data
			$row =& $this->getRowInstance();
			$row->setGridId($this->getId());
			$rowData = array('title' => $seriesForm->getData('title'),
							'division' => $divisionTitle,
							'editors' => $editorsString,
							'affiliation' => $seriesForm->getData('affiliation'));
			$row->setId($seriesForm->seriesId);
			$row->setData($rowData);
			$row->initialize($request);

			$json = new JSON('true', $this->_renderRowInternally($request, $row));
		} else {
			$json = new JSON('false');
		}

		return $json->getString();
	}

	/**
	 * Delete a series
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function deleteSeries(&$args, &$request) {
		// FIXME: add validation here?

		$router =& $request->getRouter();
		$press =& $router->getContext($request);

		$seriesDao =& DAORegistry::getDAO('SeriesDAO');
		$series = $seriesDao->getById($this->getId(), $press->getId());

		if (isset($series)) {
			$seriesDao->deleteObject($series);
			$json = new JSON('true');
		} else {
			$json = new JSON('false', Locale::translate('manager.setup.errorDeletingItem'));
		}
		echo $json->getString();
	}

}