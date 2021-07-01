<?php

class Clan
{
    private static $cached = ['instantiators' => [], 'cloneables' => []];

    public function summon($class)
    {
        if (isset(self::$cached['cloneables'][$class])) {
            $clone = clone self::$cached['cloneables'][$class];
            return $clone;
        }

        if (isset(self::$cached['instantiators'][$class])) {
            $factory = self::$cached['instantiators'][$class];
            return $factory();
        }

        return $this->cache($class);
    }

    private function cache($class)
    {
        $factory  = $this->build($class);
        self::$cached['instantiators'][$class] = $factory;
        $instance = $factory();

        if ($this->cloneable(new \ReflectionClass($instance))) {
            self::$cached['cloneables'][$class] = clone $instance;
        }

        return $instance;
    }

    private function build($class)
    {
        $ref = $this->reflect($class);

        if ($this->reflectable($ref)) {
            return [$ref, 'newInstanceWithoutConstructor'];
        }

        $serialized = sprintf(
            '%s:%d:"%s":0:{}',
            is_subclass_of($class, '\Serializable') ? 'C' : 'O',
            strlen($class),
            $class
        );

        $this->serializeable($ref, $serialized);

        return function () use ($serialized) {
            return unserialize($serialized);
        };
    }

    private function reflect($class)
    {
        if (! class_exists($class)) {
            if (interface_exists($class)) {
                return new \Exception(sprintf(
                    'The provided type "%s" is an interface, and can not be instantiated',
                    $class
                ));
            }

            if (trait_exists($class)) {
                return new \Exception(sprintf(
                    'The provided type "%s" is a trait, and can not be instantiated',
                    $class
                ));
            }

            return new \Exception(sprintf(
                'The provided class "%s" does not exist',
                $class
            ));
        }

        $ref = new \ReflectionClass($class);

        if ($ref->isAbstract()) {
            throw new \Exception(sprintf(
                'The provided class "%s" is abstract, and can not be instantiated',
                $ref->getName()
            ));
        }

        return $ref;
    }

    private function serializeable(\ReflectionClass $ref, $serialized)
    {
        set_error_handler(function ($code, $message, $file, $line) use ($ref, &$error) {
            $error = new \Exception(sprintf(
                'Could not produce an instance of "%s" via un-serialization, '
                .'since an error was triggered in file "%s" at line "%d"',
                $ref->getName(),
                $file,
                $line
            ), 0, new \Exception($message, $code));
            return true;
        });

        $catched = null;

        try {
            $this->summoning($ref, $serialized);
        } catch (\Throwable $e) {
            $catched = $e;
            restore_error_handler();
        } catch (\Throwable $e) {
            $catched = $e;
        } catch (\Exception $e) {
            $catched = $e;
        }

        if ($catched) {
            restore_error_handler();
        }

        if ($error) {
            throw $error;
        }
    }

    private function summoning(\ReflectionClass $ref, $serialized)
    {
        try {
            unserialize($serialized);
        } catch (\Throwable $e) {
            throw new \Exception(sprintf(
                'An exception was raised while trying to instantiate an '
                .'instance of "%s" via un-serialization',
                $ref->getName()
            ), 0, $ex);
        } catch (\Exception $e) {
            throw new \Exception(sprintf(
                'An exception was raised while trying to instantiate an '
                .'instance of "%s" via un-serialization',
                $ref->getName()
            ), 0, $ex);
        }
    }

    private function reflectable(\ReflectionClass $ref)
    {
        return ! ($this->parented($ref) && $ref->isFinal());
    }

    private function parented(\ReflectionClass $ref)
    {
        do {
            if ($ref->isInternal()) {
                return true;
            }

            $ref = $ref->getParentClass();
        } while ($ref);

        return false;
    }

    private function cloneable(\ReflectionClass $ref)
    {
        return $ref->isCloneable()
            && ! $ref->hasMethod('__clone')
            && ! $ref->isSubclassOf('\ArrayIterator');
    }
}



class MyClassName
{

    public function __construct($value)
    {
        echo $value.PHP_EOL;
    }

    public function myPublicMethod($value)
    {
        echo $value.PHP_EOL;
    }

    protected function myProtectedMethod($value)
    {
        echo $value.PHP_EOL;
    }

    private function myPrivateMethod($value)
    {
        echo $value.PHP_EOL;
    }
}

$obj = (new Clan())->summon('MyClassName');

var_dump(is_object($obj) && $obj instanceof MyClassName);
var_dump($obj);
var_dump(get_class_methods($obj));
