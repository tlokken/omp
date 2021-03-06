<?php

/**
 * @file pages/manager/ManagerHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for press management functions. 
 */

// $Id$


import('handler.Handler');

class ManagerHandler extends Handler {
	/**
	 * Constructor
	 */	
	function ManagerHandler() {
		parent::Handler();
		
		$this->addCheck(new HandlerValidatorPress($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_PRESS_MANAGER)));
	}
	
	/**
	 * Display press management index page.
	 */
	function index() {
		$this->validate();
		$this->setupTemplate();

		$press =& Request::getPress();
		$pressSettingsDao =& DAORegistry::getDAO('PressSettingsDAO');
		$announcementsEnabled = $pressSettingsDao->getSetting($press->getId(), 'enableAnnouncements');
		$customSignoffInternal = $pressSettingsDao->getSetting($press->getId(), 'useCustomInternalReviewSignoff');
		$customSignoffExternal = $pressSettingsDao->getSetting($press->getId(), 'useCustomExternalReviewSignoff'); 

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('customSingoffEnabled', $customSignoffInternal || $customSignoffExternal );

		$flexibleRoleDao =& DAORegistry::getDAO('FlexibleRoleDAO');
		$roles =& $flexibleRoleDao->getEnabledByPressId($press->getId());
		$templateMgr->assign_by_ref('roles', $roles);

		$session =& Request::getSession();
		$session->unsetSessionVar('enrolmentReferrer');

		$templateMgr->assign('announcementsEnabled', $announcementsEnabled);
		$templateMgr->assign('helpTopicId','press.index');
		$templateMgr->display('manager/index.tpl');
	}

	/**
	 * Send an email to a user or group of users.
	 */
	function email($args) {
		parent::validate();

		$this->setupTemplate(true);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'press.users.emailUsers');

		$userDao =& DAORegistry::getDAO('UserDAO');

		$site =& Request::getSite();
		$press =& Request::getPress();
		$user =& Request::getUser();

		import('mail.MailTemplate');
		$email = new MailTemplate(Request::getUserVar('template'), Request::getUserVar('locale'));

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();
			Request::redirect(null, Request::getRequestedPage());
		} else {
			$email->assignParams(); // FIXME Forces default parameters to be assigned (should do this automatically in MailTemplate?)
			if (!Request::getUserVar('continued')) {
				if (($groupId = Request::getUserVar('toGroup')) != '') {
					// Special case for emailing entire groups:
					// Check for a group ID and add recipients.
					$groupDao =& DAORegistry::getDAO('GroupDAO');
					$group =& $groupDao->getGroup($groupId);
					if ($group && $group->getPressId() == $press->getId()) {
						$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
						$memberships =& $groupMembershipDao->getMemberships($group->getId());
						$memberships =& $memberships->toArray();
						foreach ($memberships as $membership) {
							$user =& $membership->getUser();
							$email->addRecipient($user->getEmail(), $user->getFullName());
						}
					}
				}
				if (count($email->getRecipients())==0) $email->addRecipient($user->getEmail(), $user->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, 'email'), array(), 'manager/people/email.tpl');
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OMP_MANAGER));

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'manager'), 'manager.pressManagement'))
				: array(array(Request::url(null, 'user'), 'navigation.user'))
		);
	}
}

?>
