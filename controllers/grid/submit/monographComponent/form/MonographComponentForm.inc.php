<?php
 
/**
 * @file controllers/grid/submit/monographComponent/MonographComponentForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographComponentForm
 * @ingroup controllers_grid_submit_monographComponent_form
 *
 * @brief Form for editing and creating the components of a monograph.
 */


import('form.Form');

class MonographComponentForm extends Form {

	/** @var MonographComponent */
	var $_monographComponent;

	/** @var int */
	var $_monograph;

	/**
	 * Constructor.
	 */
	function MonographComponentForm($monographComponent, &$monograph) {
		parent::Form('controllers/grid/monographComponent/form/monographComponentForm.tpl');

		$this->_monograph =& $monograph;
		$this->_monographComponent =& $monographComponent;
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get the monograph component
	 * @return MonographComponent
	 */
	function &getMonographComponent() {
		return $this->_monographComponent;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$monograph =& $this->_monograph;
		$templateMgr->assign('monographId', $monograph->getId());
		$templateMgr->assign('workType', $monograph->getWorkType());

		$authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->_data['monographContributors'] = $authorDao->getAuthorsByMonographId($monograph->getId());

		$monographComponentDao =& DAORegistry::getDAO('MonographComponentDAO');
		$monographComponent =& $this->getMonographComponent();
		$this->_data['contributorIds'] = $monographComponentDao->getContributorIds($monographComponent->getId());

		parent::display();
	}

	/**
	 * Initialize form data.
	 */
	function initData(&$args, &$request) {
		$this->_data['monographComponent'] =& $this->getMonographComponent();

		// grid related data
		$this->_data['gridId'] = $args['gridId'];
		$this->_data['monographComponentId'] = isset($args['monographComponentId']) ? $args['monographComponentId'] : null;
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('contributors', 'title', 'primaryContact', 'gridId', 'monographComponentId'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$monographComponentDao =& DAORegistry::getDAO('MonographComponentDAO');
		$authorDao =& DAORegistry::getDAO('AuthorDAO');

		$monographComponent =& $this->_monographComponent;
		$monographComponentExists = false;
		$monograph =& $this->_monograph;

		if ($monographComponent->getId() !== null) {
			$monographComponentExists = true;
		} else {
			$monographComponent =& $monographComponentDao->newDataObject();
		}

		$monographComponent->setPrimaryContact($this->getData('primaryContact'));
		$monographComponent->setMonographId($monograph->getId());
		$monographComponent->setSequence(REALLY_BIG_NUMBER);
		$monographComponent->setTitle($this->getData('title'), null);

		$contributors = array();
		foreach ($this->getData('contributors') as $contributorId) {
			$contributors[] = $authorDao->getAuthor($contributorId);
		}
		$monographComponent->setAuthors($contributors);

		if ($monographComponentExists) {
			$monographComponentDao->updateObject($monographComponent);
		} else {
			$monographComponentDao->insertObject($monographComponent);
		}

		$this->_monographComponent =& $monographComponent;

		return $monographComponent->getId();
	}

}
