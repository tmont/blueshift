<?php

	/**
	 * ProxyHandler
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace BlueShift;
	
	/**
	 * Class used by proxies to invoke interceptors
	 *
	 * This class should not be used directly.
	 *
	 * @package BlueShift
	 */
	final class ProxyHandler {
	
		/**
		 * Invokes the {@link Interceptor::onBeforeMethodCall()} method
		 *
		 * @param InterceptionContext $context
		 */
		public static function interceptBefore(InterceptionContext $context) {
			self::iterateOverInterceptors($context, 'before');
		}
		
		/**
		 * Invokes the {@link Interceptor::onAfterMethodCall()} method
		 *
		 * @param InterceptionContext $context
		 */
		public static function interceptAfter(InterceptionContext $context) {
			self::iterateOverInterceptors($context, 'afer');
		}
		
		private static function iterateOverInterceptors(InterceptionContext $context, $event) {
			$interceptors = InterceptorCache::getInterceptors($context->getMethod());
			if (!empty($interceptors)) {
				foreach ($interceptors as $interceptor) {
					if (!$context->shouldCallNext()) {
						break;
					}
					
					($event === 'before') ? $interceptor->onBeforeMethodCall($context) : $interceptor->onAfterMethodCall($context);
				}
			}
		}
		
	}

?>