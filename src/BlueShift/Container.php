<?php

	namespace BlueShift;

	use \InvalidArgumentException;
	use \ReflectionClass;

	class Container {

		private $typeMappings = array();
		private $registeredInstances = array();
		private $objectBuilder;

		public function __construct(ObjectBuilder $objectBuilder = null) {
			$this->objectBuilder = $objectBuilder ?: new ObjectBuilder();
		}
		
		protected final function addTypeMapping($abstract, $concrete) {
			$this->typeMappings[$abstract] = $concrete;
		}
		
		protected final function getMapping($abstract) {
			return @$this->typeMappings[$abstract];
		}

		protected final function addInstance($abstract, $instance) {
			$this->registeredInstances[$abstract] = $instance;
		}
		
		protected final function getInstance($abstract) {
			return @$this->registeredInstances[$abstract];
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

		/**
		 * Resolves the specified interface or class to an instance
		 *
		 * If there is no mapping for the specified type and it's able to be
		 * instantiated (e.g. not abstract and not an interface), then it will
		 * just resolve to whatever instance the object builder creates, like
		 * a normal type that was mapped. In short, this method will resolve
		 * unmapped types that are able to be constructed.
		 *
		 * @param  string $typeToResolve
		 * @throws {@link ResolutionException}
		 * @return object An instance of the specified type
		 */
		public function resolve($type) {
			//check if the instance is already registered
			$instance = $this->getInstance($type);
			if ($instance !== null) {
				return $instance;
			}
			
			$refClass = null;
			
			//finally, check if the type has a mapping, and then create it
			$concreteType = $this->getMapping($type);
			if ($concreteType === null) {
				//if it's instantiable, then we just resolve its dependencies
				$refClass = new ReflectionClass($type);
				if (!$refClass->isInstantiable()) {
					throw new ResolutionException("The type $type has not been mapped and is not instantiable");
				}
			} else {
				$refClass = new ReflectionClass($concreteType);
			}

			$instance = $this->objectBuilder->build($refClass);
			return $instance;
		}

	}

?>