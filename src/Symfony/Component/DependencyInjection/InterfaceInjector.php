<?php

namespace Symfony\Component\DependencyInjection;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * InterfaceInjector is used for Interface Injection.
 *
 * @author     Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class InterfaceInjector
{

    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private $calls = array();

    private $processedDefinitions = array();

    /**
     * Contructs interface injector by specifying the target class name
     *
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Returns the interface name
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Adds method calls if Definition is of required interface
     *
     * @param Definition $definition
     * @return void
     */
    public function processDefinition(Definition $definition, $class = null)
    {
        if (in_array($definition, $this->processedDefinitions, true)) {
            return;
        }
        $class = $class ?: $definition->getClass();
        if (!$this->supported($class)) {
            return;
        }
        foreach ($this->calls as $callback) {
            list($method, $arguments) = $callback;
            $definition->addMethodCall($method, $arguments);
        }
        $this->processedDefinitions[] = $definition;
    }

    /**
     * Inspects if current interface injector is to be used with a given class
     *
     * @param string $class
     * @return boolean
     */
    public function supported($class)
    {
        $class = new \ReflectionClass($class);
        return is_a($class->newInstance(), $this->class);
    }

    /**
     * Adds a method to call to be injected on any service implementing the interface.
     *
     * @param  string $method    The method name to call
     * @param  array  $arguments An array of arguments to pass to the method call
     *
     * @return InterfaceInjector The current instance
     */
    public function addMethodCall($method, array $arguments = array())
    {
        $this->calls[] = array($method, $arguments);

        return $this;
    }

    /**
     * Removes a method to call after service initialization.
     *
     * @param  string $method    The method name to remove
     *
     * @return Definition The current instance
     */
    public function removeMethodCall($method)
    {
        foreach ($this->calls as $i => $call) {
            if ($call[0] === $method) {
                unset($this->calls[$i]);
                break;
            }
        }

        return $this;
    }

    /**
     * Check if the current definition has a given method to call after service initialization.
     *
     * @param  string $method    The method name to search for
     *
     * @return boolean
     */
    public function hasMethodCall($method)
    {
        foreach ($this->calls as $i => $call) {
            if ($call[0] === $method) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the methods to call after service initialization.
     *
     * @return  array An array of method calls
     */
    public function getMethodCalls()
    {
        return $this->calls;
    }

}