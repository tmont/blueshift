<?php

	/**
	 * Container
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace BlueShift;

	use InvalidArgumentException, ReflectionClass, Serializable, Closure;
	use Phroxy\ObjectBuilder;
	use Phroxy\ProxyBuilder;
	use Phroxy\Interceptor;
	use Phroxy\InterceptorCache;

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
		private $objectBuilder;
		private $typesToProxy = array();

		/**
		 * Sets the object to be used for creating proxies
		 *
		 * @param  ObjectBuilder $builder
		 * @return Container
		 */
		public final function setObjectBuilder(ObjectBuilder $builder) {
			$this->objectBuilder = $builder;
			return $this;
		}
		
		/**
		 * Gets the injected proxy builder, or creates a default {@link ProxyBuilder}
		 *
		 * @return ObjectBuilder
		 */
		public final function getObjectBuilder() {
			return $this->objectBuilder ?: ($this->objectBuilder = new ProxyBuilder());
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
		
		/**
		 * @param string $typeToResolve
		 * @param string $typeToCreate
		 */
		protected final function addTypeMapping($typeToResolve, $typeToCreate) {
			$this->typeMappings[$typeToResolve] = $typeToCreate;
		}
		
		/**
		 * @param  string $typeToResolve
		 * @return string|null The type to create, or null if no mapping exists
		 */
		protected final function getMapping($typeToResolve) {
			return @$this->typeMappings[$typeToResolve];
		}

		/**
		 * @param string $typeToResolve
		 * @param object $instance
		 */
		protected final function addInstance($typeToResolve, $instance) {
			$this->registeredInstances[$typeToResolve] = $instance;
		}
		
		/**
		 * @param  string $typeToResolve
		 * @return object|null The registered instance, or null if no mapping exists
		 */
		protected final function getInstance($typeToResolve) {
			return @$this->registeredInstances[$typeToResolve];
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
		 * @param   Closure     $matcher     Predicate that takes a ReflectionMethod as an argument and returns a boolean
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
		 * @uses    ReflectionCache::getClass()
		 * @param   string $typeToResolve
		 * @param   string $typeToCreate
		 * @throws  {@link RegistrationException} if the concrete type does not derive from or implement the given class or interface
		 * @returns Container
		 */
		public function registerType($typeToResolve, $typeToCreate) {
			$refClass = ReflectionCache::getClass($typeToCreate);
			if (!$refClass->implementsInterface($typeToResolve) && !$refClass->isSubclassOf($typeToResolve)) {
				throw new RegistrationException('The type ' . $typeToCreate  . ' does not inherit from or implement ' . $typeToResolve);
			}
			
			$this->addTypeMapping($typeToResolve, $typeToCreate);
			return $this;
		}

		/**
		 * Registers a specific instance of a class or interface so that when
		 * that class or interface is resolved it will return this specific instance
		 *
		 * @uses    ReflectionCache::getClass()
		 * @param   string $typeToResolve
		 * @param   object $instance
		 * @throws  InvalidArgumentException
		 * @throws  {@link RegistrationException} if the instance does not derive from or implement the given class or interface
		 * @returns Container
		 */
		public function registerInstance($typeToResolve, $instance) {
			if (!is_object($instance)) {
				throw new InvalidArgumentException('2nd argument must be an object');
			}
			
			$class = ReflectionCache::getClass(get_class($instance));
			if (!$class->implementsInterface($typeToResolve) && !$class->isSubclassOf($typeToResolve)) {
				throw new RegistrationException('The class ' . get_class($instance)  . ' does not inherit from or implement ' . $typeToResolve);
			}

			$this->addInstance($typeToResolve, $instance);
			return $this;
		}

		/**
		 * Builds the dependency graph for the specified type and checks for cyclic
		 * dependencies
		 *
		 * @uses   ReflectionCache::getConstructor()
		 * @uses   ReflectionUtil::getConstructorSignature()
		 * @uses   buildDependencyGraphForType()
		 * @param  string $type
		 * @throws {@link InvalidConstructorException}
		 * @throws {@link DependencyException} if a cyclic dependency was detected
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
		 * @uses   buildDependencyGraphForType()
		 * @uses   ReflectionCache::getClass()
		 * @uses   ReflectionCache::getConstructor()
		 * @uses   ProxyBuilder::build()
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
				//if it's unmapped and instantiable it's okay
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
				$that = $this; //lulz
				$args = array_map(function($dependency) use ($that) { return $that->resolve($dependency); }, $this->dependencyGraph[$type]);
			}
			
			if (in_array($type, $this->typesToProxy)) {
				return $this->getObjectBuilder()->build($class, $args);
			}
			
			return ($constructor === null) ? $class->newInstance() : $class->newInstanceArgs($args);
		}
		
	}

?>