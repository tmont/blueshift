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
			$instance = new FooImplementation();
			$builder = $this->getMock('BlueShift\ObjectBuilder', array('build'));
			$builder
				->expects($this->once())
				->method('build')
				->with(new ReflectionClass('BlueShiftTests\FooImplementation'))
				->will($this->returnValue($instance));
			
			$this->container = new Container($builder);
			$this->container->addMapping('BlueShiftTests\Foo', 'BlueShiftTests\FooImplementation');
			$resolvedInstance = $this->container->resolve('BlueShiftTests\Foo');
			
			self::assertSame($instance, $resolvedInstance);
		}
		
		public function testResolveUnmappedUninstantiableType() {
			$this->setExpectedException('BlueShift\ResolutionException');
			$this->container->resolve('BlueShiftTests\Foo');
		}
		
		public function testResolveUnmappedInstantiableType() {
			$instance = new FooImplementation();
			$builder = $this->getMock('BlueShift\ObjectBuilder', array('build'));
			$builder
				->expects($this->once())
				->method('build')
				->with(new ReflectionClass('BlueShiftTests\FooImplementation'))
				->will($this->returnValue($instance));
			
			$this->container = new Container($builder);
			$resolvedInstance = $this->container->resolve('BlueShiftTests\FooImplementation');
			
			self::assertSame($instance, $resolvedInstance);
		}

	}
	
	//-- begin mocks --//
	interface Foo {}
	class FooImplementation implements Foo {}
	//-- end mocks --//

?>