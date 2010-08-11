<?php

	/**
	 * InvalidConstructorException
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace BlueShift;

	use Exception;

	/**
	 * Exception that is raised when trying to register or resolve types
	 * that have an invalid constructor
	 *
	 * @package BlueShift
	 */
	class InvalidConstructorException extends Exception {}

?>
