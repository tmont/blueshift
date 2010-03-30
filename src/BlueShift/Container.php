<?php

	namespace BlueShift;

	use InvalidArgumentException, ReflectionClass, Serializable, Closure;

	/**
	 * Represents a container for creating objects and automatically
	 * handling dependencies between objects
	 *
	 * @package BlueShift
	 */
	class Container implements Serializable {

		private $typeMappings = array();
		private $registeredInstances = array();
		private $dependencyGraph = array();
		private $proxyBuilder;
		private $typesToProxy = array();

		/**
		 * Sets the object to be used for creating proxies
		 *
		 * @param  ProxyBuilder
		 * @return Container
		 */
		public final function setProxyBuilder(ProxyBuilder $builder) {
			$this->proxyBuilder = $builder;
			return $this;
		}
		
		/**
		 * Gets the injected proxy builder, or creates a default one
		 *
		 * @return ProxyBuilder
		 */
		public final function getProxyBuilder() {
			return $this->proxyBuilder ?: ($this->proxyBuilder = new ProxyBuilder());
		}
		
		/**
		 * Informs the container to create a proxy of this type when it is resolved.
		 * This must be called regardless of whether any interceptors are registered.
		 *
		 * @return Container
		 */
		public final function proxyType($type) {
			$this->typesToProxy[] = $type;
			return $this;
		}

		/**
		 * @ignore
		 */
		public function serialize() {
			$data = array(
				'typeMappings' => $this->typeMappings,
				'dependencyGraph' => $this->dependencyGraph
			);
			
			return serialize($data);
		}
		
		/**
		 * @ignore
		 */
		public function unserialize($data) {
			$data = unserialize($data);
			$this->typeMappings = $data['typeMappings'];
			$this->dependencyGraph = $data['dependencyGraph'];
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
		 * Gets the dependency graph for types that have already been resolved
		 *
		 * @return array
		 */
		public final function getDependencyGraph() {
			return $this->dependencyGraph;
		}
		
		/**
		 * Gets a list of registered type mappings
		 *
		 * @return array
		 */
		public final function getMappings() {
			return $this->typeMappings;
		}
		
		/**
		 * Registers an interceptor for any resolved type that matches the filter
		 * expression
		 * 
		 * This method is merely a wrapper for {@link InterceptorCache::registerInterceptor()}.
		 *
		 * @uses    InterceptorCache::registerInterceptor()
		 * @param   Interceptor $interceptor Interceptor implementation to register
		 * @param   Closure     $matcher     Predicate that takes a ReflectionClass as an argument and returns a boolean
		 * @returns Container
		 */
		public function registerInterceptor(Interceptor $interceptor, Closure $matcher) {
			InterceptorCache::registerInterceptor($interceptor, $matcher);
			return $this;
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
			$refClass = ReflectionCache::getClass($concrete);
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
			
			$refClass = ReflectionCache::getClass(get_class($instance));
			if (!$refClass->implementsInterface($abstract) && !$refClass->isSubclassOf($abstract)) {
				throw new RegistrationException('The class ' . get_class($instance)  . ' does not inherit from or implement ' . $abstract);
			}

			$this->addInstance($abstract, $instance);
			return $this;
		}

		/**
		 * Builds the dependency graph for the specified type and checks for cyclic
		 * dependencies
		 *
		 * @param  string $type
		 * @throws {@link InvalidConstructorException}
		 * @throws {@link DependencyException}
		 */
		protected final function buildDependencyGraphForType($type) {
			$constructor = ReflectionCache::getConstructor($type);
			if ($constructor !== null && !$constructor->isPublic()) {
				throw new InvalidConstructorException('The type ' . $type . ' has a non-public constructor and will not be able to be resolved');
			}

			//if constructor is null, then one is not defined, so that means the default parameterless constructor will be used and has no dependencies
			$dependentTypes = ($constructor !== null) ? ReflectionUtil::getConstructorSignature($constructor) : array();
			
			if (!array_reduce($dependentTypes, function($current, $next) { return $current && $next !== null; }, true)) {
				throw new InvalidConstructorException(
					'Dependency graph for ' . $type . ' cannot be built ' .
					'because its constructor signature contains an unresolvable (e.g. non-typehinted) type'
				);
			}
			
			$this->dependencyGraph[$type] = $dependentTypes;
			foreach ($dependentTypes as $dependentType) {
				if (!isset($this->dependencyGraph[$dependentType])) {
					$this->buildDependencyGraphForType($dependentType);
				}
				
				//check for cycles
				foreach ($this->dependencyGraph[$dependentType] as $dependency) {
					if ($dependency === $type) {
						throw new DependencyException('A cyclic dependency was detected between ' . $type . ' and ' . $dependentType);
					}
				}
			}
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
		 * @param  string $type
		 * @throws {@link ResolutionException}
		 * @return object An instance of the specified type
		 */
		public function resolve($type) {
			//check if the instance is already registered
			$instance = $this->getInstance($type);
			if ($instance !== null) {
				return $instance;
			}
			
			//add to dependency graph if not already there
			if (!isset($this->dependencyGraph[$type])) {
				$this->buildDependencyGraphForType($type);
			}
			
			//check if the type has a mapping
			$concreteType = $this->getMapping($type);
			if ($concreteType === null) {
				//if it's instantiable, add it to the dependency graph
				$class = ReflectionCache::getClass($type);
				if (!$class->isInstantiable()) {
					throw new ResolutionException("The type $type has not been mapped and is not instantiable");
				}
			} else {
				$class = ReflectionCache::getClass($concreteType);
			}
			
			$constructor = ReflectionCache::getConstructor($type);
			$args = array();
			if ($constructor !== null) {
				$that = $this;
				$args = array_map(function($dependency) use ($that) { return $that->resolve($dependency); }, $this->dependencyGraph[$type]);
			}
			
			if (in_array($type, $this->typesToProxy)) {
				return $this->getProxyBuilder()->build($class, $args);
			}
			
			return ($constructor === null) ? $class->newInstance() : $class->newInstanceArgs($args);
		}
		
	}

?>