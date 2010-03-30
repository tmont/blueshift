<?php

	namespace BlueShift;
	
	use ReflectionClass;
	use ReflectionMethod;
	use ReflectionParameter;
	use BlueShift\Util\ReflectionUtil;
	use BlueShift\Util\ReflectionCache;
	
	class ProxyBuilder {
		
		private $proxyCache = array();
		const DEFAULT_NAMESPACE = 'BlueShift';
		
		public function build(ReflectionClass $class, array $args = array()) {
			$name = $class->getName();
			if (!isset($this->proxyCache[$name])) {
				if (!ReflectionUtil::isProxyable($class)) {
					throw new ProxyException('The type ' . $name . ' cannot be proxied');
				}
				
				$this->proxyCache[$name] = $this->buildProxy($class);
			}
			
			if (!empty($args)) {
				$proxy = ReflectionCache::getClass($this->proxyCache[$name]);
				return $proxy->newInstanceArgs($args);
			} else {
				return new $this->proxyCache[$name]();
			}
		}
		
		private function buildProxy(ReflectionClass $class) {
			$name = $this->generateClassName($class);
			
			$code = $this->buildNamespace($class);
			$code .= $this->buildClassDefinition($class, $name);
			
			eval($code);
			
			return self::DEFAULT_NAMESPACE . '\\' . $name;
		}
		
		protected function generateClassName(ReflectionClass $class) {
			$prefix = 'BlueShiftProxy_' . str_replace('\\', '_', $class->getName());
			do {
				$name = $prefix . '_' . uniqid();
			} while (class_exists(self::DEFAULT_NAMESPACE . '\\' . $name) || interface_exists(self::DEFAULT_NAMESPACE . '\\' . $name));
			
			return $name;
		}
		
		private function buildNamespace(ReflectionClass $class) {
			return "namespace BlueShift;\nuse ReflectionMethod, Exception;\n\n";
		}
		
		private function buildClassDefinition(ReflectionClass $class, $className) {
			$code = "class $className extends \\" . $class->getName() . " {\n";
			
			foreach ($class->getMethods() as $method) {
				if ($method->isPrivate() || $method->isFinal()) {
					continue;
				}
				
				$code .= $this->buildMethod($method);
			}
			
			$code .= '}';
			return $code;
		}
		
		private function buildMethod(ReflectionMethod $method) {
			$code = "\t";
			$code .= $method->isPublic() ? 'public ' : 'protected ';
			$code .= $method->isStatic() ? 'static ' : '';
			$code .= 'function ';
			if ($method->returnsReference()) {
				$code .= '&';
			}
			$code .= $method->getName() . '(';
			
			$params = array();
			$paramVars = array();
			foreach ($method->getParameters() as $parameter) {
				$params[] = $this->buildMethodParameter($parameter);
				$paramVars[] = '$' . $parameter->getName();
			}
			
			do {
				$contextVar = '$context_' . uniqid();
			} while (in_array($contextVar, $paramVars));
			
			do {
				$interceptorsVar = '$interceptors_' . uniqid();
			} while (in_array($interceptorsVar, $paramVars));
			
			$code .= implode(', ', $params) . ") {\n";
			$methodCall = $method->getName() . '(' . implode(', ', $paramVars) . ')';
			
			$code .= <<<METHODBODY
		$contextVar = new InterceptionContext(isset(\$this) ? \$this : null, new ReflectionMethod(__CLASS__, __FUNCTION__), func_get_args());
		ProxyHandler::interceptBefore($contextVar);

		if ({$contextVar}->shouldCallNext()) {
			try {
				{$contextVar}->setReturnValue(parent::$methodCall);
			} catch (Exception \$e) {
				{$contextVar}->setException(\$e);
			}
		}

		ProxyHandler::interceptAfter($contextVar);
		\$exception = {$contextVar}->getException();
		if (\$exception !== null) {
			throw \$exception;
		} else {
			return {$contextVar}->getReturnValue();
		}

METHODBODY;
			
			$code .= "\t}\n";
			
			return $code;
		}
		
		private function buildMethodParameter(ReflectionParameter $parameter) {
			$code = '';
			if ($parameter->isArray()) {
				$code .= 'array';
			} else {
				$class = $parameter->getClass();
				if ($class instanceof ReflectionClass) {
					$code .= $class->getName();
				}
			}
			
			$code .= ' ';
			if ($parameter->isPassedByReference()) {
				$code .= '&';
			}
			
			$code .= '$' . $parameter->getName();
			if ($parameter->isOptional()) {
				$code .= ' = ';
				if ($parameter->isDefaultValueAvailable()) {
					$code .= var_export($parameter->getDefaultValue(), true);
				} else {
					$code .= 'null';
				}
			}
			
			return $code;
		}
		
	}

?>