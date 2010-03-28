<?php

	namespace BlueShift;

	use \InvalidArgumentException;
	use \ReflectionClass;

	class Container {

		private $typeMappings = array();
		private $registeredInstances = array();
		private $dependencyCache = array();
		private $objectBuilder;

		public function __construct(ObjectBuilder $objectBuilder = null) {
			$this->objectBuilder = $objectBuilder ?: new ObjectBuilder();
		}
		
		public function addMapping($abstract, $concrete) {
			$this->addTypeMapping($abstract, $concrete);
			return $this;
		}

		public function registerInstance($abstract, $instance) {
			if (!is_object($instance)) {
				throw new InvalidArgumentException('2nd argument must be an object');
			}
			
			$refClass = new ReflectionClass($instance);
			if (!$refClass->implementsInterface($abstract) && !$refClass->isSubclassOf($abstract)) {
				throw new ContainerException('The class ' . get_class($instance)  . ' does not inherit from or implement ' . $abstract);
			}

			$this->addInstance($abstract, $instance);
		}

		protected final function addTypeMapping($abstract, $concrete) {
			$this->typeMappings[$abstract] = $concrete;
		}

		protected final function addInstance($abstract, $instance) {
			$this->registeredInstances[$abstract] = $instance;
		}
		
		protected final function getMapping($abstract) {
			return @$this->typeMappings[$abstract];
		}
		
		protected final function getInstance($abstract) {
			return @$this->registeredInstances[$abstract];
		}

		public function resolve($typeToResolve) {
			//check if the instance is already registered
			$instance = $this->getInstance($typeToResolve);
			if ($instance !== null) {
				return $instance;
			}

			//finally, check if the type has a mapping, and then create it
			$concreteType = $this->getMapping($typeToResolve);
			if ($concreteType === null) {
				//if it's instantiable, then we just resolve its dependencies
				$refClass = new ReflectionClass($typeToResolve);
				if (!$refClass->isInstantiable()) {
					throw new ContainerException("The type $typeToResolve has not been mapped");
				}

				unset($refClass);
				$concreteType = $typeToResolve;
			}

			$instance = $this->objectBuilder->build($concreteType, $this);
			return $instance;
		}

		/**
		 * Gets an array of all classes that the specified type is dependent on
		 * upon if it were to be constructed by the container
		 *
		 * @param  string $type
		 * @throws {@link InvalidConstructorException} if the type or one of its dependencies has an invalid constructor
		 * @return array
		 */
		public function getDependencies($type) {
			if (isset($this->dependencyCache[$type])) {
				return $this->dependencyCache[$type];
			}

			$this->dependencyCache[$type] = array();

			$refClass = new ReflectionClass($type);
			$constructor = $refClass->getConstructor();
			if ($constructor !== null && !$constructor->isPublic()) {
				throw new InvalidConstructorException('Cannot instantiate object of type ' . $type . ' because its constructor is not public');
			}

			//if constructor is null, then one is not defined, so that means the default parameterless constructor will be used and has no dependencies
			$dependentTypes = ($constructor !== null) ? ReflectionUtil::getConstructorSignature($constructor) : array();

			foreach ($dependentTypes as $i => $dependentType) {
				if ($dependentType === null) {
					throw new InvalidConstructorException('Unable to resolve dependency for type ' . $type . ' because constructor signature has an invalid type at position ' . ($i + 1));
				}
				
				$this->dependencyCache[$type][] = $dependentType;
				$this->dependencyCache[$type] += $this->getDependencies($dependentType);
			}

			return $this->dependencyCache[$type];
		}
	}

?>
