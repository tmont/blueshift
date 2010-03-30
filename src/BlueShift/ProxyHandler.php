<?php

	namespace BlueShift;
	
	final class ProxyHandler {
		public static function interceptBefore(InterceptionContext $context) {
			self::iterateOverInterceptors($context, 'before');
		}
		
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