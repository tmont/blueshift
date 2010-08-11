<?php

	/**
	 * Bootstrapper for Blue Shift
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	spl_autoload_register(function($className) {
		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, trim($className, '\\')) . '.php';
		if (is_file($file)) {
			require_once $file;
		}
	});

	require_once dirname(__DIR__) . '/Phroxy/bootstrap.php';

?>