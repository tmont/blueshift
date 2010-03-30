<?php

	namespace BlueShift;

	use Closure, ReflectionMethod;
	
	final class InterceptorCache {
		
		private static $interceptors = array();
		private static $cache = array();
		
		public static function registerInterceptor(Interceptor $interceptor, Closure $matcher) {
			self::$interceptors[] = array('interceptor' => $interceptor, 'matcher' => $matcher);
		}
		
		public static function rebuildCache() {
			self::$cache = array();
		}
		
		public static function purge() {
			self::$interceptors = array();
		}
		
		public static function reset() {
			self::rebuildCache();
			self::purge();
		}
		
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