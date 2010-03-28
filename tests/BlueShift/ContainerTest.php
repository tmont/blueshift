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

	}
	
	interface Foo {}
	class FooImplementation implements Foo {}

?>