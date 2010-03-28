<?php

	namespace BlueShiftTests;
	
	use BlueShift\Container;
	use stdClass;

	class ContainerTest extends \PHPUnit_Framework_TestCase {

		private $container;
	
		public function setUp() {
			$this->container = new Container();
		}
		
		public function tearDown() {
			$this->container = null;
		}
	
		public function testCannotRegisterInstanceOfWrongType() {
			$this->setExpectedException('BlueShift\ContainerException');
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
			$instance = new FooImplementation();
			$builder = $this->getMock('BlueShift\ObjectBuilder', array('build'));
			$builder
				->expects($this->once())
				->method('build')
				->with('BlueShiftTests\FooImplementation')
				->will($this->returnValue($instance));
			
			$this->container = new Container($builder);
			$this->container->addMapping('BlueShiftTests\Foo', 'BlueShiftTests\FooImplementation');
			$resolvedInstance = $this->container->resolve('BlueShiftTests\Foo');
			
			self::assertSame($instance, $resolvedInstance);
		}
		
		public function testResolveUnmappedUninstantiableType() {
			$this->setExpectedException('BlueShift\ContainerException');
			$this->container->resolve('BlueShiftTests\Foo');
		}
		
		public function testResolveUnmappedInstantiableType() {
			$instance = new FooImplementation();
			$builder = $this->getMock('BlueShift\ObjectBuilder', array('build'));
			$builder
				->expects($this->once())
				->method('build')
				->with('BlueShiftTests\FooImplementation')
				->will($this->returnValue($instance));
			
			$this->container = new Container($builder);
			$resolvedInstance = $this->container->resolve('BlueShiftTests\FooImplementation');
			
			self::assertSame($instance, $resolvedInstance);
		}
		
		public function testDependencyGeneration() {
			$dependencies = $this->container->getDependencies('BlueShiftTests\Baz');
			self::assertEquals(array('BlueShiftTests\Bar', 'BlueShiftTests\Foo'), $dependencies);
		}
		
		public function testDependencyGenerationWithTypeWithNonPublicConstructor() {
			$this->setExpectedException('BlueShift\InvalidConstructorException');
			$this->container->getDependencies('BlueShiftTests\BadConstructor1', 'Cannot instantiate object of type BlueShiftTests\BadConstructor1 because its constructor is not public');
		}
		
		public function testDependencyGenerationWithTypeThatHasInvalidDependencyConstructor() {
			$this->setExpectedException('BlueShift\InvalidConstructorException');
			$this->container->getDependencies('BlueShiftTests\BadConstructor2', 'Unable to resolve dependency for type ReflectionClass because constructor has invalid signature at position 1');
		}

	}
	
	//-- begin mocks --//
	interface Foo {}
	class FooImplementation implements Foo {}
	class Bar {
		public function __construct(Foo $foo1, Foo $foo2) {}
	}
	
	class Baz {
		public function __construct(Bar $bar) {}
	}
	
	class BadConstructor1 {
		private function __construct() {}
	}
	
	class BadConstructor2 {
		public function __construct(\ReflectionClass $class) {}
	}
	//-- end mocks --//

?>