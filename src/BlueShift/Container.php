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
		
		/**
		 * Registers a mapping between a class or interface and its concrete
		 * implementation. The concrete implementation will be dynamically created
		 * when the specified type is resolved.
		 *
		 * @param   string $abstract The class or interface to be resolved
		 * @param   string $concrete The class that will be instantiated
		 * @throws  {@link RegistrationException} if the concrete type does not derive from or implement the given class or interface
		 * @returns Container
		 */
		public function addMapping($abstract, $concrete) {
			$refClass = new ReflectionClass($concrete);
			if (!$refClass->implementsInterface($abstract) && !$refClass->isSubclassOf($abstract)) {
				throw new RegistrationException('The type ' . $concrete  . ' does not inherit from or implement ' . $abstract);
			}
			
			$this->addTypeMapping($abstract, $concrete);
			return $this;
		}

		/**
		 * Registers a specific instance of an class or interface so that when
		 * that class or interface is resolved it will return this specific instance
		 *
		 * @param   string $abstract The class or interface to map the instance to
		 * @param   object $instance
		 * @throws  InvalidArgumentException
		 * @throws  {@link RegistrationException} if the instance does not derive from or implement the given class or interface
		 * @returns Container
		 */
		public function registerInstance($abstract, $instance) {
			if (!is_object($instance)) {
				throw new InvalidArgumentException('2nd argument must be an object');
			}
			
			$refClass = new ReflectionClass($instance);
			if (!$refClass->implementsInterface($abstract) && !$refClass->isSubclassOf($abstract)) {
				throw new RegistrationException('The class ' . get_class($instance)  . ' does not inherit from or implement ' . $abstract);
			}

			$this->addInstance($abstract, $instance);
			return $this;
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

		/**
		 * Resolves the specified interface or class to an instance
		 *
		 * @param  string $typeToResolve
		 * @return object An instance of the specified type
		 */
		public function resolve($type) {
			//check if the instance is already registered
			$instance = $this->getInstance($type);
			if ($instance !== null) {
				return $instance;
			}

			//finally, check if the type has a mapping, and then create it
			$concreteType = $this->getMapping($type);
			if ($concreteType === null) {
				//if it's instantiable, then we just resolve its dependencies
				$refClass = new ReflectionClass($type);
				if (!$refClass->isInstantiable()) {
					throw new ResolutionException("The type $type has not been mapped and is not instantiable");
				}

				unset($refClass);
				$concreteType = $type;
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