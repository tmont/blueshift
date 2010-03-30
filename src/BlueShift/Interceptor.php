<?php

	namespace BlueShift;

	interface Interceptor {
		function onBeforeMethodCall(InterceptionContext $context);
		function onAfterMethodCall(InterceptionContext $context);
	}
	
?>