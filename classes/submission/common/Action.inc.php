<?php

/**
 * @defgroup submission
 */
 
/**
 * @file classes/submission/common/Action.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Action
 * @ingroup submission
 *
 * @brief Action class.
 */

// $Id$


/* These constants correspond to editing decision "decision codes". */
define('SUBMISSION_EDITOR_DECISION_ACCEPT', 1);
define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', 2);
define('SUBMISSION_EDITOR_DECISION_RESUBMIT', 3);
define('SUBMISSION_EDITOR_DECISION_DECLINE', 4);

/* These constants are used as search fields for the various submission lists */
define('SUBMISSION_FIELD_AUTHOR', 1);
define('SUBMISSION_FIELD_EDITOR', 2);
define('SUBMISSION_FIELD_TITLE', 3);
define('SUBMISSION_FIELD_REVIEWER', 4);
define('SUBMISSION_FIELD_COPYEDITOR', 5);
define('SUBMISSION_FIELD_LAYOUTEDITOR', 6);
define('SUBMISSION_FIELD_PROOFREADER', 7);

define('SUBMISSION_FIELD_DATE_SUBMITTED', 4);
define('SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE', 5);
define('SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE', 6);
define('SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE', 7);

class Action {

	/**
	 * Constructor.
	 */
	function Action() {

	}

	/**
	 * Actions.
	 */

	/**
	 * View metadata of a monograph.
	 * @param $monograph object
	 */
	function viewMetadata($monograph) {
		if (!HookRegistry::call('Action::viewMetadata', array(&$monograph, &$roleId))) {
			import('submission.form.MetadataForm');
			$metadataForm = new MetadataForm($monograph);
			if ($metadataForm->getCanEdit() && $metadataForm->isLocaleResubmit()) {
				$metadataForm->readInputData();
			} else {
				$metadataForm->initData();
			}
			$metadataForm->display();
		}
	}

	/**
	 * Save metadata.
	 * @param $monograph object
	 */
	function saveMetadata($monograph) {
		if (!HookRegistry::call('Action::saveMetadata', array(&$monograph))) {
			import('submission.form.MetadataForm');
			$metadataForm = new MetadataForm($monograph);
			$metadataForm->readInputData();
			$editData = false;
			$editData = $metadataForm->processEvents();

			if (!$editData && $metadataForm->validate()) {
				$metadataForm->execute();
				
				// Send a notification to associated users
				import('notification.Notification');
				$notificationUsers = $monograph->getAssociatedUserIds();
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, $userRole['role'], 'submission', $monograph->getMonographId(), null, 'metadata');
					Notification::createNotification($userRole['id'], "notification.type.metadataModified",
						$monograph->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_METADATA_MODIFIED);
				}

				// Add log entry
				$user =& Request::getUser();
				import('monograph.log.MonographLog');
				import('monograph.log.MonographEventLogEntry');
				MonographLog::logEvent($monograph->getMonographId(), MONOGRAPH_LOG_METADATA_UPDATE, MONOGRAPH_LOG_TYPE_DEFAULT, 0, 'log.editor.metadataModified', Array('editorName' => $user->getFullName()));

				return true;
			} else {
				$metadataForm->display();
				return false;
			}

		}
	}

	/**
	 * Download file.
	 * @param $monographId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadFile($monographId, $fileId, $revision = null) {
		import('file.MonographFileManager');
		$monographFileManager = new MonographFileManager($monographId);
		return $monographFileManager->downloadFile($fileId, $revision);
	}

	/**
	 * View file.
	 * @param $monographId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function viewFile($monographId, $fileId, $revision = null) {
		import('file.MonographFileManager');
		$monographFileManager = new MonographFileManager($monographId);
		return $monographFileManager->viewFile($fileId, $revision);
	}

	/**
	 *
	 * @param $type string the type of instructions (copy, layout, or proof).
	 */
	function instructions($type, $allowed = array('copy', 'layout', 'proof', 'referenceLinking')) {
		$press =& Request::getPress();
		$templateMgr =& TemplateManager::getManager();

		if (!HookRegistry::call('Action::instructions', array(&$type, &$allowed))) {
			if (!in_array($type, $allowed)) {
				return false;
			}

			switch ($type) {
				case 'copy':
					$title = 'submission.copyedit.instructions';
					$instructions = $press->getLocalizedSetting('copyeditInstructions');
					break;
				case 'layout':
					$title = 'submission.layout.instructions';
					$instructions = $press->getLocalizedSetting('layoutInstructions');
					break;
				case 'proof':
					$title = 'submission.proofread.instructions';
					$instructions = $press->getLocalizedSetting('proofInstructions');
					break;
				case 'referenceLinking':
					if (!$press->getSetting('provideRefLinkInstructions')) return false;
					$title = 'submission.layout.referenceLinking';
					$instructions = $press->getLocalizedSetting('refLinkInstructions');
					break;
				default:
					return false;
			}
		}

		$templateMgr->assign('pageTitle', $title);
		$templateMgr->assign('instructions', $instructions);
		$templateMgr->display('submission/instructions.tpl');

		return true;
	}

	/**
	 * Edit comment.
	 * @param $commentId int
	 */
	function editComment($monograph, $comment) {
		if (!HookRegistry::call('Action::editComment', array(&$monograph, &$comment))) {
			import("submission.form.comment.EditCommentForm");

			$commentForm = new EditCommentForm($monograph, $comment);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Save comment.
	 * @param $commentId int
	 */
	function saveComment($monograph, &$comment, $emailComment) {
		if (!HookRegistry::call('Action::saveComment', array(&$monograph, &$comment, &$emailComment))) {
			import("submission.form.comment.EditCommentForm");

			$commentForm = new EditCommentForm($monograph, $comment);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();
				
				// Send a notification to associated users
				import('notification.Notification');
				$notificationUsers = $monograph->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, $userRole['role'], 'submissionReview', $monograph->getMonographId(), null, 'editorDecision');
					Notification::createNotification($userRole['id'], "notification.type.submissionComment",
						$monograph->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_SUBMISSION_COMMENT);
				}

				if ($emailComment) {
					$commentForm->email($commentForm->emailHelper());
				}

			} else {
				$commentForm->display();
			}
		}
	}

	/**
	 * Delete comment.
	 * @param $commentId int
	 * @param $user object The user who owns the comment, or null to default to Request::getUser
	 */
	function deleteComment($commentId, $user = null) {
		if ($user == null) $user =& Request::getUser();

		$monographCommentDao =& DAORegistry::getDAO('MonographCommentDAO');
		$comment =& $monographCommentDao->getMonographCommentById($commentId);

		if ($comment->getAuthorId() == $user->getId()) {
			if (!HookRegistry::call('Action::deleteComment', array(&$comment))) {
				$monographCommentDao->deleteMonographComment($comment);
			}
		}
	}

	/**
	 * Move the submission along in the context of the workflow.
	 * @param $monographId int
	 * @param $pressId int
	 */
	function &endSignoffProcess($monographId) {

		$workflowDao =& DAORegistry::getDAO('WorkflowDAO');

		$signoffProcess =& $workflowDao->proceed($monographId);

		return $signoffProcess;
	}
}

?>
