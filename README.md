# Blue Shift
[![Build Status](https://travis-ci.org/tmont/blueshift.png)](https://travis-ci.org/tmont/blueshift)

A simple inversion-of-control container.

## Installation
Install using composer:

```json
{
  "require": {
    "tmont/blueshift": "1.1.*"
  }
}
```

Blue Shift is PSR-4 compliant, so the following will setup autoloading once
you've `composer install`'d:

```php
require_once 'vendor/autoload.php';
```

## Usage
Some example objects:

```php
interface MyInterface {}

class MyType implements MyInterface {
  public function __construct(MyOtherType $type) {
    $this->type = $type;
  }
}

class MyOtherType {
  public function __construct($foo) {
    $this->foo = $foo;
  }
}
```

Registering a type and an instance:

```php
$container = new Tmont\BlueShift\Container();
$container
  ->registerType('MyType')
  ->registerInstance('MyOtherType', new MyOtherType('bar'));

$myType = $container->resolve('MyType');
echo $myType->type->foo; // 'bar'
```

Registering a mapped type (interface -> implementation):

```php
$container = new Tmont\BlueShift\Container();
$container
  ->registerType('MyInterface', 'MyType')
  ->registerInstance('MyOtherType', new MyOtherType('bar'));

$myType = $container->resolve('MyInterface');
echo $myType instanceof MyInterface; // true
echo $myType instanceof MyClass; // true
```

Proxies and interception using [Phroxy](https://github.com/tmont/phroxy):

```php
use Tmont\Phroxy\Interceptor;
use Tmont\Phroxy\InterceptionContext;

class MyInterceptableClass {
	public function foo() {
		return 'intercepted!';
	}
}

class MyInterceptor implements Interceptor {
	public function onBeforeMethodCall(InterceptionContext $context) {
		$context->setReturnValue('not foo');
	}

	public function onAfterMethodCall(InterceptionContext $context) {}
}

$container = new Tmont\BlueShift\Container();
$container
  ->registerType('MyInterceptableClass')
  ->proxyType('MyType')
  ->registerInterceptor(new MyInterceptor(), function(ReflectionMethod $method) {
	    return $method->getDeclaringClass()->getName() === 'MyInterceptableClass' &&
	        $method->getName() === 'foo';
    });

$obj = $container->resolve('MyInterceptableClass');
echo $obj->foo(); // 'intercepted!'
```


## Development
```bash
git clone git@github.com:tmont/blueshift.git
cd blueshift
composer install
vendor/bin/phpunit
```