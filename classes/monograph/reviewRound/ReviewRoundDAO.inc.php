<?php

/**
 * @file ReviewRoundDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewRoundDAO
 * @ingroup monograph_reviewRound
 * @see ReviewRound
 *
 * @brief Operations for retrieving and modifying ReviewRound objects.
 */

//$Id$

import('monograph.reviewRound.ReviewRound');

class ReviewRoundDAO extends DAO {

	/**
	 * Fetch a signoff by symbolic info, building it if needed.
	 * @param $symbolic string
	 * @param $assocType int
	 * @param $assocId int
	 * @return $signoff
	 */
	function build($monographId, $reviewType, $round, $reviewRevision = null) {
		// If one exists, fetch and return.
		$reviewRound = $this->getReviewRound($monographId, $reviewType, $round);
		if ($reviewRound) return $reviewRound;

		// Otherwise, build one.
		unset($reviewRound);
		$reviewRound = $this->newDataObject();
		$reviewRound->setMonographId($monographId);
		$reviewRound->setRound($round);
		$reviewRound->setReviewType($reviewType);
		$reviewRound->setReviewRevision($reviewRevision);
		$this->insertObject($reviewRound);
		return $reviewRound;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return SignoffEntry
	 */
	function newDataObject() {
		return new ReviewRound();
	}

	/**
	 * Internal function to return an Signoff object from a row.
	 * @param $row array
	 * @return Signoff
	 */
	function _fromRow(&$row) {
		$reviewRound = $this->newDataObject();

		$reviewRound->setMonographId($row['monograph_id']);
		$reviewRound->setReviewType($row['review_type']);
		$reviewRound->setRound($row['round']);
		$reviewRound->setReviewRevision($row['review_revision']);

		return $reviewRound;
	}

	/**
	 * Insert a new Signoff.
	 * @param $signoff Signoff
	 * @return int 
	 */
	function insertObject(&$reviewRound) {
		$this->update(
				'INSERT INTO review_rounds
				(monograph_id, review_type, round, review_revision)
				VALUES
				(?, ?, ?, ?)',
				array(
					$reviewRound->getMonographid(),
					$reviewRound->getReviewType(),
					$reviewRound->getRound(),
					$reviewRound->getReviewRevision(),
				)
		);
		return $reviewRound;
	}

	/**
	 * Update an existing signoff.
	 * @param $signoff Signoff
	 * @return boolean
	 */
	function updateObject(&$reviewRound) {
		$returner = $this->update(
				'UPDATE	review_rounds
				SET	monograph_id = ?,
					review_type = ?,
					round = ?,
					review_revision = ?
				WHERE	monograph_id = ? AND
					review_type = ? AND
					round = ?',
				array(
					$reviewRound->getMonographId(),
					$reviewRound->getReviewType(),
					$reviewRound->getRound(),
					$reviewRound->getReviewRevision(),
					$reviewRound->getMonographId(),
					$reviewRound->getReviewType(),
					$reviewRound->getRound()
				)
			);
		return $returner;
	}

	/**
	 * Retrieve an array of signoffs matching the specified
	 * symbolic name and assoc info.
	 * @param $symbolic string
	 * @param $assocType int
	 * @param $assocId int
	 */
	function getReviewRound($monographId, $reviewType, $round) {
		$result =& $this->retrieve(
			'SELECT * FROM review_rounds WHERE monograph_id = ? AND review_type = ? AND round = ?',
			array($monographId, (int) $reviewType, (int) $round)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}
}

?>
