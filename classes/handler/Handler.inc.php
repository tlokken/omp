<?php

/**
 * @file classes/core/Handler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Handler
 * @ingroup core
 *
 * @brief Base request handler application class
 */


import('handler.PKPHandler');
import('handler.validation.HandlerValidatorPress');
import('handler.validation.HandlerValidatorRoles');
import('handler.validation.HandlerValidatorCustomRole');

class Handler extends PKPHandler{
	function Handler() {
		parent::PKPHandler();
	}
}

?>
