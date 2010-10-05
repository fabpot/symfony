<?php

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * ProjectServiceContainer
 *
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 */
class ProjectServiceContainer extends Container
{
    protected $shared = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(new ParameterBag($this->getDefaultParameters()));
    }

    /**
     * Gets the 'barFactory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * 
     */
    protected function getBarFactoryService()
    {
        if (isset($this->shared['barFactory'])) return $this->shared['barFactory'];

        $class = NULL;
        $instance = new $class();
        $this->shared['barFactory'] = $instance;

        $this->applyInterfaceInjection($instance);

        return $instance;
    }

    /**
     * Gets the 'bar' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Object An instance returned by barFactory::createBarClass().
     */
    protected function getBarService()
    {
        if (isset($this->shared['bar'])) return $this->shared['bar'];

        $instance = $this->getBarFactoryService()->createBarClass();
        $this->shared['bar'] = $instance;

        $this->applyInterfaceInjection($instance);

        return $instance;
    }

    /**
     * Returns service ids for a given tag.
     *
     * @param string $name The tag name
     *
     * @return array An array of tags
     */
    public function findTaggedServiceIds($name)
    {
        static $tags = array (
);

        return isset($tags[$name]) ? $tags[$name] : array();
    }

    /**
     * Applies all known interface injection calls
     * 
     * @param Object $instance
     */
    protected function applyIntrefaceInjectors($instance)
    {
        if ($instance instanceof \BarClass) {
            $instance->setFoo('someValue');
        }
    }
}
