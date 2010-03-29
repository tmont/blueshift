<?php

	namespace BlueShift\Util;
	
	use ReflectionClass;
	use ReflectionMethod;
	use Reflector;

	final class ReflectionUtil {

		private function __construct() {}

		public static function isProxyable(ReflectionClass $class) {
			return !$class->isFinal();
		}
		
		public static function getConstructorSignature(ReflectionMethod $constructor) {
			$params = $constructor->getParameters();
			$signature = array();
			foreach ($params as $param) {
				$class = $param->getClass();
				$signature[$param->getName()] = $class instanceof ReflectionClass ? ltrim($class->getName(), '\\') : null;
			}

			return $signature;
		}

		/**
		 * Stolen from PHP MVC code: PhpMvc\Util\ReflectionHelper
		 *
		 * @param Reflector $reflector
		 * @return array
		 */
		public static function getDocCommentValues(Reflector $reflector) {
            if ($reflector instanceof ReflectionParameter) {
                //the documentation for parameters is in the function documentation (e.g. @param)
                $reflector = $reflector->getDeclaringFunction();
            }
            if (!method_exists($reflector, 'getDocComment')) {
                //Reflector doesn't actually have the getDocComment() method, but nearly every implementation does.
                //Reflector is only there for typehinting purposes.
                //ReflectionExtension is the only type that doesn't have getDocComment()
                return array();
            }

            $doc = $reflector->getDocComment();
            if (empty($doc)) {
                //no need to do any regex matching
                return array();
            }

            preg_match_all('/^[\s\*]*@(.+?)(?:\s|$)(?:\s*)?(.+)?/m', $doc, $values);
            if (!isset($values[2]) || empty($values[2])) {
                //no matches
                return array();
            }

            return self::combineDocCommentValues($values[1], $values[2]);
        }

        private static function combineDocCommentValues(array $keys, array $values) {
            $combined = array();
            foreach ($keys as $i => $key) {
                if (isset($values[$i])) {
                    $combined[$key][] = $values[$i];
                }
            }

            return $combined;
        }

	}

?>
