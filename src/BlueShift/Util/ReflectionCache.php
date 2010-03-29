<?php

	namespace BlueShift\Util;
	
	use ReflectionClass;

	final class ReflectionCache {

		private static $classes = array();
		private static $constructors = array();
	
		//@codeCoverageIgnoreStart
		private function __construct() {}
		//@codeCoverageIgnoreEnd
		
		public static function getClass($type) {
			if (!isset(self::$classes[$type])) {
				self::$classes[$type] = new ReflectionClass($type);
			}
			
			return self::$classes[$type];
		}
		
		public static function getConstructor($type) {
			if (!array_key_exists($type, self::$constructors)) {
				self::$constructors[$type] = self::getClass($type)->getConstructor();
			}
			
			return self::$constructors[$type];
		}
		
	}

?>
