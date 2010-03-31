<?php

	/**
	 * ReflectionUtil
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace BlueShift;
	
	use ReflectionClass, ReflectionMethod, Reflector;

	/**
	 * Reflection utilities
	 *
	 * @package BlueShift
	 */
	final class ReflectionUtil {

		//@codeCoverageIgnoreStart
		private function __construct() {}
		//@codeCoverageIgnoreEnd

		/**
		 * Determines if the specified class is able to be proxied
		 *
		 * Proxyable classes are non-final and instantiable
		 *
		 * @param  ReflectionClass $class
		 * @return bool
		 */
		public static function isProxyable(ReflectionClass $class) {
			return !$class->isFinal() && $class->isInstantiable();
		}
		
		/**
		 * Gets an array representation of a constructor's signature, with the
		 * keys being the parameter name and the values being name of the type or null
		 * if the parameter is not typehinted
		 *
		 * @param  ReflectionMethod $constructor
		 * @return array
		 */
		public static function getConstructorSignature(ReflectionMethod $constructor) {
			$params = $constructor->getParameters();
			$signature = array();
			foreach ($params as $param) {
				$class = $param->getClass();
				$signature[$param->getName()] = $class instanceof ReflectionClass ? ltrim($class->getName(), '\\') : null;
			}

			return $signature;
		}

		/**
		 * Stolen from PHP MVC. Gets the values of a doc comment
		 *
		 * @link   http://svn.tommymontgomery.com/phpmvc/src/trunk/src/PhpMvc/Util/ReflectionHelper.php
		 * @param  Reflector $reflector
		 * @return array
		 */
		public static function getDocCommentValues(Reflector $reflector) {
			if ($reflector instanceof ReflectionParameter) {
				//the documentation for parameters is in the function documentation (e.g. @param)
				$reflector = $reflector->getDeclaringFunction();
			}
			if (!method_exists($reflector, 'getDocComment')) {
				//Reflector doesn't actually have the getDocComment() method, but nearly every implementation does.
				//Reflector is only there for typehinting purposes.
				//ReflectionExtension is the only built-in implementation type that doesn't have getDocComment().
				return array();
			}

			$doc = $reflector->getDocComment();
			if (empty($doc)) {
				//no need to do any regex matching
				return array();
			}

			preg_match_all('/^[\s\*]*@(.+?)(?:\s|$)(?:\s*)?(.+)?/m', $doc, $values);
			if (!isset($values[2]) || empty($values[2])) {
				//no matches
				return array();
			}

			return self::combineDocCommentValues($values[1], $values[2]);
		}

		private static function combineDocCommentValues(array $keys, array $values) {
			$combined = array();
			foreach ($keys as $i => $key) {
				if (isset($values[$i])) {
					$combined[$key][] = $values[$i];
				}
			}

			return $combined;
		}

	}

?>