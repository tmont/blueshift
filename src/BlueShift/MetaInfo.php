<?php

	/**
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright © 2010 Tommy Montgomery
	 * @link      http://blueshiftcontainer.com/
	 */

	namespace BlueShift;

	/**
	 * Provides meta information about BlueShift
	 *
	 * @package BlueShift
	 */
	final class MetaInfo {

		//@codeCoverageIgnoreStart
		private function __construct() {}
		//@codeCoverageIgnoreEnd

		/**
		 * The product version
		 *
		 * @var string
		 */
		const VERSION    = '1.0';

		/**
		 * The product author
		 *
		 * @var string
		 */
		const AUTHOR     = 'Tommy Montgomery';

		/**
		 * The full product name
		 *
		 * @var string
		 */
		const NAME       = 'Blue Shift';

		/**
		 * The build date (Y-m-d H:i:s P)
		 *
		 * @var string
		 */
		const BUILD_DATE = '2010-03-30 19:09:24 -07:00';

	}

?>