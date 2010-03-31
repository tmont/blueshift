<?php

	/**
	 * InterceptorCache
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace BlueShift;

	use Closure, ReflectionMethod;
	
	/**
	 * Cache for interceptors so that repeated retrieval is not hideously slow
	 *
	 * All interceptor registration should go through the {@link Container}.
	 *
	 * @package BlueShift
	 */
	final class InterceptorCache {
		
		private static $interceptors = array();
		private static $cache = array();
		
		/**
		 * Registers an interceptor in the cache
		 *
		 * @param Interceptor $interceptor
		 * @param Closure     $matcher     Function that takes a ReflectionMethod as an argument and returns a boolean
		 */
		public static function registerInterceptor(Interceptor $interceptor, Closure $matcher) {
			self::$interceptors[] = array('interceptor' => $interceptor, 'matcher' => $matcher);
		}
		
		/**
		 * Resets this object, used for testing
		 * @ignore
		 */
		public static function reset() {
			self::$cache = array();
			self::$interceptors = array();
		}
		
		/**
		 * Gets all interceptors for the specified method invocation
		 *
		 * @param  ReflectionMethod $method
		 * @return array
		 */
		public static function getInterceptors(ReflectionMethod $method) {
			$key = $method->getDeclaringClass()->getName() . '::' . $method->getName();
			
			if (!isset(self::$cache[$key])) {
				self::$cache[$key] = array();
				foreach (self::$interceptors as $data) {
					if (call_user_func($data['matcher'], $method)) {
						self::$cache[$key][] = $data['interceptor'];
					}
				}
			}
			
			return self::$cache[$key];
		}
		
	}

?>