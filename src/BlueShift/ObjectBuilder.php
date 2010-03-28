<?php

	namespace BlueShift;
	
	use ReflectionClass;

	class ObjectBuilder {

		private $dependencyCache = array();
		private $proxyBuilder;
		
		public function __construct(ProxyBuilder $proxyBuilder = null) {
			$this->proxyBuilder = $proxyBuilder ?: new ProxyBuilder();
		}
		
		/**
		 * Builds an instance the given type
		 *
		 * @param ReflectionClass $class The type of the object to build
		 * @return object
		 */
		public function build(ReflectionClass $class, $buildProxy = false) {
			if ($buildProxy && ReflectionUtil::isProxyable($class)) {
				return $this->proxyBuilder->build($class);
			}
			
			$args = array();
			
			$constructor = $class->getConstructor();
			if ($constructor !== null) {
				if (!$constructor->isPublic()) {
					throw new InvalidConstructorException('The type ' . $class->getName() . ' cannot be built because the constructor is not public');
				}
				
				$args = $this->generateConstructorArgs($args);
			}
			
			return $class->newInstanceArgs($args);
		}
		
		protected function generateConstructorArgs(ReflectionClass $class) {
			
		}
		
		/**
		 * Gets an array of all classes that the specified type is dependent on
		 * upon if it were to be constructed by the container
		 *
		 * @param  ReflectionClass $class
		 * @throws {@link InvalidConstructorException} if the type or one of its dependencies has an invalid constructor
		 * @return array
		 */
		public function getDependencies(ReflectionClass $class) {
			$type = $class->getName();
			if (isset($this->dependencyCache[$type])) {
				return $this->dependencyCache[$type];
			}

			$this->dependencyCache[$type] = array();

			$constructor = $class->getConstructor();
			if ($constructor !== null && !$constructor->isPublic()) {
				throw new InvalidConstructorException('Cannot instantiate object of type ' . $type . ' because its constructor is not public');
			}

			//if constructor is null, then one is not defined, so that means the default parameterless constructor will be used and has no dependencies
			$dependentTypes = ($constructor !== null) ? ReflectionUtil::getConstructorSignature($constructor) : array();

			foreach ($dependentTypes as $i => $dependentType) {
				if ($dependentType === null) {
					throw new InvalidConstructorException(
						'Unable to resolve dependency for type ' . $type .
						' because constructor signature has an invalid type at position ' . ($i + 1)
					);
				}
				
				$this->dependencyCache[$type][] = $dependentType;
				$this->dependencyCache[$type] += $this->getDependencies(new ReflectionClass($dependentType));
			}

			return $this->dependencyCache[$type];
		}

	}

?>