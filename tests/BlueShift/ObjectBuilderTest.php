<?php

	namespace BlueShiftTests;
	
	use BlueShift\ObjectBuilder;
	use ReflectionClass;
	use stdClass;

	class ObjectBuilderTest extends \PHPUnit_Framework_TestCase {

		private $builder;
	
		public function setUp() {
			$this->builder = new ObjectBuilder();
		}
		
		public function tearDown() {
			$this->builder = null;
		}
	
		public function testDependencyGeneration() {
			$dependencies = $this->builder->getDependencies(new ReflectionClass('BlueShiftTests\Baz'));
			self::assertEquals(array('BlueShiftTests\Bar', 'BlueShiftTests\Foofy'), $dependencies);
		}
		
		public function testDependencyGenerationWithTypeWithNonPublicConstructor() {
			$this->setExpectedException('BlueShift\InvalidConstructorException', 'Cannot instantiate object of type BlueShiftTests\BadConstructor1 because its constructor is not public');
			$this->builder->getDependencies(new ReflectionClass('BlueShiftTests\BadConstructor1'));
		}
		
		public function testDependencyGenerationWithTypeThatHasInvalidDependencyConstructor() {
			$this->setExpectedException('BlueShift\InvalidConstructorException', 'Unable to resolve dependency for type ReflectionClass because constructor signature has an invalid type at position 1');
			$this->builder->getDependencies(new ReflectionClass('BlueShiftTests\BadConstructor2'));
		}

	}
	
	//-- begin mocks --//
	class Foofy {}
	class Bar {
		public function __construct(Foofy $foo1, Foofy $foo2) {}
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