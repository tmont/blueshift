<?php

	namespace BlueShiftTests;
	
	use BlueShift\Container;
	use stdClass;
	use ReflectionClass;

	class ContainerTest extends \PHPUnit_Framework_TestCase {

		private $container;
	
		public function setUp() {
			$this->container = new Container();
		}
		
		public function tearDown() {
			$this->container = null;
		}
		
		public function testSerialization() {
			$this->container->addMapping('BlueShiftTests\Foo', 'BlueShiftTests\FooImplementation');
			$this->container->resolve('BlueShiftTests\Foo');
			
			$mappings = $this->container->getMappings();
			$dependencyGraph = $this->container->getDependencyGraph();
			
			
			$serialized = serialize($this->container);
			$container = unserialize($serialized);
			
			self::assertType('BlueShift\Container', $container);
			self::assertEquals($mappings, $container->getMappings());
			self::assertEquals($dependencyGraph, $container->getDependencyGraph());
		}
	
		public function testCannotAddMappingOfWrongType() {
			$this->setExpectedException('BlueShift\RegistrationException');
			$this->container->addMapping('BlueShiftTests\Foo', 'stdClass');
		}
	
		public function testCannotRegisterInstanceOfWrongType() {
			$this->setExpectedException('BlueShift\RegistrationException');
			$this->container->registerInstance('BlueShiftTests\Foo', new stdClass());
		}
		
		public function testRegisterInstanceMustBeAnActualInstance() {
			$this->setExpectedException('InvalidArgumentException');
			$this->container->registerInstance('BlueShiftTests\Foo', 'not an object');
		}
		
		public function testResolveInstance() {
			$instance = new FooImplementation();
			$this->container->registerInstance('BlueShiftTests\Foo', $instance);
			$resolvedInstance = $this->container->resolve('BlueShiftTests\Foo');
			
			self::assertSame($instance, $resolvedInstance);
		}
		
		public function testResolveMappedType() {
			$this->container->addMapping('BlueShiftTests\Foo', 'BlueShiftTests\FooImplementation');
			$resolvedInstance = $this->container->resolve('BlueShiftTests\Foo');
			self::assertType('BlueShiftTests\FooImplementation', $resolvedInstance);
		}
		
		public function testResolveUnmappedUninstantiableType() {
			$this->setExpectedException('BlueShift\ResolutionException');
			$this->container->resolve('BlueShiftTests\Foo');
		}
		
		public function testResolveUnmappedInstantiableType() {
			$resolvedInstance = $this->container->resolve('BlueShiftTests\FooImplementation');
			self::assertType('BlueShiftTests\FooImplementation', $resolvedInstance);
		}

		public function testResolveTypeWithNonPublicConstructor() {
			$this->setExpectedException(
				'BlueShift\InvalidConstructorException',
				'The type BlueShiftTests\BadConstructor has a non-public constructor and will not be able to be resolved'
			);
			$this->container->resolve('BlueShiftTests\BadConstructor');
		}
		
		public function testResolveTypeWithInvalidConstructorSignature() {
			$this->setExpectedException(
				'BlueShift\InvalidConstructorException',
				'Dependency graph for ReflectionClass cannot be built because its constructor signature contains an unresolvable (e.g. non-typehinted) type'
			);
			$this->container->resolve('ReflectionClass');
		}
		
		public function testResolveTypeWithDependencies() {
			$baz = $this->container->resolve('BlueShiftTests\Baz');
			self::assertType('BlueShiftTests\Baz', $baz);
			self::assertType('BlueShiftTests\Bar', $baz->bar);
			self::assertType('BlueShiftTests\FooImplementation', $baz->bar->foo);
		}
		
		public function testResolveTypeWithACyclicDependency() {
			$this->setExpectedException('BlueShift\DependencyException', 'A cyclic dependency was detected between BlueShiftTests\Cyclic2 and BlueShiftTests\Cyclic1');
			$this->container->resolve('BlueShiftTests\Cyclic1');
		}
	
	}
	
	//-- begin mocks --//
	interface Foo {}
	class FooImplementation implements Foo {}
	class BadConstructor {
		private function __construct() {}
	}
	
	class Bar {
		public $foo;
		public function __construct(FooImplementation $foo) {
			$this->foo = $foo;
		}
	}
	
	class Baz {
		public $bar;
		public function __construct(Bar $bar) {
			$this->bar = $bar;
		}
	}
	
	class Cyclic1 {
		public function __construct(Cyclic2 $x) {}
	}
	class Cyclic2 {
		public function __construct(Cyclic1 $x) {}
	}
	//-- end mocks --//

?>