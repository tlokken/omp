<?php

/**
 * @file controllers/grid/contributor/form/ContributorForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContributorForm
 * @ingroup controllers_grid_contributor_form
 *
 * @brief Form for adding/edditing a contributor
 * stores/retrieves from an associative array
 */

import('form.Form');

class ContributorForm extends Form {
	/** the id for the contributor being edited **/
	var $contributorId;

	/**
	 * Constructor.
	 */
	function ContributorForm($contributorId = null) {
		$this->contributorId = $contributorId;
		parent::Form('controllers/grid/contributor/form/contributorForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'institution', 'required', 'manager.setup.form.contributors.institutionRequired'));
		$this->addCheck(new FormValidator($this, 'url', 'required', 'manager.emails.form.contributors.urlRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData(&$args, &$request) {
		$press =& Request::getPress();

		$contributors = $press->getSetting('contributors');
		if ( $this->contributorId && isset($contributors[$this->contributorId]) ) {
			$this->_data = array(
				'contributorId' => $this->contributorId,
				'institution' => $contributors[$this->contributorId]['institution'],
				'url' => $contributors[$this->contributorId]['url']
				);
		} else {
			$this->_data = array(
				'institution' => '',
				'url' => ''
			);
		}
		
		// grid related data
		$this->_data['gridId'] = $args['gridId'];
		$this->_data['rowId'] = isset($args['rowId']) ? $args['rowId'] : null;	
	}

	/**
	 * Display
	 */
	function display() {
		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_MANAGER));
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('contributorId', 'institution', 'url'));
		$this->readUserVars(array('gridId', 'rowId'));
	}

	/**
	 * Save email template.
	 */
	function execute() {
		$press =& Request::getPress();
		$contributors = $press->getSetting('contributors');
		//FIXME: a bit of kludge to get unique contributor id's
		$this->contributorId = ($this->contributorId?$this->contributorId:(max(array_keys($contributors)) + 1));
		$contributors[$this->contributorId] = array('institution' => $this->getData('institution'),
							'url' => $this->getData('url'));

		$press->updateSetting('contributors', $contributors, 'object', false);
		return true;
	}
}

?>
