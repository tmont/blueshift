<?php

	/**
	 * ObjectBuilder
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace BlueShift;
	
	use ReflectionClass;

	/**
	 * Builds objects. Duh.
	 *
	 * @package BlueShift
	 */
	interface ObjectBuilder {
	
		/**
		 * Dynamically creates an instance of the given class
		 *
		 * @param  ReflectionClass $class The class to create
		 * @param  array           $args  Constructor arguments
		 * @return object
		 */
		function build(ReflectionClass $class, array $args = array());
	}

?>