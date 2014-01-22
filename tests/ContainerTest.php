<?php

	namespace Tmont\BlueShift\Tests;
	
	use Tmont\BlueShift\Container;
	use Tmont\Phroxy\InterceptorCache;
	use stdClass;
	use ReflectionMethod;

	class ContainerTest extends \PHPUnit_Framework_TestCase {

		private $container;
	
		public function setUp() {
			$this->container = new Container();
			InterceptorCache::reset();
		}
		
		public function tearDown() {
			$this->container = null;
			InterceptorCache::reset();
		}
		
		public function testSerialization() {
			$this->container->registerType('Tmont\BlueShift\Tests\Foo', 'Tmont\BlueShift\Tests\FooImplementation');
			$this->container->resolve('Tmont\BlueShift\Tests\Foo');
			
			$mappings = $this->container->getMappings();
			$dependencyGraph = $this->container->getDependencyGraph();
			
			
			$serialized = serialize($this->container);
			$container = unserialize($serialized);
			
			self::assertInstanceOf('Tmont\BlueShift\Container', $container);
			self::assertEquals($mappings, $container->getMappings());
			self::assertEquals($dependencyGraph, $container->getDependencyGraph());
		}
	
		public function testCannotRegisterTypeOfWrongType() {
			$this->setExpectedException('Tmont\BlueShift\RegistrationException');
			$this->container->registerType('Tmont\BlueShift\Tests\Foo', 'stdClass');
		}
	
		public function testCannotRegisterInstanceOfWrongType() {
			$this->setExpectedException('Tmont\BlueShift\RegistrationException');
			$this->container->registerInstance('Tmont\BlueShift\Tests\Foo', new stdClass());
		}
		
		public function testRegisterInstanceMustBeAnActualInstance() {
			$this->setExpectedException('InvalidArgumentException');
			$this->container->registerInstance('Tmont\BlueShift\Tests\Foo', 'not an object');
		}
		
		public function testResolveInstance() {
			$instance = new FooImplementation();
			$this->container->registerInstance('Tmont\BlueShift\Tests\Foo', $instance);
			$resolvedInstance = $this->container->resolve('Tmont\BlueShift\Tests\Foo');
			
			self::assertSame($instance, $resolvedInstance);
		}
		
		public function testResolveMappedType() {
			$this->container->registerType('Tmont\BlueShift\Tests\Foo', 'Tmont\BlueShift\Tests\FooImplementation');
			$resolvedInstance = $this->container->resolve('Tmont\BlueShift\Tests\Foo');
			self::assertInstanceOf('Tmont\BlueShift\Tests\FooImplementation', $resolvedInstance);
		}
		
		public function testResolveUnmappedUninstantiableType() {
			$this->setExpectedException('Tmont\BlueShift\ResolutionException');
			$this->container->resolve('Tmont\BlueShift\Tests\Foo');
		}
		
		public function testResolveUnmappedInstantiableType() {
			$resolvedInstance = $this->container->resolve('Tmont\BlueShift\Tests\FooImplementation');
			self::assertInstanceOf('Tmont\BlueShift\Tests\FooImplementation', $resolvedInstance);
		}

		public function testResolveTypeWithNonPublicConstructor() {
			$this->setExpectedException(
				'Tmont\BlueShift\InvalidConstructorException',
				'The type Tmont\BlueShift\Tests\BadConstructor has a non-public constructor and will not be able to be resolved'
			);
			$this->container->resolve('Tmont\BlueShift\Tests\BadConstructor');
		}
		
		public function testResolveTypeWithInvalidConstructorSignature() {
			$this->setExpectedException(
				'Tmont\BlueShift\InvalidConstructorException',
				'Dependency graph for ReflectionClass cannot be built because its constructor signature contains an unresolvable (e.g. non-typehinted) type'
			);
			$this->container->resolve('ReflectionClass');
		}
		
		public function testResolveTypeWithDependencies() {
			$baz = $this->container->resolve('Tmont\BlueShift\Tests\Baz');
			self::assertInstanceOf('Tmont\BlueShift\Tests\Baz', $baz);
			self::assertInstanceOf('Tmont\BlueShift\Tests\Bar', $baz->bar);
			self::assertInstanceOf('Tmont\BlueShift\Tests\FooImplementation', $baz->bar->foo);
		}
		
		public function testResolveTypeWithACyclicDependency() {
			$this->setExpectedException('Tmont\BlueShift\DependencyException', 'A cyclic dependency was detected between Tmont\BlueShift\Tests\Cyclic2 and Tmont\BlueShift\Tests\Cyclic1');
			$this->container->resolve('Tmont\BlueShift\Tests\Cyclic1');
		}
		
		public function testResolveUsingProxybuilder() {
			$builder = $this->getMock('Tmont\Phroxy\ObjectBuilder');
			$builder->expects($this->once())->method('build')->will($this->returnValue('foo'));
			
			$this->container
				->setObjectBuilder($builder)
				->registerType('Tmont\BlueShift\Tests\Foo', 'Tmont\BlueShift\Tests\FooImplementation')
				->proxyType('Tmont\BlueShift\Tests\Foo');
			
			self::assertEquals('foo', $this->container->resolve('Tmont\BlueShift\Tests\Foo'));
		}
		
		public function testAddInterceptorToCache() {
			$interceptor = $this->getMock('Tmont\Phroxy\Interceptor');
			$this->container->registerInterceptor($interceptor, function($x) { return true; });
			self::assertEquals(1, count(InterceptorCache::getInterceptors(new ReflectionMethod($interceptor, 'onBeforeMethodCall'))));
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