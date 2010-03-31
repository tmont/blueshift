<?php

	/**
	 * InterceptionContext
	 *
	 * @package   BlueShift
	 * @version   1.0
	 * @copyright (c) 2010 Tommy Montgomery
	 */

	namespace BlueShift;

	use Exception, ReflectionMethod;

	/**
	 * Object for passing  and keeping track of data between interceptors
	 *
	 * @package BlueShift
	 */
	class InterceptionContext {
		private $target;
		private $method;
		private $args;
		private $data = array();
		private $callNext = true;
		private $exception;
		private $returnValue;
		
		/**
		 * @param object           $target The object that contains the method that is being invoked
		 * @param ReflectionMEthod $method The invoked method
		 * @param array            $args   Method arguments
		 */
		public function __construct($target, ReflectionMethod $method, array $args) {
			$this->target = $target;
			$this->method = $method;
			$this->args = $args;
		}
		
		/**
		 * Gets the object that contains the method that is being invoked, or null if the method is static
		 *
		 * @return object|null
		 */
		public function getTarget() {
			return $this->target;
		}
		
		/**
		 * Gets the method that is being invoked
		 *
		 * @return ReflectionMethod
		 */
		public function getMethod() {
			return $this->method;
		}
		
		/**
		 * Gets the arguments passed to the method
		 *
		 * @return array
		 */
		public function getArguments() {
			return $this->args;
		}
		
		/**
		 * Gets the exception that was thrown during interception or during the process
		 * of method invocation, or null if no exception has been thrown
		 *
		 * @return Exception|null
		 */
		public function getException() {
			return $this->exception;
		}
		
		/**
		 * Sets the exception that will be thrown
		 *
		 * @param Exception $exception
		 */
		public function setException(Exception $exception) {
			$this->exception = $exception;
		}
		
		/**
		 * Gets the return value of the method invocation
		 *
		 * @return anything
		 */
		public function getReturnValue() {
			return $this->returnValue;
		}
		
		/**
		 * Sets the return value of the method invocation
		 *
		 * @param anything $value
		 */
		public function setReturnValue($value) {
			$this->returnValue = $value;
		}
		
		/**
		 * Gets user-defined data from the dictionary
		 *
		 * @param  string|int $key If this is not set, then the entire data array is returned
		 * @return anything
		 */
		public function getData($key = null) {
			if (is_string($key) || is_int($key)) {
				return @$this->data[$key];
			} else {
				return $this->data;
			}
		}
		
		/**
		 * Sets a piece of user-defined data in the dictionary
		 *
		 * @param string|int $key
		 * @param anything   $value
		 */
		public function setData($key, $value) {
			if (is_string($key) || is_int($key)) {
				$this->data[$key] = $value;
			} else {
				$this->data[] = $value;
			}
		}
		
		/**
		 * Sets whether the next interceptor or method should be invoked
		 *
		 * @param bool $shouldCallNext
		 */
		public function callNext($shouldCallNext) {
			$this->callNext = (bool)$shouldCallNext;
		}
		
		/**
		 * Gets whether the next interceptor or method should be invoked
		 *
		 * @return bool
		 */
		public function shouldCallNext() {
			return $this->callNext;
		}
		
	}

?>