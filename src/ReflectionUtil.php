<?php

	/**
	 * ReflectionUtil
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace Tmont\BlueShift;
	
	use ReflectionClass, ReflectionMethod;

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
		 * Determines if the specified method is able to be proxied
		 *
		 * Proxyable methods are non-final, non-private, and are neither a constructor
		 * nor a destructor.
		 *
		 * @param  ReflectionMethod $method
		 * @return bool
		 */
		public static function methodIsProxyable(ReflectionMethod $method) {
			return !$method->isPrivate() && !$method->isFinal() && !$method->isConstructor() && !$method->isDestructor();
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

	}

?>