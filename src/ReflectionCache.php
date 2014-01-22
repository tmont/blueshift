<?php

	/**
	 * ReflectionCache
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace Tmont\BlueShift;
	
	use ReflectionClass;

	/**
	 * Cache for reflection-related stuff, since reflection is used
	 * constantly and this will help mitigate its potential slowness
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */
	final class ReflectionCache {

		private static $classes = array();
		private static $constructors = array();
	
		//@codeCoverageIgnoreStart
		private function __construct() {}
		//@codeCoverageIgnoreEnd
		
		/**
		 * Gets or creates a ReflectionClass
		 *
		 * @param  string $type
		 * @return ReflectionClass
		 */
		public static function getClass($type) {
			if (!isset(self::$classes[$type])) {
				self::$classes[$type] = new ReflectionClass($type);
			}
			
			return self::$classes[$type];
		}
		
		/**
		 * Gets or creates a ReflectionMethod for the specified type's constructor
		 *
		 * @param  string $type
		 * @return \ReflectionMethod|null Null if the type has no constructor
		 */
		public static function getConstructor($type) {
			if (!array_key_exists($type, self::$constructors)) {
				//this value can be null for types that don't have constructors
				self::$constructors[$type] = self::getClass($type)->getConstructor();
			}
			
			return self::$constructors[$type];
		}
		
	}

?>
