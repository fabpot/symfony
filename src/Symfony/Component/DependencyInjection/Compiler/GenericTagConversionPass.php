<?php

namespace Symfony\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Allows you to easily convert tags to DIC parameters.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class GenericTagConversionPass implements CompilerPassInterface
{
    protected $tagName;
    protected $parameterName;

    public function __construct($tagName, $parameterName)
    {
        $this->tagName = $tagName;
        $this->parameterName = $parameterName;
    }

    public function process(ContainerBuilder $container)
    {
        $container->setParameter($this->parameterName, $container->findTaggedServiceIds($this->tagName));
    }
}