# OBJECTS

The objects helper class provides 3 methods for making using Reflection a little cleaner and shorter.

Can be used with public/protected/private, methods & properties, including static's.

## How to use.

All methods in this helper class are self contained and called in via static methods.

### PROPERTIES

``` php

use Gin0115\WPUnit_Helpers\Objects;

class Foo {

    public    $public_property    = 'public FOO';
    protected $protected_property = 'protected FOO';
    private   $private_property   = 'private FOO';

    public    static $public_static_property    = 'public static FOO';
    protected static $protected_static_property = 'protected static FOO';
    private   static $private_static_property   = 'private static FOO';

}

// Accessing.
$instance = new Foo(); 
Objects::get_property($instance, 'public_property'); // public FOO
Objects::get_property($instance, 'protected_property'); // protected FOO
Objects::get_property($instance, 'private_property'); // private FOO

Objects::get_property($instance, 'public_static_property'); // public static FOO
Objects::get_property($instance, 'protected_static_property'); // protected static FOO
Objects::get_property($instance, 'private_static_property'); // private static FOO

// Setting
Objects::set_property($instance, 'public_property', 'new public foo'); // new public foo
Objects::set_property($instance, 'protected_property', 'new protected foo'); // new protected foo
Objects::set_property($instance, 'private_property', 'new private foo'); // new private foo

Objects::set_property($instance, 'public_static_property', 'new static public foo'); // new static public foo
Objects::set_property($instance, 'protected_static_property', 'new static protected foo'); // new static protected foo
Objects::set_property($instance, 'private_static_property', 'new static private foo'); // new static private foo
```

### METHODS

``` php

use Gin0115\WPUnit_Helpers\Objects; 

class Foo {

    public function public_method(string $value): string { 
        return 'from public_method ' . $value; 
    }
    protected function protected_method(string $value): string { 
        return 'from protected_method ' . $value; 
    }
    private function private_method(string $value): string { 
        return 'from private_method ' . $value; 
    }

    public static function public_static_method(string $value): string { 
        return 'from public_static_method ' . $value; 
    }
    protected static function protected_static_method(string $value): string { 
        return 'from protected_static_method ' . $value; 
    }
    private static function private_static_method(string $value): string { 
        return 'from private_static_method ' . $value; 
    }

}


$instance = new Foo(); 
Objects::invoke_method($instance, 'public_method'); // from public_method invoked
Objects::invoke_method($instance, 'protected_method'); // from protected_method invoked
Objects::invoke_method($instance, 'private_method'); // from private_method invoked

Objects::invoke_method($instance, 'public_static_method'); // from public_static_method invoked
Objects::invoke_method($instance, 'protected_static_method'); // from protected_static_method invoked
Objects::invoke_method($instance, 'private_static_method'); // from private_static_method invoked

```
