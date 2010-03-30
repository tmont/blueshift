<?php

	namespace BlueShift;

	use Exception;
	use ReflectionMethod;

	class InterceptionContext {
		private $target;
		private $method;
		private $args;
		private $data;
		private $callNext;
		private $exception;
		private $returnValue;
		
		public function __construct($target, ReflectionMethod $method, array $args) {
			$this->target = $target;
			$this->method = $method;
			$this->args = $args;
			$this->data = array();
			$this->callNext = true;
		}
		
		public function getTarget() {
			return $this->target;
		}
		
		public function getMethod() {
			return $this->method;
		}
		
		public function getArguments() {
			return $this->args;
		}
		
		public function getException() {
			return $this->exception;
		}
		
		public function setException(Exception $exception) {
			$this->exception = $exception;
		}
		
		public function getReturnValue() {
			return $this->returnValue;
		}
		
		public function setReturnValue($value) {
			$this->returnValue = $value;
		}
		
		public function getData($key = null) {
			if (is_string($key)) {
				return @$this->data[$key];
			} else {
				return $this->data;
			}
		}
		
		public function setDatum($key, $value) {
			if (is_string($key) || is_int($key)) {
				$this->data[$key] = $value;
			} else {
				$this->data[] = $value;
			}
		}
		
		public function callNext($shouldCallNext) {
			$this->callNext = (bool)$shouldCallNext;
		}
		
		public function shouldCallNext() {
			return $this->callNext;
		}
		
	}

?>