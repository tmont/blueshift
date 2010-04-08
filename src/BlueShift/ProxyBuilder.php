<?php

	/**
	 * ProxyBuilder
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace BlueShift;
	
	use ReflectionClass, ReflectionMethod, ReflectionParameter;
	
	/**
	 * Class for dynamically building interceptable proxies
	 *
	 * @package BlueShift
	 */
	class ProxyBuilder implements ObjectBuilder {
		
		private $proxyCache = array();
		
		/**
		 * The namespace that the proxy is contained within
		 *
		 * @var string
		 */
		const DEFAULT_NAMESPACE = 'BlueShift';
		
		/**
		 * Creates an interceptable proxy of the given class
		 *
		 * @uses   ReflectionCache::getClass()
		 * @uses   ReflectionUtil::isProxyable()
		 * @param  ReflectionClass $class The class to proxy, must be non-final and instantiable
		 * @param  array           $args  Constructor arguments
		 * @throws {@link ProxyException} if the class cannot be proxied
		 * @return object
		 */
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
		
		/**
		 * Generates a unique proxy name for the given class
		 *
		 * @param  ReflectionClass $class
		 * @return string
		 */
		protected function generateClassName(ReflectionClass $class) {
			$prefix = 'BlueShiftProxy_' . str_replace('\\', '_', $class->getName());
			do {
				$name = $prefix . '_' . uniqid();
			} while (class_exists(self::DEFAULT_NAMESPACE . '\\' . $name) || interface_exists(self::DEFAULT_NAMESPACE . '\\' . $name));
			
			return $name;
		}
		
		/**
		 * Creates a proxy definition and eval()s it
		 *
		 * @uses   generateClassName()
		 * @uses   buildNamespaceDeclaration()
		 * @uses   buildClassDefinition()
		 * @param  ReflectionClass $class
		 * @return string The name of the generated class
		 */
		protected final function buildProxy(ReflectionClass $class) {
			$name = $this->generateClassName($class);
			
			$code = $this->buildNamespaceDeclaration($class);
			$code .= $this->buildClassDefinition($class, $name);
			
			
			//echo '<xmp>' . $code . '</xmp>'; exit;
			
			eval($code);
			
			return self::DEFAULT_NAMESPACE . '\\' . $name;
		}
		
		/**
		 * Builds the code needed for the namespace declaration
		 *
		 * @param  ReflectionClass $class
		 * @return string Literal PHP code
		 */
		protected function buildNamespaceDeclaration(ReflectionClass $class) {
			return "namespace BlueShift;\nuse ReflectionMethod, Exception;\n\n";
		}
		
		/**
		 * Builds the code needed for a class
		 *
		 * @uses   buildMethod()
		 * @uses   ReflectionUtil::methodIsProxyable()
		 * @param  ReflectionClass $class
		 * @param  string          $className The name of the proxy
		 * @return string Literal PHP code
		 */
		protected function buildClassDefinition(ReflectionClass $class, $className) {
			$code = "class $className extends \\" . $class->getName() . " {\n";
			
			foreach ($class->getMethods() as $method) {
				if (!ReflectionUtil::methodIsProxyable($method)) {
					continue;
				}
				
				$code .= $this->buildMethod($method);
			}
			
			$code .= '}';
			return $code;
		}
		
		/**
		 * Builds the code needed for a method
		 *
		 * @uses   buildMethodParameter()
		 * @param  ReflectionMethod $method
		 * @return string Literal PHP code
		 */
		protected function buildMethod(ReflectionMethod $method) {
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
		
		/**
		 * Builds the code needed for a method parameter
		 *
		 * @param  ReflectionParameter $parameter
		 * @return string Literal PHP code
		 */
		protected final function buildMethodParameter(ReflectionParameter $parameter) {
			$code = '';
			if ($parameter->isArray()) {
				$code .= 'array';
			} else {
				$class = $parameter->getClass();
				if ($class instanceof ReflectionClass) {
					$code .= '\\' . $class->getName();
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